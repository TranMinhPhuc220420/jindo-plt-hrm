<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateEmployeePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_update_employee') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'use_default' => ['sometimes', 'boolean'],
            'password' => [
                Rule::requiredIf(fn () => ! $this->boolean('use_default')),
                'prohibited_if:use_default,true',
                'string',
                Password::defaults(),
                'confirmed',
            ],
            'password_confirmation' => [
                Rule::requiredIf(fn () => $this->filled('password')),
                'string',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('use_default')) {
                return;
            }

            if (! $this->filled('password')) {
                $validator->errors()->add(
                    'password',
                    'Provide a new password or set use_default to true.',
                );
            }
        });
    }
}
