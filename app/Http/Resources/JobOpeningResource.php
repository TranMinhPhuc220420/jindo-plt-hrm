<?php

namespace App\Http\Resources;

use App\Models\JobOpening;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobOpening
 */
class JobOpeningResource extends JsonResource
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
            'title' => $this->title,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'description' => $this->description,
            'headcount' => $this->headcount,
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toDateString(),
            'closed_at' => $this->closed_at?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
