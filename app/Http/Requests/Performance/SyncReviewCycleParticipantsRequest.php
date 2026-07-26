<?php

namespace App\Http\Requests\Performance;

use Illuminate\Foundation\Http\FormRequest;

class SyncReviewCycleParticipantsRequest extends FormRequest
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
            // 'present' (not 'required') so an explicit empty array is accepted —
            // this endpoint fully replaces the roster, including clearing it.
            'participant_employee_ids' => ['present', 'array'],
            'participant_employee_ids.*' => ['integer'],
        ];
    }
}
