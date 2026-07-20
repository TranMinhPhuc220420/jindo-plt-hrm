<?php

namespace App\Http\Resources;

use App\Models\OnboardingCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OnboardingCase
 */
class OnboardingCaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tasks = $this->whenLoaded('tasks');

        $data = [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'offer_id' => $this->offer_id,
            'candidate_id' => $this->candidate_id,
            'onboarding_template_id' => $this->onboarding_template_id,
            'status' => $this->status,
            'probation_ends_on' => $this->probation_ends_on?->toDateString(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('tasks')) {
            $total = $this->tasks->count();
            $done = $this->tasks->where('status', 'done')->count();
            $mandatoryRemaining = $this->tasks
                ->where('mandatory', true)
                ->where('status', '!=', 'done')
                ->count();

            $data['progress'] = [
                'done' => $done,
                'total' => $total,
                'mandatory_remaining' => $mandatoryRemaining,
            ];

            $data['tasks'] = OnboardingTaskResource::collection($this->tasks)->resolve();
        }

        return $data;
    }
}
