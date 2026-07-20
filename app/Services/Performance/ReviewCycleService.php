<?php

namespace App\Services\Performance;

use App\Events\ReviewCycleFinalized;
use App\Events\ReviewCycleStarted;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\PerformanceCycleParticipant;
use App\Models\PerformanceReviewCycle;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PerformanceReviewCycle::query()
            ->withCount('participants')
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): PerformanceReviewCycle
    {
        $cycle = PerformanceReviewCycle::query()
            ->withCount('participants')
            ->with('participants')
            ->where('company_id', $this->companyContext->id())
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

            return $cycle->fresh(['participants']);
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

        $cycle->status = 'active';
        $cycle->started_at = now();
        $cycle->save();

        $this->audit->write(action: 'performance.cycle_started', subject: $cycle);

        ReviewCycleStarted::dispatch($cycle);

        return $cycle->fresh(['participants']);
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

        return $cycle->fresh(['participants']);
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
            ->all();

        return array_values(array_map('intval', $valid));
    }
}
