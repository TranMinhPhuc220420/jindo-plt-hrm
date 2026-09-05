<?php

namespace App\Services\Attendance;

use App\Models\AttendancePunchReminder;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Leave\LeaveCoverageService;
use App\Services\Notification\NotificationService;
use App\Services\Organization\CompanyContext;
use App\Services\Settings\SettingsService;
use App\Services\Shift\WorkingCalendarService;
use App\Support\SettingsDefaults;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

class AttendancePunchReminderService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly SettingsService $settings,
        private readonly WorkingCalendarService $calendar,
        private readonly LeaveCoverageService $leaveCoverage,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Scan active companies and notify employees who missed check-in or check-out.
     */
    public function sendDue(?CarbonImmutable $now = null): int
    {
        $sent = 0;

        $companies = Company::query()->where('is_active', true)->orderBy('id')->get();

        foreach ($companies as $company) {
            $sent += $this->companyContext->using(
                $company->id,
                fn (): int => $this->sendDueForCurrentCompany($now),
            );
        }

        return $sent;
    }

    private function sendDueForCurrentCompany(?CarbonImmutable $now): int
    {
        $attendance = $this->settings->all('attendance')['attendance'] ?? [];

        if (! (bool) ($attendance['punch_reminder_enabled'] ?? true)) {
            return 0;
        }

        $checkInGrace = max(0, (int) ($attendance['punch_reminder_check_in_grace_minutes'] ?? 5));
        $checkOutGrace = max(0, (int) ($attendance['punch_reminder_check_out_grace_minutes'] ?? 10));
        $timezone = $this->companyTimezone();
        $clock = ($now ?? CarbonImmutable::now($timezone))->timezone($timezone);
        $today = $clock->toDateString();
        $yesterday = $clock->subDay()->toDateString();
        $companyId = $this->companyContext->id();
        $sent = 0;

        $employees = Employee::query()
            ->with('user')
            ->where('company_id', $companyId)
            ->whereNotNull('user_id')
            ->whereIn('status', Employee::PUNCH_ALLOWED_STATUSES)
            ->get();

        foreach ($employees as $employee) {
            $days = $this->calendar->resolve($employee->id, $yesterday, $today);
            $leaveByDate = $this->leaveCoverage->coverageByDate($employee->id, $yesterday, $today);

            foreach ($days as $day) {
                if (($day['rest_kind']) !== 'none') {
                    continue;
                }

                $workDate = $day['date'];
                $leave = $leaveByDate[$workDate] ?? null;
                if (is_array($leave) && $leave['coverage'] === 'full') {
                    continue;
                }

                foreach ($day['windows'] as $window) {
                    $shiftId = (int) $window['shift_id'];
                    [$start, $end] = $this->windowBounds($workDate, $window, $timezone);
                    $record = $this->recordFor($companyId, $employee->id, $workDate, $shiftId);
                    $overnight = $window['is_night'] || $end->toDateString() !== $workDate;

                    if (
                        $workDate === $today
                        && $clock->gte($start->addMinutes($checkInGrace))
                        && ($record?->check_in_at === null)
                    ) {
                        $sent += $this->notifyOnce(
                            $employee,
                            $companyId,
                            $workDate,
                            $shiftId,
                            AttendancePunchReminder::KIND_CHECK_IN,
                        ) ? 1 : 0;
                    }

                    $checkOutInScope = $workDate === $today || ($workDate === $yesterday && $overnight);

                    if (
                        $checkOutInScope
                        && $clock->gte($end->addMinutes($checkOutGrace))
                        && $record?->check_in_at !== null
                        && $record->check_out_at === null
                    ) {
                        $sent += $this->notifyOnce(
                            $employee,
                            $companyId,
                            $workDate,
                            $shiftId,
                            AttendancePunchReminder::KIND_CHECK_OUT,
                        ) ? 1 : 0;
                    }
                }
            }
        }

        return $sent;
    }

    /**
     * @param  array{start_time: string, end_time: string, is_night: bool}  $window
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function windowBounds(string $workDate, array $window, string $timezone): array
    {
        $start = CarbonImmutable::parse($workDate.' '.$window['start_time'], $timezone);
        $end = CarbonImmutable::parse($workDate.' '.$window['end_time'], $timezone);

        if ($window['is_night'] || $end->lte($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    private function recordFor(int $companyId, int $employeeId, string $workDate, int $shiftId): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $workDate)
            ->where('shift_id', $shiftId)
            ->first();
    }

    private function notifyOnce(
        Employee $employee,
        int $companyId,
        string $workDate,
        int $shiftId,
        string $kind,
    ): bool {
        if (! $this->claim($companyId, $employee->id, $workDate, $shiftId, $kind)) {
            return false;
        }

        $user = $employee->user;
        if ($user === null) {
            return false;
        }

        $type = $kind === AttendancePunchReminder::KIND_CHECK_OUT
            ? 'attendance.check_out_reminder'
            : 'attendance.check_in_reminder';

        $this->notifications->notify(
            user: $user,
            type: $type,
            data: [
                'attendance_url' => '/attendance',
                'shift_id' => $shiftId,
                'work_date' => $workDate,
            ],
            companyId: $companyId,
        );

        return true;
    }

    private function claim(
        int $companyId,
        int $employeeId,
        string $workDate,
        int $shiftId,
        string $kind,
    ): bool {
        try {
            AttendancePunchReminder::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'work_date' => $workDate,
                'shift_id' => $shiftId,
                'kind' => $kind,
                'sent_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    private function companyTimezone(): string
    {
        $timezone = $this->settings->all('company')['company']['timezone']
            ?? SettingsDefaults::all()['company']['timezone']
            ?? 'UTC';

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }
}
