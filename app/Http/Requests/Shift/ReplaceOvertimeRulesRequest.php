<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceOvertimeRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_overtime_rules') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.*.code' => ['required', 'string', 'max:50'],
            'rules.*.name' => ['required', 'string', 'max:100'],
            'rules.*.applies_after_minutes' => ['sometimes', 'integer', 'min:0'],
            'rules.*.allow_before_shift' => ['sometimes', 'boolean'],
            'rules.*.night_ot_enabled' => ['sometimes', 'boolean'],
            'rules.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
