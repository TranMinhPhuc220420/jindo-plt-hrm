<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmployeeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_change_employee_status') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Employee::STATUSES)],
            'reason' => ['nullable', 'string', 'max:500'],
            'effective_on' => ['nullable', 'date'],
            'confirm_asset_return' => ['sometimes', 'boolean'],
        ];
    }
}
