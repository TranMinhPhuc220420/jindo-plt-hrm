<?php

namespace App\Http\Resources;

use App\Models\OnboardingTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OnboardingTask
 */
class OnboardingTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'onboarding_case_id' => $this->onboarding_case_id,
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'mandatory' => $this->mandatory,
            'assignee_type' => $this->assignee_type,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completed_by' => $this->completed_by,
        ];
    }
}
