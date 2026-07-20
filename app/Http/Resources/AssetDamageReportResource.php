<?php

namespace App\Http\Resources;

use App\Models\AssetDamageReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssetDamageReport
 */
class AssetDamageReportResource extends JsonResource
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
            'reported_at' => $this->reported_at?->toDateString(),
            'reported_by' => $this->reported_by,
            'document_ids' => $this->document_ids,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
