<?php

namespace App\Services\Performance;

use App\Events\ReviewCycleFinalized;
use App\Events\ReviewCycleStarted;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\PerformanceCycleParticipant;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReviewCycle;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewCycleService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{status?: string}  $filters
     * @return LengthAwarePaginator<int, PerformanceReviewCycle>
     */
    public function list(User $actor, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PerformanceReviewCycle::query()
            ->withCount([
                'participants',
                'evaluations',
                'goals as goals_active_count' => fn ($q) => $q->where('status', 'active'),
                'goals as goals_completed_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        $this->applyVisibilityScope($query, $actor);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id, ?User $actor = null): PerformanceReviewCycle
    {
        $cycle = PerformanceReviewCycle::query()
            ->withCount([
                'participants',
                'evaluations',
                'goals as goals_active_count' => fn ($q) => $q->where('status', 'active'),
                'goals as goals_completed_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->with(['participants.employee'])
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($cycle === null) {
            throw new DomainException(
                message: 'Review cycle not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        if ($actor !== null) {
            $this->assertCanView($actor, $cycle);
        }

        return $cycle;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): PerformanceReviewCycle
    {
        $companyId = $this->companyContext->id();

        $participantIds = $this->resolveParticipantIds($companyId, $data['participant_employee_ids'] ?? []);

        return DB::transaction(function () use ($companyId, $data, $actor, $participantIds): PerformanceReviewCycle {
            $cycle = PerformanceReviewCycle::query()->create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'framework' => $data['framework'] ?? 'goal',
                'status' => 'draft',
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'participant_employee_ids' => $participantIds,
                'created_by' => $actor->id,
            ]);

            foreach ($participantIds as $employeeId) {
                PerformanceCycleParticipant::query()->create([
                    'company_id' => $companyId,
                    'review_cycle_id' => $cycle->id,
                    'employee_id' => $employeeId,
                ]);
            }

            $this->audit->write(
                action: 'performance.cycle_created',
                subject: $cycle,
                payload: ['participants' => count($participantIds)],
            );

            return $this->find($cycle->id, $actor);
        });
    }

    /**
     * Replace participants for a draft cycle.
     * Goals belonging to removed participants are deleted with them.
     *
     * @param  list<int|string>  $employeeIds
     */
    public function syncParticipants(PerformanceReviewCycle $cycle, array $employeeIds): PerformanceReviewCycle
    {
        if ($cycle->status !== 'draft') {
            throw new DomainException(
                message: 'Participants can only be changed on draft cycles.',
                errorCode: 'PERFORMANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $companyId = $this->companyContext->id();
        $participantIds = $this->resolveParticipantIds($companyId, $employeeIds);
        $previousIds = PerformanceCycleParticipant::query()
            ->where('review_cycle_id', $cycle->id)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $removedIds = array_values(array_diff($previousIds, $participantIds));

        return DB::transaction(function () use ($cycle, $companyId, $participantIds, $removedIds): PerformanceReviewCycle {
            if ($removedIds !== []) {
                PerformanceGoal::query()
                    ->where('review_cycle_id', $cycle->id)
                    ->whereIn('employee_id', $removedIds)
                    ->delete();
            }

            PerformanceCycleParticipant::query()
                ->where('review_cycle_id', $cycle->id)
                ->delete();

            foreach ($participantIds as $employeeId) {
                PerformanceCycleParticipant::query()->create([
                    'company_id' => $companyId,
                    'review_cycle_id' => $cycle->id,
                    'employee_id' => $employeeId,
                ]);
            }

            $cycle->participant_employee_ids = $participantIds;
            $cycle->save();

            $this->audit->write(
                action: 'performance.cycle_participants_synced',
                subject: $cycle,
                payload: [
                    'participants' => count($participantIds),
                    'removed' => $removedIds,
                ],
            );

            return $this->find($cycle->id);
        });
    }

    public function start(PerformanceReviewCycle $cycle): PerformanceReviewCycle
    {
        if ($cycle->status !== 'draft') {
            throw new DomainException(
                message: 'Only draft cycles can be started.',
                errorCode: 'PERFORMANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $participantCount = $cycle->participants_count
            ?? $cycle->participants()->count();

        if ($participantCount < 1) {
            throw new DomainException(
                message: 'Add at least one participant before starting the review cycle.',
                errorCode: 'REVIEW_CYCLE_NO_PARTICIPANTS',
                status: 422,
            );
        }

        $cycle->status = 'active';
        $cycle->started_at = now();
        $cycle->save();

        $this->audit->write(action: 'performance.cycle_started', subject: $cycle);

        ReviewCycleStarted::dispatch($cycle);

        return $this->find($cycle->id);
    }

    public function finalize(PerformanceReviewCycle $cycle): PerformanceReviewCycle
    {
        if ($cycle->status !== 'active') {
            throw new DomainException(
                message: 'Only active cycles can be finalized.',
                errorCode: 'PERFORMANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $cycle->status = 'finalized';
        $cycle->finalized_at = now();
        $cycle->save();

        $this->audit->write(action: 'performance.cycle_finalized', subject: $cycle);

        ReviewCycleFinalized::dispatch($cycle);

        return $this->find($cycle->id);
    }

    public function delete(PerformanceReviewCycle $cycle): void
    {
        if ($cycle->status !== 'draft') {
            throw new DomainException(
                message: 'Only draft cycles can be deleted.',
                errorCode: 'PERFORMANCE_INVALID_TRANSITION',
                status: 422,
            );
        }

        DB::transaction(function () use ($cycle): void {
            PerformanceGoal::query()
                ->where('review_cycle_id', $cycle->id)
                ->delete();

            $this->audit->write(
                action: 'performance.cycle_deleted',
                subject: $cycle,
                payload: ['name' => $cycle->name],
            );

            $cycle->delete();
        });
    }

    public function assertCanView(User $actor, PerformanceReviewCycle $cycle): void
    {
        if ($this->canView($actor, $cycle)) {
            return;
        }

        throw new DomainException(
            message: 'You are not a participant in this review cycle.',
            errorCode: 'PERFORMANCE_FORBIDDEN_SCOPE',
            status: 403,
        );
    }

    public function canView(User $actor, PerformanceReviewCycle $cycle): bool
    {
        if ($actor->can('can_manage_review_cycles')) {
            return true;
        }

        $actorEmployeeId = $actor->employee?->id;

        if ($actorEmployeeId === null) {
            return false;
        }

        $isParticipant = PerformanceCycleParticipant::query()
            ->where('review_cycle_id', $cycle->id)
            ->where('employee_id', $actorEmployeeId)
            ->exists();

        if ($isParticipant) {
            return true;
        }

        // Managers who evaluate direct reports may open cycles that include those reports.
        if ($actor->can('can_evaluate_employee')) {
            return PerformanceCycleParticipant::query()
                ->where('review_cycle_id', $cycle->id)
                ->whereIn(
                    'employee_id',
                    Employee::query()
                        ->where('company_id', $cycle->company_id)
                        ->where('manager_id', $actorEmployeeId)
                        ->select('id'),
                )
                ->exists();
        }

        return false;
    }

    /**
     * @param  Builder<PerformanceReviewCycle>  $query
     */
    private function applyVisibilityScope(Builder $query, User $actor): void
    {
        if ($actor->can('can_manage_review_cycles')) {
            return;
        }

        $actorEmployeeId = $actor->employee?->id;

        if ($actorEmployeeId === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $outer) use ($actor, $actorEmployeeId): void {
            $outer->whereHas(
                'participants',
                fn (Builder $p) => $p->where('employee_id', $actorEmployeeId),
            );

            if ($actor->can('can_evaluate_employee')) {
                $outer->orWhereHas(
                    'participants',
                    fn (Builder $p) => $p->whereIn(
                        'employee_id',
                        Employee::query()
                            ->where('manager_id', $actorEmployeeId)
                            ->select('id'),
                    ),
                );
            }
        });
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return list<int>
     */
    private function resolveParticipantIds(int $companyId, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $valid = Employee::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $invalid = array_values(array_diff($ids, $valid));

        if ($invalid !== []) {
            throw new DomainException(
                message: 'One or more participant employee ids do not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 422,
                errors: ['participant_employee_ids' => array_map('strval', $invalid)],
            );
        }

        return array_values($valid);
    }
}
