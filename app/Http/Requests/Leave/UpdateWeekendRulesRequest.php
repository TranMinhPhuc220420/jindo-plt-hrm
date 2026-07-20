<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeekendRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_holidays') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weekend_days' => ['required', 'array', 'min:0'],
            'weekend_days.*' => ['integer', 'min:0', 'max:6'],
        ];
    }
}
