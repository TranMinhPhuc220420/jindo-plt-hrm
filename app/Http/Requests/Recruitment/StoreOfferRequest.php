<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:191'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'start_date' => ['nullable', 'date'],
            'probation_ends_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
