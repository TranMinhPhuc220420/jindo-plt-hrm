<?php

namespace App\Http\Requests\Payroll;

use App\Models\EmployeeSalary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEmployeeSalaryRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:8'],
            'effective_from' => ['required', 'date'],
            'strategy' => ['sometimes', 'string', Rule::in(EmployeeSalary::STRATEGIES)],
        ];
    }
}
