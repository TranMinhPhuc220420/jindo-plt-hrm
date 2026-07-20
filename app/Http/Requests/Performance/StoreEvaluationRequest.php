<?php

namespace App\Http\Requests\Performance;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_evaluate_employee') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'review_cycle_id' => ['required', 'integer'],
            'employee_id' => ['required', 'integer'],
            'overall_score' => ['required', 'numeric', 'min:0', 'max:5'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'ratings' => ['sometimes', 'nullable', 'array'],
            'ratings.*.criterion' => ['required_with:ratings', 'string'],
            'ratings.*.score' => ['required_with:ratings', 'numeric', 'min:0', 'max:5'],
        ];
    }
}
