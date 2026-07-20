<?php

namespace App\Http\Requests\Performance;

use App\Models\PerformanceReviewCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_review_cycles') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'framework' => ['sometimes', 'string', Rule::in(PerformanceReviewCycle::FRAMEWORKS)],
            'starts_on' => ['sometimes', 'nullable', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_on'],
            'participant_employee_ids' => ['sometimes', 'array'],
            'participant_employee_ids.*' => ['integer'],
        ];
    }
}
