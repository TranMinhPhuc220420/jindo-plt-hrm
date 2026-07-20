<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceCompensationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_salary') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['present', 'array'],
            'items.*.code' => ['required', 'string', 'max:64'],
            'items.*.name' => ['required', 'string', 'max:150'],
            'items.*.amount' => ['required', 'numeric'],
            'items.*.is_taxable' => ['sometimes', 'boolean'],
            'items.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
