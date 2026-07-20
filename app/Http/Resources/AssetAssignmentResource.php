<?php

namespace App\Http\Resources;

use App\Models\AssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssetAssignment
 */
class AssetAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'asset_id' => $this->asset_id,
            'employee_id' => $this->employee_id,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at?->toDateString(),
            'assigned_by' => $this->assigned_by,
            'returned_at' => $this->returned_at?->toDateString(),
            'return_condition' => $this->return_condition,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
