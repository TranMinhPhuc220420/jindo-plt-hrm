<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompensationComponentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'code' => $this->code,
            'name' => $this->name,
            'amount' => (string) $this->amount,
            'is_taxable' => $this->is_taxable,
            'is_active' => $this->is_active,
        ];
    }
}
