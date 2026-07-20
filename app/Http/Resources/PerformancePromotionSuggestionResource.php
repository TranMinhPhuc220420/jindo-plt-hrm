<?php

namespace App\Http\Resources;

use App\Models\PerformancePromotionSuggestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PerformancePromotionSuggestion
 */
class PerformancePromotionSuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name ?? $this->employee?->code),
            'review_cycle_id' => $this->review_cycle_id,
            'evaluation_id' => $this->evaluation_id,
            'overall_score' => (float) $this->overall_score,
            'status' => $this->status,
            'note' => $this->note,
            'suggested_at' => $this->suggested_at?->toIso8601String(),
            'acknowledged_by' => $this->acknowledged_by,
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
        ];
    }
}
