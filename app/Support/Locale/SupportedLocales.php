<?php

namespace App\Support\Locale;

final class SupportedLocales
{
    public const DEFAULT = 'vi';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return ['vi', 'en'];
    }

    public static function isValid(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::all(), true);
    }

    public static function normalize(?string $locale): string
    {
        if (self::isValid($locale)) {
            return $locale;
        }

        $fallback = config('app.locale', self::DEFAULT);

        return self::isValid($fallback) ? $fallback : self::DEFAULT;
    }
}
