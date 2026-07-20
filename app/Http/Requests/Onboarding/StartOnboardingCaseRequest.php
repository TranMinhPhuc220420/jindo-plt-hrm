<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StartOnboardingCaseRequest extends FormRequest
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
            'employee_id' => ['required', 'integer'],
            'template_id' => ['nullable', 'integer'],
            'offer_id' => ['nullable', 'integer'],
            'probation_ends_on' => ['nullable', 'date'],
        ];
    }
}
