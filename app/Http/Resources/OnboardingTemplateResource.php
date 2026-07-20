<?php

namespace App\Http\Resources;

use App\Models\OnboardingTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OnboardingTemplate
 */
class OnboardingTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('items')) {
            $data['items'] = OnboardingTemplateItemResource::collection(
                $this->items->sortBy('sort_order')->values()
            )->resolve();
        }

        return $data;
    }
}
