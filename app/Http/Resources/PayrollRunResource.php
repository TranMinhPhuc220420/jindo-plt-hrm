<?php

namespace App\Http\Resources;

use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PayrollRun
 */
class PayrollRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'run_type' => $this->run_type,
            'status' => $this->status,
            'employee_count' => $this->employee_count,
            'total_gross' => (string) $this->total_gross,
            'total_net' => (string) $this->total_net,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
