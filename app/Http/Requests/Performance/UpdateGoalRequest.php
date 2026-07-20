<?php

namespace App\Http\Requests\Performance;

use App\Models\PerformanceGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_goals') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'type' => ['sometimes', 'string', Rule::in(PerformanceGoal::TYPES)],
            'metric' => ['sometimes', 'nullable', 'string', 'max:191'],
            'target' => ['sometimes', 'nullable', 'string', 'max:191'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(PerformanceGoal::STATUSES)],
        ];
    }
}
