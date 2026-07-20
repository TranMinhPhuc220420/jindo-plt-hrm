<?php

namespace App\Http\Requests\Organization;

use App\Services\Organization\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
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
        $companyId = app(CompanyContext::class)->id();

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
