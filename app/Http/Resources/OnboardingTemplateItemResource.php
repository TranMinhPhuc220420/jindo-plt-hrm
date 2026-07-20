<?php

namespace App\Http\Resources;

use App\Models\OnboardingTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OnboardingTemplateItem
 */
class OnboardingTemplateItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'onboarding_template_id' => $this->onboarding_template_id,
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'mandatory' => $this->mandatory,
            'assignee_type' => $this->assignee_type,
            'sort_order' => $this->sort_order,
        ];
    }
}
