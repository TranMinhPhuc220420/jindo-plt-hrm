<?php

namespace App\Http\Requests\Auth;

use App\Support\Locale\SupportedLocales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'locale' => [
                'present',
                'nullable',
                'string',
                Rule::in(SupportedLocales::all()),
            ],
        ];
    }
}
