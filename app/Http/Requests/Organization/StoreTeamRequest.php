<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_organization') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $companyId = app(\App\Services\Organization\CompanyContext::class)->id();

        return [
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('teams', 'code')->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
