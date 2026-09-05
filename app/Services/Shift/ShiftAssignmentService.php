<?php

namespace App\Services\Shift;

use App\Events\ShiftAssigned;
use App\Events\ShiftAssignmentChanged;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class ShiftAssignmentService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{employee_id?: int, shift_id?: int}  $filters
     * @return LengthAwarePaginator<int, ShiftAssignment>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ShiftAssignment::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['shift', 'employee'])
            ->orderByDesc('start_date');

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ShiftAssignment
    {
        return ShiftAssignment::query()
            ->where('company_id', $this->companyContext->id())
            ->with(['shift', 'employee'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ShiftAssignment
    {
        $companyId = $this->companyContext->id();
        $employee = $this->assertEmployeeInCompany((int) $data['employee_id'], $companyId);

        if (! $employee->canPunch()) {
            throw new DomainException(
                message: 'Cannot assign a shift to an inactive employee.',
                errorCode: 'SHIFT_EMPLOYEE_INACTIVE',
                status: 422,
            );
        }

        $shift = $this->assertShiftInCompany((int) $data['shift_id'], $companyId);

        $startDate = CarbonImmutable::parse($data['start_date'])->toDateString();
        $endDate = ! empty($data['end_date'])
            ? CarbonImmutable::parse((string) $data['end_date'])->toDateString()
            : null;

        $this->assertDateOrder($startDate, $endDate);
        $weekdays = $this->normalizeWeekdays($data['weekdays'] ?? null);
        $this->assertNoOverlap($companyId, $employee->id, $startDate, $endDate, $weekdays, $shift);

        $assignment = ShiftAssignment::query()->create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'weekdays' => $weekdays,
        ]);

        $this->audit->write(
            action: 'shift.assignment_created',
            subject: $assignment,
            payload: [
                'employee_id' => $assignment->employee_id,
                'shift_id' => $assignment->shift_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'weekdays' => $weekdays,
            ],
        );

        ShiftAssigned::dispatch($assignment);

        return $assignment->fresh(['shift', 'employee']);
    }

    /**
     * @param  array{employee_id?: int, shift_id?: int, start_date?: string, end_date?: string|null, weekdays?: list<int>|null}  $data
     */
    public function update(ShiftAssignment $assignment, array $data): ShiftAssignment
    {
        $this->assertCompanyScope($assignment->company_id);
        $companyId = $assignment->company_id;

        $employeeId = (int) ($data['employee_id'] ?? $assignment->employee_id);
        $shiftId = (int) ($data['shift_id'] ?? $assignment->shift_id);
        $this->assertEmployeeInCompany($employeeId, $companyId);
        $shift = $this->assertShiftInCompany($shiftId, $companyId);

        $startDate = isset($data['start_date'])
            ? CarbonImmutable::parse($data['start_date'])->toDateString()
            : $assignment->start_date->toDateString();

        $endDate = array_key_exists('end_date', $data)
            ? ($data['end_date'] !== null && $data['end_date'] !== ''
                ? CarbonImmutable::parse($data['end_date'])->toDateString()
                : null)
            : $assignment->end_date?->toDateString();

        $this->assertDateOrder($startDate, $endDate);
        $weekdays = array_key_exists('weekdays', $data)
            ? $this->normalizeWeekdays($data['weekdays'])
            : $assignment->weekdays;
        $this->assertNoOverlap($companyId, $employeeId, $startDate, $endDate, $weekdays, $shift, $assignment->id);

        $assignment->update([
            'employee_id' => $employeeId,
            'shift_id' => $shiftId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'weekdays' => $weekdays,
        ]);

        $this->audit->write(
            action: 'shift.assignment_updated',
            subject: $assignment,
            payload: [
                'employee_id' => $employeeId,
                'shift_id' => $shiftId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'weekdays' => $weekdays,
            ],
        );

        ShiftAssignmentChanged::dispatch($assignment);

        return $assignment->fresh(['shift', 'employee']);
    }

    public function closeFrom(Employee $employee, string $effectiveOn): void
    {
        $this->assertCompanyScope($employee->company_id);

        $assignments = ShiftAssignment::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($effectiveOn): void {
                $query->where('start_date', '>', $effectiveOn)
                    ->orWhere(function ($running) use ($effectiveOn): void {
                        $running->where('start_date', '<=', $effectiveOn)
                            ->where(function ($end) use ($effectiveOn): void {
                                $end->whereNull('end_date')
                                    ->orWhere('end_date', '>', $effectiveOn);
                            });
                    });
            })
            ->orderBy('start_date')
            ->get();

        foreach ($assignments as $assignment) {
            $startDate = $assignment->start_date->toDateString();

            if ($startDate > $effectiveOn) {
                $assignment->delete();

                $this->audit->write(
                    action: 'shift.assignment_deleted',
                    subject: $assignment,
                    payload: [
                        'employee_id' => $assignment->employee_id,
                        'shift_id' => $assignment->shift_id,
                        'reason' => 'Closed during employee offboarding',
                    ],
                );

                ShiftAssignmentChanged::dispatch($assignment);

                continue;
            }

            $assignment->end_date = CarbonImmutable::parse($effectiveOn);
            $assignment->save();

            $this->audit->write(
                action: 'shift.assignment_updated',
                subject: $assignment,
                payload: [
                    'employee_id' => $assignment->employee_id,
                    'shift_id' => $assignment->shift_id,
                    'end_date' => $effectiveOn,
                    'reason' => 'Closed during employee offboarding',
                ],
            );

            ShiftAssignmentChanged::dispatch($assignment);
        }
    }

    public function delete(ShiftAssignment $assignment): void
    {
        $this->assertCompanyScope($assignment->company_id);

        $assignment->delete();

        $this->audit->write(
            action: 'shift.assignment_deleted',
            subject: $assignment,
            payload: [
                'employee_id' => $assignment->employee_id,
                'shift_id' => $assignment->shift_id,
            ],
        );
    }

    /**
     * @param  array<mixed>|null  $weekdays
     */
    private function assertNoOverlap(
        int $companyId,
        int $employeeId,
        string $startDate,
        ?string $endDate,
        ?array $weekdays,
        Shift $shift,
        ?int $excludeId = null,
    ): void {
        $query = ShiftAssignment::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->with('shift');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $openEnd = $endDate ?? '9999-12-31';

        $candidates = $query->where(function ($q) use ($startDate, $openEnd): void {
            $q->whereDate('start_date', '<=', $openEnd)
                ->where(function ($endQ) use ($startDate): void {
                    $endQ->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $startDate);
                });
        })->get();

        $newStart = ShiftSchedule::formatTime($shift->start_time);
        $newEnd = ShiftSchedule::formatTime($shift->end_time);

        foreach ($candidates as $existing) {
            if (! ShiftSchedule::weekdaysIntersect($weekdays, $existing->weekdays)) {
                continue;
            }

            $existingShift = $existing->shift;
            if ($existingShift === null) {
                continue;
            }

            if (ShiftSchedule::timesOverlap(
                $newStart,
                $newEnd,
                ShiftSchedule::formatTime($existingShift->start_time),
                ShiftSchedule::formatTime($existingShift->end_time),
            )) {
                throw new DomainException(
                    message: 'Shift assignment overlaps an existing assignment for this employee.',
                    errorCode: 'SHIFT_ASSIGNMENT_OVERLAP',
                    status: 409,
                );
            }
        }
    }

    /**
     * @return list<int>|null
     */
    private function normalizeWeekdays(mixed $weekdays): ?array
    {
        if ($weekdays === null) {
            return null;
        }

        if (! is_array($weekdays)) {
            throw new DomainException(
                message: 'Assignment weekdays must be a list of weekday numbers.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }

        if ($weekdays === []) {
            throw new DomainException(
                message: 'Assignment weekdays cannot be empty.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }

        return ShiftSchedule::weekdaysOrAll($weekdays);
    }

    private function assertDateOrder(string $startDate, ?string $endDate): void
    {
        if ($endDate !== null && $endDate < $startDate) {
            throw new DomainException(
                message: 'Assignment end date must be on or after start date.',
                errorCode: 'SHIFT_INVALID_TIME_RANGE',
                status: 422,
            );
        }
    }

    private function assertEmployeeInCompany(int $employeeId, int $companyId): Employee
    {
        $employee = Employee::query()->find($employeeId);

        if ($employee === null || $employee->company_id !== $companyId) {
            throw new DomainException(
                message: 'Employee does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }

        return $employee;
    }

    private function assertShiftInCompany(int $shiftId, int $companyId): Shift
    {
        $shift = Shift::query()->find($shiftId);

        if ($shift === null || $shift->company_id !== $companyId) {
            throw new DomainException(
                message: 'Shift does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }

        return $shift;
    }

    private function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }
    }
}
