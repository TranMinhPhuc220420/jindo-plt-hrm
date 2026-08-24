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

    /**
     * @return array{status: string, reason?: string|null, effective_on?: string|null, confirm_asset_return?: bool}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'status' => (string) $validated['status'],
            'reason' => array_key_exists('reason', $validated)
                ? ($validated['reason'] !== null ? (string) $validated['reason'] : null)
                : null,
            'effective_on' => array_key_exists('effective_on', $validated)
                ? ($validated['effective_on'] !== null ? (string) $validated['effective_on'] : null)
                : null,
            'confirm_asset_return' => $this->boolean('confirm_asset_return'),
        ];
    }
}
