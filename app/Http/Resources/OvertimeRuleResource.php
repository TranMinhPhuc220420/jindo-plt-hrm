<?php

namespace App\Http\Resources;

use App\Models\OvertimeRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OvertimeRule
 */
class OvertimeRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'applies_after_minutes' => $this->applies_after_minutes,
            'allow_before_shift' => $this->allow_before_shift,
            'night_ot_enabled' => $this->night_ot_enabled,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
