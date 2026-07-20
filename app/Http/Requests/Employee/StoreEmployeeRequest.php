<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_create_employee') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'team_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'manager_id' => ['nullable', 'integer'],
            'supervisor_id' => ['nullable', 'integer'],
            'hr_owner_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'hired_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(Employee::STATUSES)],
        ];
    }
}
