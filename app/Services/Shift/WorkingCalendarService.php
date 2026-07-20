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
 * Unassigned days are omitted from the result set.
 * Holidays and weekend rest days set is_holiday=true.
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
     *     is_holiday: bool
     * }>
     */
    public function resolve(int $employeeId, string $dateFrom, string $dateTo): array
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

        $nonWorking = $this->nonWorkingDates($companyId, $from->toDateString(), $to->toDateString());
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

            $days[] = [
                'date' => $date,
                'shift_id' => $shift->id,
                'shift_name' => $shift->name,
                'start_time' => $this->formatTime($shift->start_time),
                'end_time' => $this->formatTime($shift->end_time),
                'is_holiday' => isset($nonWorking[$date]),
            ];
        }

        return $days;
    }

    /**
     * @return array<string, true>
     */
    private function nonWorkingDates(int $companyId, string $dateFrom, string $dateTo): array
    {
        $holidayDates = Holiday::query()
            ->where('company_id', $companyId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())
            ->all();

        $weekendDays = WeekendRule::query()
            ->where('company_id', $companyId)
            ->value('weekend_days') ?? [0, 6];

        $result = [];

        foreach ($holidayDates as $date) {
            $result[$date] = true;
        }

        foreach (CarbonPeriod::create($dateFrom, $dateTo) as $day) {
            $carbon = CarbonImmutable::instance($day);
            if (in_array((int) $carbon->dayOfWeek, $weekendDays, true)) {
                $result[$carbon->toDateString()] = true;
            }
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
