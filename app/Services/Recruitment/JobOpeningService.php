<?php

namespace App\Services\Recruitment;

use App\Exceptions\DomainException;
use App\Models\JobOpening;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

class JobOpeningService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{status?: string, search?: string}  $filters
     * @return LengthAwarePaginator<int, JobOpening>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = JobOpening::query()
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', $search)->orWhere('code', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): JobOpening
    {
        $opening = JobOpening::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($opening === null) {
            throw new DomainException(
                message: 'Job opening not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $opening;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JobOpening
    {
        $data['company_id'] = $this->companyContext->id();
        $data['status'] = $data['status'] ?? 'open';
        $data['opened_at'] = $data['opened_at'] ?? now()->toDateString();

        try {
            $opening = JobOpening::query()->create($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new DomainException(
                    message: 'Job opening code already exists for this company.',
                    errorCode: 'JOB_OPENING_CODE_DUPLICATE',
                    status: 422,
                );
            }

            throw $e;
        }

        $this->audit->write(
            action: 'job_opening.created',
            subject: $opening,
            payload: ['title' => $opening->title],
        );

        return $opening;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JobOpening $opening, array $data): JobOpening
    {
        $this->assertCompanyScope($opening->company_id);

        unset($data['company_id']);

        $opening->fill($data);
        $opening->save();

        $this->audit->write(
            action: 'job_opening.updated',
            subject: $opening,
            payload: ['title' => $opening->title],
        );

        return $opening->fresh();
    }

    public function close(JobOpening $opening): JobOpening
    {
        $this->assertCompanyScope($opening->company_id);

        $opening->status = 'closed';
        $opening->closed_at = now()->toDateString();
        $opening->save();

        $this->audit->write(
            action: 'job_opening.closed',
            subject: $opening,
            payload: [],
        );

        return $opening->fresh();
    }

    private function assertCompanyScope(int $companyId): void
    {
        if ($companyId !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Resource is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return str_contains(strtolower($e->getMessage()), 'unique')
            || (string) $e->getCode() === '23000';
    }
}
