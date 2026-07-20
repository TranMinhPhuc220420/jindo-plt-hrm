<?php

namespace App\Services\Recruitment;

use App\Events\OfferAccepted;
use App\Events\OfferSent;
use App\Exceptions\DomainException;
use App\Models\Candidate;
use App\Models\Offer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Employee\EmployeeService;
use App\Services\Onboarding\OnboardingService;
use App\Services\Organization\CompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
        private readonly EmployeeService $employees,
        private readonly OnboardingService $onboarding,
    ) {}

    public function find(int $id): Offer
    {
        $offer = Offer::query()
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($offer === null) {
            throw new DomainException(
                message: 'Offer not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $offer;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function listForCandidate(Candidate $candidate): Collection
    {
        $this->assertCompanyScope($candidate->company_id);

        return $candidate->offers()->orderByDesc('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Candidate $candidate, array $data): Offer
    {
        $this->assertCompanyScope($candidate->company_id);

        $offer = $candidate->offers()->create([
            ...$data,
            'company_id' => $candidate->company_id,
            'status' => 'draft',
        ]);

        $this->audit->write(
            action: 'offer.created',
            subject: $offer,
            payload: ['candidate_id' => $candidate->id],
        );

        return $offer;
    }

    public function send(Offer $offer, User $actor): Offer
    {
        $this->assertCompanyScope($offer->company_id);

        if ($offer->status !== 'draft') {
            throw new DomainException(
                message: 'Only draft offers can be sent.',
                errorCode: 'OFFER_NOT_PENDING',
                status: 422,
            );
        }

        $offer->status = 'sent';
        $offer->sent_at = now();
        $offer->sent_by = max(0, $actor->id);
        $offer->save();

        $this->audit->write(
            action: 'offer.sent',
            subject: $offer,
            payload: ['candidate_id' => $offer->candidate_id],
        );

        OfferSent::dispatch($offer);

        return $offer->fresh();
    }

    /**
     * @param  array{accepted_at?: string}  $data
     */
    public function accept(Offer $offer, array $data = []): Offer
    {
        $this->assertCompanyScope($offer->company_id);

        if ($offer->status !== 'sent') {
            throw new DomainException(
                message: 'Only sent offers can be accepted.',
                errorCode: 'OFFER_NOT_PENDING',
                status: 422,
            );
        }

        return DB::transaction(function () use ($offer, $data): Offer {
            $candidate = Candidate::query()
                ->where('company_id', $offer->company_id)
                ->findOrFail($offer->candidate_id);

            [$firstName, $lastName] = $this->splitName($candidate->full_name);

            $employee = $this->employees->create([
                'code' => 'CAND-'.$candidate->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'status' => 'probation',
            ]);

            $case = $this->onboarding->startFromOffer($offer, $employee);

            $candidate->stage = 'hired';
            $candidate->employee_id = max(0, $employee->id);
            $candidate->save();

            $offer->status = 'accepted';
            $offer->accepted_at = ! empty($data['accepted_at'])
                ? CarbonImmutable::parse($data['accepted_at'])
                : CarbonImmutable::now();
            $offer->save();

            $this->audit->write(
                action: 'offer.accepted',
                subject: $offer,
                payload: [
                    'candidate_id' => $candidate->id,
                    'employee_id' => $employee->id,
                    'onboarding_case_id' => $case->id,
                ],
            );

            OfferAccepted::dispatch($offer);

            $offer->setAttribute('onboarding_case_id', $case->id);

            return $offer;
        });
    }

    public function reject(Offer $offer): Offer
    {
        $this->assertCompanyScope($offer->company_id);

        if (! in_array($offer->status, ['draft', 'sent'], true)) {
            throw new DomainException(
                message: 'Only draft or sent offers can be rejected.',
                errorCode: 'OFFER_NOT_PENDING',
                status: 422,
            );
        }

        $offer->status = 'rejected';
        $offer->rejected_at = now();
        $offer->save();

        $this->audit->write(
            action: 'offer.rejected',
            subject: $offer,
            payload: ['candidate_id' => $offer->candidate_id],
        );

        return $offer->fresh();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        if ($parts === []) {
            return ['Candidate', 'Unknown'];
        }

        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }

        $first = array_shift($parts);

        return [$first, implode(' ', $parts)];
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
