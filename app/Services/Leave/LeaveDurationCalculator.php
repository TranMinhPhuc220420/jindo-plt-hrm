<?php

namespace App\Services\Leave;

use App\Models\Holiday;
use App\Models\WeekendRule;
use App\Services\Organization\CompanyContext;
use App\Services\Shift\WorkingCalendarService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class LeaveDurationCalculator
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly WorkingCalendarService $calendar,
    ) {}

    /**
     * @param  array{
     *     unit: string,
     *     start_date: string,
     *     end_date: string,
     *     is_half_day?: bool,
     *     start_at?: string|null,
     *     end_at?: string|null,
     *     employee_id?: int|null
     * }  $input
     */
    public function calculate(array $input): float
    {
        $unit = $input['unit'];
        $start = CarbonImmutable::parse($input['start_date'])->startOfDay();
        $end = CarbonImmutable::parse($input['end_date'])->startOfDay();

        if ($unit === 'half_day' || ($input['is_half_day'] ?? false)) {
            return 0.5;
        }

        if ($unit === 'hour') {
            return $this->calculateHours($input);
        }

        $employeeId = $input['employee_id'] ?? null;
        $nonWorking = $this->nonWorkingDates($start->toDateString(), $end->toDateString(), $employeeId);
        $quantity = 0.0;

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();
            if (! isset($nonWorking[$date])) {
                $quantity += 1.0;
            }
        }

        return $quantity;
    }

    /**
     * @return array<string, true>
     */
    public function nonWorkingDates(string $dateFrom, string $dateTo, ?int $employeeId = null): array
    {
        $companyId = $this->companyContext->id();
        $from = CarbonImmutable::parse($dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($dateTo)->startOfDay();

        $holidayDates = Holiday::query()
            ->where('company_id', $companyId)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
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

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $carbon = CarbonImmutable::instance($day);
            if (in_array((int) $carbon->dayOfWeek, $weekendDays, true)) {
                $result[$carbon->toDateString()] = true;
            }
        }

        if ($employeeId !== null) {
            foreach (CarbonPeriod::create($from, $to) as $day) {
                $date = CarbonImmutable::instance($day)->toDateString();
                if (isset($result[$date])) {
                    continue;
                }
                if ($this->calendar->isScheduledOff($employeeId, $date)) {
                    $result[$date] = true;
                }
            }
        }

        return $result;
    }

    public function isNonWorkingDate(string $date, ?int $employeeId = null): bool
    {
        return isset($this->nonWorkingDates($date, $date, $employeeId)[$date]);
    }

    /**
     * @param  array{
     *     start_date: string,
     *     end_date: string,
     *     start_at?: string|null,
     *     end_at?: string|null,
     *     employee_id?: int|null
     * }  $input
     */
    private function calculateHours(array $input): float
    {
        if (! empty($input['start_at']) && ! empty($input['end_at'])) {
            $start = CarbonImmutable::parse($input['start_at']);
            $end = CarbonImmutable::parse($input['end_at']);
            $minutes = max(0, $start->diffInMinutes($end));

            return round($minutes / 60, 2);
        }

        $employeeId = $input['employee_id'] ?? null;
        $hoursPerDay = 8.0;

        if ($employeeId !== null) {
            $days = $this->calendar->resolve(
                $employeeId,
                $input['start_date'],
                $input['end_date'],
            );

            if ($days !== []) {
                $minutes = 0;
                foreach ($days[0]['windows'] as $window) {
                    $start = CarbonImmutable::parse($days[0]['date'].' '.$window['start_time']);
                    $end = CarbonImmutable::parse($days[0]['date'].' '.$window['end_time']);
                    if ($end->lte($start)) {
                        $end = $end->addDay();
                    }
                    $minutes += max(0, $start->diffInMinutes($end));
                }

                if ($minutes === 0) {
                    $day = $days[0];
                    $start = CarbonImmutable::parse($day['date'].' '.$day['start_time']);
                    $end = CarbonImmutable::parse($day['date'].' '.$day['end_time']);
                    if ($end->lte($start)) {
                        $end = $end->addDay();
                    }
                    $minutes = max(0, $start->diffInMinutes($end));
                }

                $hoursPerDay = max(0.25, round($minutes / 60, 2));
            }
        }

        $nonWorking = $this->nonWorkingDates($input['start_date'], $input['end_date'], $employeeId);
        $workingDays = 0;

        foreach (CarbonPeriod::create($input['start_date'], $input['end_date']) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();
            if (! isset($nonWorking[$date])) {
                $workingDays++;
            }
        }

        return round($workingDays * $hoursPerDay, 2);
    }
}
