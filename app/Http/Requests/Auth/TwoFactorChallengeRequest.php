<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasCode = filled($this->input('code'));
            $hasRecovery = filled($this->input('recovery_code'));

            if ($hasCode === $hasRecovery) {
                $validator->errors()->add(
                    'code',
                    'Provide either a one-time code or a recovery code.',
                );
            }
        });
    }
}
