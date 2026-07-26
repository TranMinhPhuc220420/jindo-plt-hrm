<?php

namespace App\Services\Performance;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\PerformanceCycleParticipant;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReviewCycle;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Pagination\LengthAwarePaginator;

class GoalService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{employee_id?: int, review_cycle_id?: int, status?: string}  $filters
     * @return LengthAwarePaginator<int, PerformanceGoal>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PerformanceGoal::query()
            ->with('employee')
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }
        if (! empty($filters['review_cycle_id'])) {
            $query->where('review_cycle_id', (int) $filters['review_cycle_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): PerformanceGoal
    {
        $goal = PerformanceGoal::query()
            ->with('employee')
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($goal === null) {
            throw new DomainException(
                message: 'Goal not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $goal;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): PerformanceGoal
    {
        $companyId = $this->companyContext->id();
        $employee = $this->requireEmployee($companyId, (int) $data['employee_id']);

        $reviewCycleId = isset($data['review_cycle_id']) ? (int) $data['review_cycle_id'] : null;
        if ($reviewCycleId) {
            $cycle = $this->requireCycle($companyId, $reviewCycleId);
            $this->assertParticipant($cycle, $employee);
        }

        $goal = PerformanceGoal::query()->create([
            'company_id' => $companyId,
            'review_cycle_id' => $reviewCycleId,
            'employee_id' => $employee->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'goal',
            'metric' => $data['metric'] ?? null,
            'target' => $data['target'] ?? null,
            'weight' => $data['weight'] ?? null,
            'progress' => (int) ($data['progress'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'created_by' => $actor->id,
        ]);

        $this->audit->write(action: 'performance.goal_created', subject: $goal);

        return $goal->fresh('employee');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PerformanceGoal $goal, array $data): PerformanceGoal
    {
        $goal->fill([
            'title' => $data['title'] ?? $goal->title,
            'description' => $data['description'] ?? $goal->description,
            'type' => $data['type'] ?? $goal->type,
            'metric' => $data['metric'] ?? $goal->metric,
            'target' => $data['target'] ?? $goal->target,
            'weight' => $data['weight'] ?? $goal->weight,
            'progress' => $data['progress'] ?? $goal->progress,
            'status' => $data['status'] ?? $goal->status,
        ]);
        $goal->save();

        $this->audit->write(action: 'performance.goal_updated', subject: $goal);

        return $goal->fresh('employee');
    }

    private function requireEmployee(int $companyId, int $employeeId): Employee
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

    private function requireCycle(int $companyId, int $id): PerformanceReviewCycle
    {
        $cycle = PerformanceReviewCycle::query()
            ->where('company_id', $companyId)
            ->find($id);

        if ($cycle === null) {
            throw new DomainException(
                message: 'Review cycle not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $cycle;
    }

    private function assertParticipant(PerformanceReviewCycle $cycle, Employee $employee): void
    {
        $isParticipant = PerformanceCycleParticipant::query()
            ->where('review_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->exists();

        if (! $isParticipant) {
            throw new DomainException(
                message: 'Employee is not a participant in this review cycle.',
                errorCode: 'PERFORMANCE_FORBIDDEN_SCOPE',
                status: 403,
            );
        }
    }
}
