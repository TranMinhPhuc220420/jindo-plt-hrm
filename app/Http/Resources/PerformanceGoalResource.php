<?php

namespace App\Http\Resources;

use App\Models\PerformanceGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PerformanceGoal
 */
class PerformanceGoalResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->full_name ?? $this->employee?->code),
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'metric' => $this->metric,
            'target' => $this->target,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'progress' => (int) $this->progress,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
