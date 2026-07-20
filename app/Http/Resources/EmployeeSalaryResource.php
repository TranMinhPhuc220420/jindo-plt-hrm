<?php

namespace App\Http\Resources;

use App\Models\EmployeeSalary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeSalary
 */
class EmployeeSalaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'employee_code' => $this->whenLoaded('employee', fn () => $this->employee?->code),
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name),
            'amount' => (string) $this->amount,
            'currency' => $this->currency,
            'strategy' => $this->strategy,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
