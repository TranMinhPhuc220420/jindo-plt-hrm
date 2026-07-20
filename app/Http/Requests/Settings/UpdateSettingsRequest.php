<?php

namespace App\Http\Requests\Settings;

use App\Support\Locale\SupportedLocales;
use App\Support\SettingsDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('can_manage_settings') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            // At least one known group object
        ];

        foreach (SettingsDefaults::allowedGroups() as $group) {
            $rules[$group] = ['sometimes', 'array'];

            foreach (SettingsDefaults::allowedKeysForGroup($group) as $key) {
                if ($group === 'company' && $key === 'locale') {
                    $rules["{$group}.{$key}"] = ['sometimes', 'string', Rule::in(SupportedLocales::all())];

                    continue;
                }

                $rules["{$group}.{$key}"] = ['sometimes'];
            }
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $payload = collect($this->all())
                ->only(SettingsDefaults::allowedGroups())
                ->filter(fn ($value) => is_array($value) && $value !== []);

            if ($payload->isEmpty()) {
                $validator->errors()->add('settings', 'Provide at least one settings group to update.');
            }
        });
    }
}
