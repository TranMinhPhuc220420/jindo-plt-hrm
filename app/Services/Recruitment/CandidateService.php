<?php

namespace App\Services\Recruitment;

use App\Events\CandidateStageChanged;
use App\Exceptions\DomainException;
use App\Models\Candidate;
use App\Models\JobOpening;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Pagination\LengthAwarePaginator;

class CandidateService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{job_opening_id?: int, stage?: string, search?: string}  $filters
     * @return LengthAwarePaginator<int, Candidate>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Candidate::query()
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('id');

        if (! empty($filters['job_opening_id'])) {
            $query->where('job_opening_id', $filters['job_opening_id']);
        }

        if (! empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('full_name', 'like', $search)->orWhere('email', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Candidate
    {
        $candidate = Candidate::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($candidate === null) {
            throw new DomainException(
                message: 'Candidate not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Candidate
    {
        $companyId = $this->companyContext->id();

        $opening = JobOpening::query()
            ->where('company_id', $companyId)
            ->find($data['job_opening_id']);

        if (! $opening instanceof JobOpening) {
            throw new DomainException(
                message: 'Job opening is outside the current company scope.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 404,
            );
        }

        if ($opening->status === 'closed') {
            throw new DomainException(
                message: 'Cannot add candidates to a closed job opening.',
                errorCode: 'JOB_OPENING_CLOSED',
                status: 422,
            );
        }

        $data['company_id'] = $companyId;
        $data['stage'] = $data['stage'] ?? 'applied';

        $candidate = Candidate::query()->create($data);

        $this->audit->write(
            action: 'candidate.created',
            subject: $candidate,
            payload: ['stage' => $candidate->stage, 'job_opening_id' => $candidate->job_opening_id],
        );

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Candidate $candidate, array $data): Candidate
    {
        $this->assertCompanyScope($candidate->company_id);

        unset($data['company_id'], $data['stage'], $data['employee_id'], $data['job_opening_id']);

        $candidate->fill($data);
        $candidate->save();

        $this->audit->write(
            action: 'candidate.updated',
            subject: $candidate,
            payload: ['full_name' => $candidate->full_name],
        );

        return $candidate->fresh();
    }

    public function changeStage(Candidate $candidate, string $to): Candidate
    {
        $this->assertCompanyScope($candidate->company_id);

        $from = $candidate->stage;

        if (! CandidateStageTransitions::canTransition($from, $to)) {
            throw new DomainException(
                message: "Cannot move candidate from {$from} to {$to}.",
                errorCode: 'CANDIDATE_INVALID_STAGE',
                status: 422,
            );
        }

        $candidate->stage = $to;
        $candidate->save();

        $this->audit->write(
            action: 'candidate.stage_changed',
            subject: $candidate,
            payload: ['from' => $from, 'to' => $to],
        );

        CandidateStageChanged::dispatch($candidate, $from, $to);

        return $candidate->fresh();
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
}
