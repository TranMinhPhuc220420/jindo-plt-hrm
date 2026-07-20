<?php

namespace App\Http\Resources;

use App\Models\AssetMaintenance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssetMaintenance
 */
class AssetMaintenanceResource extends JsonResource
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
            'description' => $this->description,
            'status' => $this->status,
            'cost' => $this->cost !== null ? (string) $this->cost : null,
            'scheduled_at' => $this->scheduled_at?->toDateString(),
            'completed_at' => $this->completed_at?->toDateString(),
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
