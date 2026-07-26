<?php

namespace App\Services\Shift;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\ShiftAssignment;
use App\Models\WeekendRule;
use App\Services\Organization\CompanyContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Resolves expected work windows for Attendance/Leave consumers.
 *
 * Unassigned days are omitted from resolve().
 * Holidays and weekend rest days set is_holiday=true with rest_kind metadata.
 * Use unassignedRestDays() for UI calendars that need rest days without a shift.
 */
class WorkingCalendarService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @return list<array{
     *     date: string,
     *     shift_id: int,
     *     shift_name: string,
     *     start_time: string,
     *     end_time: string,
     *     is_holiday: bool,
     *     rest_kind: 'none'|'weekend'|'holiday',
     *     holiday_name: string|null
     * }>
     */
    public function resolve(int $employeeId, string $dateFrom, string $dateTo): array
    {
        [$companyId, $from, $to] = $this->assertEmployeeRange($employeeId, $dateFrom, $dateTo);

        $assignments = ShiftAssignment::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->with('shift')
            ->where('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from): void {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->get();

        $nonWorking = $this->nonWorkingMeta($companyId, $from->toDateString(), $to->toDateString());
        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();
            $assignment = $assignments->first(function (ShiftAssignment $row) use ($date): bool {
                $startOk = $row->start_date->toDateString() <= $date;
                $endOk = $row->end_date === null || $row->end_date->toDateString() >= $date;

                return $startOk && $endOk;
            });

            if ($assignment === null || $assignment->shift === null) {
                continue;
            }

            $shift = $assignment->shift;
            $rest = $nonWorking[$date] ?? null;
            $restKind = $rest['rest_kind'] ?? 'none';

            $days[] = [
                'date' => $date,
                'shift_id' => $shift->id,
                'shift_name' => $shift->name,
                'start_time' => $this->formatTime($shift->start_time),
                'end_time' => $this->formatTime($shift->end_time),
                'is_holiday' => $restKind !== 'none',
                'rest_kind' => $restKind,
                'holiday_name' => $rest['holiday_name'] ?? null,
            ];
        }

        return $days;
    }

    /**
     * Rest / holiday days in range that have no shift assignment (for schedule UI).
     *
     * @param  list<string>  $assignedDates
     * @return list<array{
     *     date: string,
     *     shift_id: null,
     *     shift_name: null,
     *     start_time: null,
     *     end_time: null,
     *     is_holiday: true,
     *     rest_kind: 'weekend'|'holiday',
     *     holiday_name: string|null
     * }>
     */
    public function unassignedRestDays(
        int $employeeId,
        string $dateFrom,
        string $dateTo,
        array $assignedDates,
    ): array {
        [$companyId, $from, $to] = $this->assertEmployeeRange($employeeId, $dateFrom, $dateTo);

        $assigned = array_fill_keys($assignedDates, true);
        $nonWorking = $this->nonWorkingMeta($companyId, $from->toDateString(), $to->toDateString());
        $days = [];

        foreach ($nonWorking as $date => $meta) {
            if (isset($assigned[$date])) {
                continue;
            }

            $days[] = [
                'date' => $date,
                'shift_id' => null,
                'shift_name' => null,
                'start_time' => null,
                'end_time' => null,
                'is_holiday' => true,
                'rest_kind' => $meta['rest_kind'],
                'holiday_name' => $meta['holiday_name'],
            ];
        }

        usort($days, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return $days;
    }

    /**
     * @return array{0: int, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    private function assertEmployeeRange(int $employeeId, string $dateFrom, string $dateTo): array
    {
        $companyId = $this->companyContext->id();
        $employee = Employee::query()->find($employeeId);

        if ($employee === null || $employee->company_id !== $companyId) {
            throw new DomainException(
                message: 'Employee does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }

        $from = CarbonImmutable::parse($dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($dateTo)->startOfDay();

        if ($to->lt($from)) {
            throw new DomainException(
                message: 'date_to must be on or after date_from.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }

        return [$companyId, $from, $to];
    }

    /**
     * @return array<string, array{rest_kind: 'weekend'|'holiday', holiday_name: string|null}>
     */
    private function nonWorkingMeta(int $companyId, string $dateFrom, string $dateTo): array
    {
        $holidays = Holiday::query()
            ->where('company_id', $companyId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->get(['date', 'name']);

        $weekendDays = WeekendRule::query()
            ->where('company_id', $companyId)
            ->value('weekend_days') ?? [0, 6];

        $result = [];

        foreach (CarbonPeriod::create($dateFrom, $dateTo) as $day) {
            $carbon = CarbonImmutable::instance($day);
            $date = $carbon->toDateString();

            if (in_array((int) $carbon->dayOfWeek, $weekendDays, true)) {
                $result[$date] = [
                    'rest_kind' => 'weekend',
                    'holiday_name' => null,
                ];
            }
        }

        foreach ($holidays as $holiday) {
            $date = CarbonImmutable::parse($holiday->date)->toDateString();
            $result[$date] = [
                'rest_kind' => 'holiday',
                'holiday_name' => $holiday->name,
            ];
        }

        return $result;
    }

    private function formatTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($time)->format('H:i');
        }

        $raw = (string) $time;

        if (preg_match('/^\d{2}:\d{2}/', $raw, $m) === 1) {
            return substr($raw, 0, 5);
        }

        return $raw;
    }
}
