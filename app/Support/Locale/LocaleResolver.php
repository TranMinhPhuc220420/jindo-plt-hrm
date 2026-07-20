<?php

namespace App\Support\Locale;

use App\Models\Setting;
use App\Models\User;
use App\Services\Organization\CompanyContext;
use Throwable;

class LocaleResolver
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    public function resolve(?User $user = null): string
    {
        $userLocale = $user?->locale;

        if (SupportedLocales::isValid($userLocale)) {
            return $userLocale;
        }

        return SupportedLocales::normalize($this->companyLocale());
    }

    public function companyLocale(): string
    {
        try {
            $companyId = $this->companyContext->id();
        } catch (Throwable) {
            return SupportedLocales::normalize(config('app.locale'));
        }

        $stored = Setting::query()
            ->where('company_id', $companyId)
            ->where('group', 'company')
            ->where('key', 'locale')
            ->value('value');

        if (is_string($stored) && SupportedLocales::isValid($stored)) {
            return $stored;
        }

        // JSON cast may return a quoted string already decoded
        if (is_string($stored)) {
            return SupportedLocales::normalize($stored);
        }

        return SupportedLocales::normalize(config('app.locale'));
    }

    /**
     * @return array{locale: string, user_locale: string|null, company_locale: string}
     */
    public function payload(?User $user = null): array
    {
        $companyLocale = $this->companyLocale();
        $userLocale = SupportedLocales::isValid($user?->locale) ? $user->locale : null;

        return [
            'locale' => $userLocale ?? $companyLocale,
            'user_locale' => $userLocale,
            'company_locale' => $companyLocale,
        ];
    }
}
