<?php

namespace App\Http\Resources;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReportExport
 */
class ReportExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report' => $this->report,
            'format' => $this->format,
            'filters' => $this->filters ?? [],
            'status' => $this->status,
            'path' => $this->path,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
