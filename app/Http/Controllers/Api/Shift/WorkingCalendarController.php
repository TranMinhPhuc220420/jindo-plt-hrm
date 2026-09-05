<?php

namespace App\Http\Controllers\Api\Shift;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Leave\LeaveCoverageService;
use App\Services\Shift\WorkingCalendarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkingCalendarController extends Controller
{
    public function __construct(
        private readonly WorkingCalendarService $calendar,
        private readonly LeaveCoverageService $leaveCoverage,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $canViewAll = $user?->can('can_view_shifts') ?? false;
        $canViewOwn = $user?->can('can_view_own_schedule') ?? false;

        if (! $canViewAll && ! $canViewOwn) {
            throw new DomainException(
                message: 'You are not allowed to view working calendars.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $employeeId = (int) $validated['employee_id'];

        if (! $canViewAll) {
            $linkedId = Employee::query()
                ->where('user_id', $user->id)
                ->value('id');

            if ($linkedId === null || (int) $linkedId !== $employeeId) {
                throw new DomainException(
                    message: 'You may only view your own schedule.',
                    errorCode: 'FORBIDDEN',
                    status: 403,
                );
            }
        }

        $days = $this->calendar->resolve(
            $employeeId,
            $validated['date_from'],
            $validated['date_to'],
        );

        $assignedDates = array_column($days, 'date');
        $restOnly = $this->calendar->unassignedRestDays(
            $employeeId,
            $validated['date_from'],
            $validated['date_to'],
            $assignedDates,
        );
        $offOnly = $this->calendar->scheduledOffDays(
            $employeeId,
            $validated['date_from'],
            $validated['date_to'],
            array_merge($assignedDates, array_column($restOnly, 'date')),
        );

        $merged = array_merge($days, $restOnly, $offOnly);
        usort($merged, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        $leaveMap = $this->leaveCoverage->coverageByDate(
            $employeeId,
            $validated['date_from'],
            $validated['date_to'],
        );

        $enriched = array_map(function (array $day) use ($leaveMap): array {
            $day['leave'] = $leaveMap[$day['date']] ?? null;

            return $day;
        }, $merged);

        return ApiResponse::success($enriched);
    }
}
