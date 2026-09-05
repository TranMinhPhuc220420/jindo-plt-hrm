<?php

namespace App\Services\Leave;

use App\Models\LeaveRequest;
use App\Services\Organization\CompanyContext;
use App\Services\Shift\WorkingCalendarService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class LeaveCoverageService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly LeaveDurationCalculator $duration,
        private readonly WorkingCalendarService $calendar,
    ) {}

    /**
     * Map Y-m-d => leave overlay for approved requests in range.
     * When multiple leave rows cover the same day, the lowest id wins.
     *
     * @return array<string, array{
     *     request_id: int,
     *     leave_type_name: string,
     *     is_paid: bool,
     *     unit: string,
     *     coverage: 'full'|'am'|'pm'|'hours',
     *     start_at: string|null,
     *     end_at: string|null
     * }>
     */
    public function coverageByDate(int $employeeId, string $from, string $to): array
    {
        $requests = $this->approvedRequests($employeeId, $from, $to);
        $map = [];

        foreach ($requests as $request) {
            foreach ($this->expandRequest($request, $from, $to) as $date => $overlay) {
                if (! isset($map[$date])) {
                    $map[$date] = $overlay;
                }
            }
        }

        return $map;
    }

    /**
     * Day-equivalent unpaid leave overlapping the payroll period.
     */
    public function unpaidDayEquivalentInPeriod(
        int $employeeId,
        string $periodStart,
        string $periodEnd,
    ): float {
        $requests = $this->approvedRequests($employeeId, $periodStart, $periodEnd)
            ->filter(fn (LeaveRequest $row) => $row->leaveType !== null && ! $row->leaveType->is_paid);

        $total = 0.0;

        foreach ($requests as $request) {
            $total += $this->dayEquivalentInWindow($request, $periodStart, $periodEnd);
        }

        return round($total, 2);
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    private function approvedRequests(int $employeeId, string $from, string $to): Collection
    {
        return LeaveRequest::query()
            ->with('leaveType')
            ->where('company_id', $this->companyContext->id())
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, array{
     *     request_id: int,
     *     leave_type_name: string,
     *     is_paid: bool,
     *     unit: string,
     *     coverage: 'full'|'am'|'pm'|'hours',
     *     start_at: string|null,
     *     end_at: string|null
     * }>
     */
    private function expandRequest(LeaveRequest $request, string $windowFrom, string $windowTo): array
    {
        $base = [
            'request_id' => $request->id,
            'leave_type_name' => (string) ($request->leaveType->name ?? ''),
            'is_paid' => (bool) ($request->leaveType->is_paid ?? true),
            'unit' => $request->unit,
            'start_at' => null,
            'end_at' => null,
        ];

        $leaveStart = CarbonImmutable::parse($request->start_date)->toDateString();
        $leaveEnd = CarbonImmutable::parse($request->end_date)->toDateString();
        $from = max($leaveStart, $windowFrom);
        $to = min($leaveEnd, $windowTo);

        if ($to < $from) {
            return [];
        }

        if ($request->unit === 'half_day' || $request->is_half_day) {
            $period = $request->half_day_period === 'pm' ? 'pm' : 'am';

            return [
                $from => array_merge($base, [
                    'unit' => 'half_day',
                    'coverage' => $period,
                ]),
            ];
        }

        if ($request->unit === 'hour') {
            return $this->expandHourly($request, $base, $from, $to);
        }

        $nonWorking = $this->duration->nonWorkingDates($from, $to, $request->employee_id);
        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();
            if (isset($nonWorking[$date])) {
                continue;
            }
            $days[$date] = array_merge($base, ['coverage' => 'full']);
        }

        return $days;
    }

    /**
     * @param  array{
     *     request_id: int,
     *     leave_type_name: string,
     *     is_paid: bool,
     *     unit: string,
     *     start_at: string|null,
     *     end_at: string|null
     * }  $base
     * @return array<string, array{
     *     request_id: int,
     *     leave_type_name: string,
     *     is_paid: bool,
     *     unit: string,
     *     coverage: 'full'|'am'|'pm'|'hours',
     *     start_at: string|null,
     *     end_at: string|null
     * }>
     */
    private function expandHourly(LeaveRequest $request, array $base, string $from, string $to): array
    {
        $startAt = $request->start_at !== null
            ? CarbonImmutable::parse($request->start_at)
            : CarbonImmutable::parse($request->start_date)->startOfDay();
        $endAt = $request->end_at !== null
            ? CarbonImmutable::parse($request->end_at)
            : CarbonImmutable::parse($request->end_date)->endOfDay();

        $windowStart = CarbonImmutable::parse($from)->startOfDay();
        $windowEnd = CarbonImmutable::parse($to)->endOfDay();
        $overlapStart = $startAt->greaterThan($windowStart) ? $startAt : $windowStart;
        $overlapEnd = $endAt->lessThan($windowEnd) ? $endAt : $windowEnd;

        if ($overlapEnd->lte($overlapStart)) {
            return [];
        }

        $days = [];
        $cursor = $overlapStart->startOfDay();
        $last = $overlapEnd->startOfDay();

        while ($cursor->lte($last)) {
            $date = $cursor->toDateString();
            $dayStart = $cursor->startOfDay();
            $dayEnd = $cursor->endOfDay();
            $segStart = $overlapStart->greaterThan($dayStart) ? $overlapStart : $dayStart;
            $segEnd = $overlapEnd->lessThan($dayEnd) ? $overlapEnd : $dayEnd;

            if ($segEnd->gt($segStart)) {
                $days[$date] = array_merge($base, [
                    'coverage' => 'hours',
                    'start_at' => $segStart->toIso8601String(),
                    'end_at' => $segEnd->toIso8601String(),
                ]);
            }

            $cursor = $cursor->addDay();
        }

        return $days;
    }

    private function dayEquivalentInWindow(
        LeaveRequest $request,
        string $periodStart,
        string $periodEnd,
    ): float {
        if ($request->unit === 'half_day' || $request->is_half_day) {
            $date = CarbonImmutable::parse($request->start_date)->toDateString();
            if ($date < $periodStart || $date > $periodEnd) {
                return 0.0;
            }
            if ($this->duration->isNonWorkingDate($date, $request->employee_id)) {
                return 0.0;
            }

            return 0.5;
        }

        if ($request->unit === 'hour') {
            return $this->hourlyDayEquivalent($request, $periodStart, $periodEnd);
        }

        $leaveStart = CarbonImmutable::parse($request->start_date)->toDateString();
        $leaveEnd = CarbonImmutable::parse($request->end_date)->toDateString();
        $from = max($leaveStart, $periodStart);
        $to = min($leaveEnd, $periodEnd);

        if ($to < $from) {
            return 0.0;
        }

        $nonWorking = $this->duration->nonWorkingDates($from, $to, $request->employee_id);
        $quantity = 0.0;

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $date = CarbonImmutable::instance($day)->toDateString();
            if (! isset($nonWorking[$date])) {
                $quantity += 1.0;
            }
        }

        return $quantity;
    }

    private function hourlyDayEquivalent(
        LeaveRequest $request,
        string $periodStart,
        string $periodEnd,
    ): float {
        $startAt = $request->start_at !== null
            ? CarbonImmutable::parse($request->start_at)
            : CarbonImmutable::parse($request->start_date)->startOfDay();
        $endAt = $request->end_at !== null
            ? CarbonImmutable::parse($request->end_at)
            : CarbonImmutable::parse($request->end_date)->endOfDay();

        $windowStart = CarbonImmutable::parse($periodStart)->startOfDay();
        $windowEnd = CarbonImmutable::parse($periodEnd)->endOfDay();
        $overlapStart = $startAt->greaterThan($windowStart) ? $startAt : $windowStart;
        $overlapEnd = $endAt->lessThan($windowEnd) ? $endAt : $windowEnd;

        if ($overlapEnd->lte($overlapStart)) {
            return 0.0;
        }

        $hours = $overlapStart->diffInMinutes($overlapEnd) / 60;
        $hoursPerDay = $this->hoursPerDay($request->employee_id, $periodStart, $periodEnd);

        return round($hours / $hoursPerDay, 2);
    }

    private function hoursPerDay(int $employeeId, string $from, string $to): float
    {
        $days = $this->calendar->resolve($employeeId, $from, $to);

        if ($days === []) {
            return 8.0;
        }

        $day = $days[0];
        $minutes = 0;
        foreach ($day['windows'] as $window) {
            $start = CarbonImmutable::parse($day['date'].' '.$window['start_time']);
            $end = CarbonImmutable::parse($day['date'].' '.$window['end_time']);
            if ($end->lte($start)) {
                $end = $end->addDay();
            }
            $minutes += max(0, $start->diffInMinutes($end));
        }
        if ($minutes === 0) {
            $start = CarbonImmutable::parse($day['date'].' '.$day['start_time']);
            $end = CarbonImmutable::parse($day['date'].' '.$day['end_time']);
            if ($end->lte($start)) {
                $end = $end->addDay();
            }
            $minutes = max(0, $start->diffInMinutes($end));
        }

        return max(0.25, round($minutes / 60, 2));
    }
}
