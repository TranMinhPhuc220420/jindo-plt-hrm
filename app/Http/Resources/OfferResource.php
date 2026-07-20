<?php

namespace App\Http\Resources;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'candidate_id' => $this->candidate_id,
            'title' => $this->title,
            'salary_amount' => $this->salary_amount !== null ? (string) $this->salary_amount : null,
            'currency' => $this->currency,
            'start_date' => $this->start_date?->toDateString(),
            'probation_ends_on' => $this->probation_ends_on?->toDateString(),
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'notes' => $this->notes,
            'onboarding_case_id' => $this->whenHas('onboarding_case_id'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
