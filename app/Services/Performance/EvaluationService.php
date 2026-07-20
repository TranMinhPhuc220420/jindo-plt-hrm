<?php

namespace App\Services\Performance;

use App\Events\EvaluationSubmitted;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\PerformanceCycleParticipant;
use App\Models\PerformanceEvaluation;
use App\Models\PerformancePromotionSuggestion;
use App\Models\PerformanceReviewCycle;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    public const PROMOTION_THRESHOLD = 4.5;

    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{review_cycle_id?: int, employee_id?: int}  $filters
     * @return LengthAwarePaginator<int, PerformanceEvaluation>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PerformanceEvaluation::query()
            ->with(['employee', 'reviewCycle', 'evaluator'])
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['review_cycle_id'])) {
            $query->where('review_cycle_id', (int) $filters['review_cycle_id']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): PerformanceEvaluation
    {
        $evaluation = PerformanceEvaluation::query()
            ->with(['employee', 'reviewCycle', 'evaluator'])
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($evaluation === null) {
            throw new DomainException(
                message: 'Evaluation not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $evaluation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(array $data, User $actor): PerformanceEvaluation
    {
        $companyId = $this->companyContext->id();
        $cycle = $this->requireCycle($companyId, (int) $data['review_cycle_id']);

        if ($cycle->status !== 'active') {
            throw new DomainException(
                message: 'The review cycle is not open for evaluations.',
                errorCode: 'REVIEW_CYCLE_NOT_OPEN',
                status: 422,
            );
        }

        $employee = $this->requireEmployee($companyId, (int) $data['employee_id']);
        $this->assertParticipant($cycle, $employee);
        $this->assertScope($actor, $employee);

        $exists = PerformanceEvaluation::query()
            ->where('review_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->exists();

        if ($exists) {
            throw new DomainException(
                message: 'An evaluation already exists for this employee in this cycle.',
                errorCode: 'EVALUATION_DUPLICATE',
                status: 422,
            );
        }

        $overall = (float) $data['overall_score'];

        return DB::transaction(function () use ($companyId, $cycle, $employee, $actor, $data, $overall): PerformanceEvaluation {
            $evaluation = PerformanceEvaluation::query()->create([
                'company_id' => $companyId,
                'review_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
                'evaluator_id' => $actor->id,
                'overall_score' => $overall,
                'summary' => $data['summary'] ?? null,
                'ratings' => $data['ratings'] ?? null,
                'submitted_at' => now(),
            ]);

            $this->audit->write(
                action: 'performance.evaluation_submitted',
                subject: $evaluation,
                payload: ['overall_score' => $overall],
            );

            if ($overall >= self::PROMOTION_THRESHOLD) {
                $this->upsertPromotionSuggestion($companyId, $cycle, $employee->id, $evaluation->id, $overall);
            }

            EvaluationSubmitted::dispatch($evaluation);

            return $evaluation->fresh(['employee', 'reviewCycle', 'evaluator']);
        });
    }

    private function upsertPromotionSuggestion(
        int $companyId,
        PerformanceReviewCycle $cycle,
        int $employeeId,
        int $evaluationId,
        float $overall,
    ): void {
        $suggestion = PerformancePromotionSuggestion::query()->updateOrCreate(
            [
                'employee_id' => $employeeId,
                'review_cycle_id' => $cycle->id,
            ],
            [
                'company_id' => $companyId,
                'evaluation_id' => $evaluationId,
                'overall_score' => $overall,
                'status' => 'suggested',
                'suggested_at' => now(),
            ],
        );

        $this->audit->write(
            action: 'performance.promotion_suggested',
            subject: $suggestion,
            payload: ['overall_score' => $overall],
        );
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

    /**
     * HR (cycle managers) can evaluate any participant; a manager can only
     * evaluate direct reports (employee.manager_id === manager's employee id).
     */
    private function assertScope(User $actor, Employee $employee): void
    {
        if ($actor->can('can_manage_review_cycles')) {
            return;
        }

        $actorEmployeeId = $actor->employee?->id;

        if ($actorEmployeeId !== null && $employee->manager_id === $actorEmployeeId) {
            return;
        }

        throw new DomainException(
            message: 'You may only evaluate employees within your scope.',
            errorCode: 'PERFORMANCE_FORBIDDEN_SCOPE',
            status: 403,
        );
    }
}
