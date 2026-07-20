<?php

namespace App\Services\Attendance;

use App\Models\OvertimeRule;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\Leave\LeaveCoverageService;
use App\Services\Organization\CompanyContext;
use App\Services\Shift\WorkingCalendarService;
use App\Support\SettingsDefaults;
use Carbon\CarbonImmutable;

class AttendanceMetricsCalculator
{
    public function __construct(
        private readonly WorkingCalendarService $calendar,
        private readonly CompanyContext $companyContext,
        private readonly LeaveCoverageService $leaveCoverage,
    ) {}

    /**
     * @return array{
     *     worked_minutes: int,
     *     late_minutes: int,
     *     early_leave_minutes: int,
     *     overtime_minutes: int,
     *     break_minutes: int
     * }
     */
    public function compute(
        int $employeeId,
        string $workDate,
        ?CarbonImmutable $checkInAt,
        ?CarbonImmutable $checkOutAt,
        ?int $breakMinutesOverride = null,
    ): array {
        $window = $this->resolveEffectiveWindow($employeeId, $workDate);
        $breakMinutes = $breakMinutesOverride
            ?? $this->defaultBreakMinutes($window['shift_id'] ?? null);

        $worked = 0;
        $late = 0;
        $early = 0;
        $overtime = 0;

        if ($checkInAt !== null && $checkOutAt !== null && $checkOutAt->gte($checkInAt)) {
            $worked = max(0, (int) $checkInAt->diffInMinutes($checkOutAt) - $breakMinutes);
        }

        if ($window !== null && $checkInAt !== null) {
            $scheduledStart = $this->atDate($workDate, $window['start_time']);
            if ($checkInAt->gt($scheduledStart)) {
                $late = (int) $scheduledStart->diffInMinutes($checkInAt);
            }
        }

        if ($window !== null && $checkOutAt !== null) {
            $scheduledEnd = $this->atDate($workDate, $window['end_time']);
            if (($window['is_night']) && $window['end_time'] < $window['start_time']) {
                $scheduledEnd = $scheduledEnd->addDay();
            }

            if ($checkOutAt->lt($scheduledEnd)) {
                $early = (int) $checkOutAt->diffInMinutes($scheduledEnd);
            } else {
                $otGrace = $this->appliesAfterMinutes();
                $otStart = $scheduledEnd->addMinutes($otGrace);
                if ($checkOutAt->gt($otStart)) {
                    $overtime = (int) $otStart->diffInMinutes($checkOutAt);
                }
            }
        }

        return [
            'worked_minutes' => $worked,
            'late_minutes' => $late,
            'early_leave_minutes' => $early,
            'overtime_minutes' => $overtime,
            'break_minutes' => $breakMinutes,
        ];
    }

    /**
     * @return array{shift_id: int, start_time: string, end_time: string, is_night: bool}|null
     */
    private function resolveEffectiveWindow(int $employeeId, string $workDate): ?array
    {
        $window = $this->resolveWindow($employeeId, $workDate);
        if ($window === null) {
            return null;
        }

        $leaveMap = $this->leaveCoverage->coverageByDate($employeeId, $workDate, $workDate);
        $leave = $leaveMap[$workDate] ?? null;
        if ($leave === null) {
            return $window;
        }

        return $this->applyLeaveToWindow($window, $workDate, $leave);
    }

    /**
     * @param  array{shift_id: int, start_time: string, end_time: string, is_night: bool}  $window
     * @param  array{
     *     request_id: int,
     *     leave_type_name: string,
     *     is_paid: bool,
     *     unit: string,
     *     coverage: 'full'|'am'|'pm'|'hours',
     *     start_at: string|null,
     *     end_at: string|null
     * }  $leave
     * @return array{shift_id: int, start_time: string, end_time: string, is_night: bool}|null
     */
    private function applyLeaveToWindow(array $window, string $workDate, array $leave): ?array
    {
        $coverage = $leave['coverage'];

        if ($coverage === 'full') {
            return null;
        }

        $start = $this->atDate($workDate, $window['start_time']);
        $end = $this->atDate($workDate, $window['end_time']);
        if ($window['is_night'] && $window['end_time'] < $window['start_time']) {
            $end = $end->addDay();
        }

        $mid = $start->addMinutes((int) floor($start->diffInMinutes($end) / 2));

        if ($coverage === 'am') {
            return [
                'shift_id' => $window['shift_id'],
                'start_time' => $mid->format('H:i'),
                'end_time' => $end->format('H:i'),
                'is_night' => $window['is_night'],
            ];
        }

        if ($coverage === 'pm') {
            return [
                'shift_id' => $window['shift_id'],
                'start_time' => $start->format('H:i'),
                'end_time' => $mid->format('H:i'),
                'is_night' => $window['is_night'],
            ];
        }

        return $this->applyHourlyLeave($window, $start, $end, $leave);
    }

    /**
     * @param  array{shift_id: int, start_time: string, end_time: string, is_night: bool}  $window
     * @param  array{start_at: string|null, end_at: string|null}  $leave
     * @return array{shift_id: int, start_time: string, end_time: string, is_night: bool}|null
     */
    private function applyHourlyLeave(
        array $window,
        CarbonImmutable $shiftStart,
        CarbonImmutable $shiftEnd,
        array $leave,
    ): ?array {
        if ($leave['start_at'] === null || $leave['end_at'] === null) {
            return $window;
        }

        $leaveStart = CarbonImmutable::parse($leave['start_at'], $this->companyTimezone());
        $leaveEnd = CarbonImmutable::parse($leave['end_at'], $this->companyTimezone());

        $coversStart = $leaveStart->lte($shiftStart) && $leaveEnd->gt($shiftStart);
        $coversEnd = $leaveStart->lt($shiftEnd) && $leaveEnd->gte($shiftEnd);

        if ($coversStart && $coversEnd) {
            return null;
        }

        $effectiveStart = $coversStart ? $leaveEnd : $shiftStart;
        $effectiveEnd = $coversEnd ? $leaveStart : $shiftEnd;

        if ($effectiveEnd->lte($effectiveStart)) {
            return null;
        }

        return [
            'shift_id' => $window['shift_id'],
            'start_time' => $effectiveStart->format('H:i'),
            'end_time' => $effectiveEnd->format('H:i'),
            'is_night' => $window['is_night'],
        ];
    }

    /**
     * @return array{shift_id: int, start_time: string, end_time: string, is_night: bool}|null
     */
    private function resolveWindow(int $employeeId, string $workDate): ?array
    {
        $days = $this->calendar->resolve($employeeId, $workDate, $workDate);
        $day = $days[0] ?? null;

        if ($day === null) {
            return null;
        }

        $shift = Shift::query()->find($day['shift_id']);

        return [
            'shift_id' => $day['shift_id'],
            'start_time' => $day['start_time'],
            'end_time' => $day['end_time'],
            'is_night' => (bool) ($shift?->is_night || $shift?->kind === 'night'),
        ];
    }

    private function defaultBreakMinutes(?int $shiftId): int
    {
        if ($shiftId === null) {
            return 0;
        }

        return (int) (Shift::query()->whereKey($shiftId)->value('break_minutes') ?? 0);
    }

    private function appliesAfterMinutes(): int
    {
        $rule = OvertimeRule::query()
            ->where('company_id', $this->companyContext->id())
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        if ($rule === null) {
            return 0;
        }

        return (int) ($rule->applies_after_minutes ?? 0);
    }

    private function atDate(string $workDate, string $time): CarbonImmutable
    {
        $normalized = strlen($time) === 5 ? $time.':00' : $time;
        $tz = $this->companyTimezone();

        return CarbonImmutable::parse($workDate.' '.$normalized, $tz);
    }

    private function companyTimezone(): string
    {
        $stored = Setting::query()
            ->where('company_id', $this->companyContext->id())
            ->where('group', 'company')
            ->where('key', 'timezone')
            ->value('value');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return (string) (SettingsDefaults::all()['company']['timezone'] ?? 'UTC');
    }
}
