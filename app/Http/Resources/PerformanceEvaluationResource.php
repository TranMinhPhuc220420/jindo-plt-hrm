<?php

namespace App\Http\Resources;

use App\Models\PerformanceEvaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PerformanceEvaluation
 */
class PerformanceEvaluationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'review_cycle_id' => $this->review_cycle_id,
            'review_cycle_name' => $this->whenLoaded('reviewCycle', fn () => $this->reviewCycle?->name),
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name ?? $this->employee?->code),
            'evaluator_id' => $this->evaluator_id,
            'overall_score' => (float) $this->overall_score,
            'summary' => $this->summary,
            'ratings' => $this->ratings ?? [],
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
