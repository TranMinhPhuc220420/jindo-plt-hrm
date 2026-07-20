<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnboardingTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.key' => ['required_with:items', 'string', 'max:64'],
            'items.*.title' => ['required_with:items', 'string', 'max:191'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.mandatory' => ['nullable', 'boolean'],
            'items.*.assignee_type' => ['nullable', 'string', 'in:hr,employee,manager,it'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}
