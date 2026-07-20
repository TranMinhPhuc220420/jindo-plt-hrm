<?php

namespace App\Services\Recruitment;

use App\Exceptions\DomainException;
use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\Interview;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Database\Eloquent\Collection;

class InterviewService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    public function find(int $id): Interview
    {
        $interview = Interview::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($interview === null) {
            throw new DomainException(
                message: 'Interview not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $interview;
    }

    /**
     * @return Collection<int, Interview>
     */
    public function listForCandidate(Candidate $candidate): Collection
    {
        $this->assertCompanyScope($candidate->company_id);

        return $candidate->interviews()->orderByDesc('scheduled_at')->orderByDesc('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(Candidate $candidate, array $data): Interview
    {
        $this->assertCompanyScope($candidate->company_id);

        $interview = $candidate->interviews()->create([
            ...$data,
            'company_id' => $candidate->company_id,
            'status' => $data['status'] ?? 'scheduled',
        ]);

        $this->audit->write(
            action: 'interview.scheduled',
            subject: $interview,
            payload: ['candidate_id' => $candidate->id],
        );

        return $interview;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitEvaluation(Interview $interview, array $data, User $actor): CandidateEvaluation
    {
        $this->assertCompanyScope($interview->company_id);

        $evaluation = CandidateEvaluation::query()->create([
            'company_id' => $interview->company_id,
            'interview_id' => $interview->id,
            'candidate_id' => $interview->candidate_id,
            'evaluator_id' => $actor->id,
            'rating' => $data['rating'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
            'comments' => $data['comments'] ?? null,
        ]);

        $interview->status = 'completed';
        $interview->save();

        $this->audit->write(
            action: 'interview.evaluated',
            subject: $interview,
            payload: ['evaluation_id' => $evaluation->id, 'recommendation' => $evaluation->recommendation],
        );

        return $evaluation;
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
