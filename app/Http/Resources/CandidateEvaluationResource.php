<?php

namespace App\Http\Resources;

use App\Models\CandidateEvaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CandidateEvaluation
 */
class CandidateEvaluationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'interview_id' => $this->interview_id,
            'candidate_id' => $this->candidate_id,
            'evaluator_id' => $this->evaluator_id,
            'rating' => $this->rating,
            'recommendation' => $this->recommendation,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
