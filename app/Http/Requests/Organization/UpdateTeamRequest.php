<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
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
        $teamId = (int) $this->route('team');

        return [
            'department_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('teams', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at'))
                    ->ignore($teamId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
