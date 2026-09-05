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
use Illuminate\Support\Collection;

/**
 * Resolves expected work windows for Attendance/Leave consumers.
 *
 * Unassigned days are omitted from resolve().
 * Holidays and weekend rest days set is_holiday=true with rest_kind metadata.
 * Scheduled weekdays that are not in the assignment mask use rest_kind=off.
 * Use unassignedRestDays() / scheduledOffDays() for UI calendars.
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
     *     holiday_name: string|null,
     *     windows: list<array{shift_id: int, shift_name: string, start_time: string, end_time: string, assignment_id: int, is_night: bool}>
     * }>
     */
    public function resolve(int $employeeId, string $dateFrom, string $dateTo): array
    {
        [$companyId, $from, $to] = $this->assertEmployeeRange($employeeId, $dateFrom, $dateTo);

        $assignments = $this->assignmentsOverlapping($companyId, $employeeId, $from, $to);
        $nonWorking = $this->nonWorkingMeta($companyId, $from->toDateString(), $to->toDateString());
        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();
            $windows = $this->windowsOnDate($assignments, $date);

            if ($windows === []) {
                continue;
            }

            $first = $windows[0];
            $rest = $nonWorking[$date] ?? null;
            $restKind = $rest['rest_kind'] ?? 'none';

            $days[] = [
                'date' => $date,
                'shift_id' => $first['shift_id'],
                'shift_name' => $first['shift_name'],
                'start_time' => $first['start_time'],
                'end_time' => $first['end_time'],
                'is_holiday' => $restKind !== 'none',
                'rest_kind' => $restKind,
                'holiday_name' => $rest['holiday_name'] ?? null,
                'windows' => $windows,
            ];
        }

        return $days;
    }

    /**
     * @return list<array{shift_id: int, shift_name: string, start_time: string, end_time: string, assignment_id: int, is_night: bool}>
     */
    public function windowsForDate(int $employeeId, string $date): array
    {
        [$companyId] = $this->assertEmployeeRange($employeeId, $date, $date);
        $day = CarbonImmutable::parse($date)->startOfDay();
        $assignments = $this->assignmentsOverlapping($companyId, $employeeId, $day, $day);

        return $this->windowsOnDate($assignments, $date);
    }

    public function assignmentForDate(int $employeeId, string $date): ?ShiftAssignment
    {
        [$companyId] = $this->assertEmployeeRange($employeeId, $date, $date);
        $day = CarbonImmutable::parse($date)->startOfDay();
        $assignments = $this->assignmentsOverlapping($companyId, $employeeId, $day, $day);
        $windows = $this->windowsOnDate($assignments, $date);

        if ($windows === []) {
            return null;
        }

        return $assignments->firstWhere('id', $windows[0]['assignment_id']);
    }

    /**
     * True when a date-range assignment covers the day but no weekday/window applies.
     */
    public function isScheduledOff(int $employeeId, string $date): bool
    {
        [$companyId] = $this->assertEmployeeRange($employeeId, $date, $date);
        $day = CarbonImmutable::parse($date)->startOfDay();
        $assignments = $this->assignmentsOverlapping($companyId, $employeeId, $day, $day);

        if ($assignments->isEmpty()) {
            return false;
        }

        return $this->windowsOnDate($assignments, $date) === [];
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
     *     holiday_name: string|null,
     *     windows: list<empty>
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
                'windows' => [],
            ];
        }

        usort($days, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return $days;
    }

    /**
     * Weekdays inside an assignment range that are not worked and are not company rest days.
     *
     * @param  list<string>  $skipDates
     * @return list<array{
     *     date: string,
     *     shift_id: null,
     *     shift_name: null,
     *     start_time: null,
     *     end_time: null,
     *     is_holiday: true,
     *     rest_kind: 'off',
     *     holiday_name: null,
     *     windows: list<empty>
     * }>
     */
    public function scheduledOffDays(
        int $employeeId,
        string $dateFrom,
        string $dateTo,
        array $skipDates,
    ): array {
        [$companyId, $from, $to] = $this->assertEmployeeRange($employeeId, $dateFrom, $dateTo);

        $skip = array_fill_keys($skipDates, true);
        $assignments = $this->assignmentsOverlapping($companyId, $employeeId, $from, $to);
        $nonWorking = $this->nonWorkingMeta($companyId, $from->toDateString(), $to->toDateString());
        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();

            if (isset($skip[$date]) || isset($nonWorking[$date])) {
                continue;
            }

            if (! $this->dateCoveredByAssignmentRange($assignments, $date)) {
                continue;
            }

            if ($this->windowsOnDate($assignments, $date) !== []) {
                continue;
            }

            $days[] = [
                'date' => $date,
                'shift_id' => null,
                'shift_name' => null,
                'start_time' => null,
                'end_time' => null,
                'is_holiday' => true,
                'rest_kind' => 'off',
                'holiday_name' => null,
                'windows' => [],
            ];
        }

        return $days;
    }

    /**
     * @return Collection<int, ShiftAssignment>
     */
    private function assignmentsOverlapping(
        int $companyId,
        int $employeeId,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): Collection {
        return ShiftAssignment::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->with('shift')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from): void {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $from->toDateString());
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * @param  Collection<int, ShiftAssignment>  $assignments
     * @return list<array{shift_id: int, shift_name: string, start_time: string, end_time: string, assignment_id: int, is_night: bool}>
     */
    private function windowsOnDate(Collection $assignments, string $date): array
    {
        $dayOfWeek = (int) CarbonImmutable::parse($date)->dayOfWeek;
        $windows = [];

        foreach ($assignments as $row) {
            if (! $this->dateInAssignmentRange($row, $date)) {
                continue;
            }

            if (! ShiftSchedule::appliesOnWeekday($row->weekdays, $dayOfWeek)) {
                continue;
            }

            $shift = $row->shift;
            if ($shift === null) {
                continue;
            }

            $windows[] = [
                'shift_id' => $shift->id,
                'shift_name' => $shift->name,
                'start_time' => ShiftSchedule::formatTime($shift->start_time),
                'end_time' => ShiftSchedule::formatTime($shift->end_time),
                'assignment_id' => $row->id,
                'is_night' => (bool) ($shift->is_night || $shift->kind === 'night'),
            ];
        }

        usort($windows, function (array $a, array $b): int {
            return strcmp($a['start_time'], $b['start_time']);
        });

        return $windows;
    }

    /**
     * @param  Collection<int, ShiftAssignment>  $assignments
     */
    private function dateCoveredByAssignmentRange(Collection $assignments, string $date): bool
    {
        foreach ($assignments as $row) {
            if ($this->dateInAssignmentRange($row, $date)) {
                return true;
            }
        }

        return false;
    }

    private function dateInAssignmentRange(ShiftAssignment $row, string $date): bool
    {
        $startOk = $row->start_date->toDateString() <= $date;
        $endOk = $row->end_date === null || $row->end_date->toDateString() >= $date;

        return $startOk && $endOk;
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
}
