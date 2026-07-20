<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'legal_name' => $this->legal_name,
            'tax_code' => $this->tax_code,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ];
    }
}
