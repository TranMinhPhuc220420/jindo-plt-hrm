<?php

namespace App\Http\Resources;

use App\Models\PerformanceReviewCycle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PerformanceReviewCycle
 */
class PerformanceReviewCycleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'framework' => $this->framework,
            'status' => $this->status,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'participant_employee_ids' => $this->participant_employee_ids ?? [],
            'participants_count' => (int) ($this->participants_count ?? 0),
            'evaluations_count' => (int) ($this->evaluations_count ?? 0),
            'goals_active_count' => (int) ($this->goals_active_count ?? 0),
            'goals_completed_count' => (int) ($this->goals_completed_count ?? 0),
            'participants' => $this->whenLoaded('participants', function () {
                return $this->participants->map(fn ($row) => [
                    'employee_id' => $row->employee_id,
                    'employee_name' => $row->employee?->full_name ?? $row->employee?->code,
                    'employee_code' => $row->employee?->code,
                ])->values()->all();
            }),
            'started_at' => $this->started_at?->toIso8601String(),
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
