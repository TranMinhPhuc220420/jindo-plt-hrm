<?php

namespace App\Services\Performance;

use App\Exceptions\DomainException;
use App\Models\PerformancePromotionSuggestion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PromotionSuggestionService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{status?: string, employee_id?: int}  $filters
     * @return LengthAwarePaginator<int, PerformancePromotionSuggestion>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PerformancePromotionSuggestion::query()
            ->with(['employee', 'reviewCycle'])
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): PerformancePromotionSuggestion
    {
        $suggestion = PerformancePromotionSuggestion::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($suggestion === null) {
            throw new DomainException(
                message: 'Promotion suggestion not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $suggestion;
    }

    /**
     * Acknowledge a suggestion. Advisory only — never changes the employee.
     */
    public function acknowledge(PerformancePromotionSuggestion $suggestion, User $actor): PerformancePromotionSuggestion
    {
        if ($suggestion->status === 'acknowledged') {
            return $suggestion->fresh(['employee', 'reviewCycle']);
        }

        $suggestion->status = 'acknowledged';
        $suggestion->acknowledged_by = $actor->id;
        $suggestion->acknowledged_at = now();
        $suggestion->save();

        $this->audit->write(action: 'performance.promotion_acknowledged', subject: $suggestion);

        return $suggestion->fresh(['employee', 'reviewCycle']);
    }
}
