<?php

namespace App\Support;

/**
 * Default company settings seeded / merged for Foundation.
 */
final class SettingsDefaults
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'company' => [
                'timezone' => 'Asia/Ho_Chi_Minh',
                'locale' => 'vi',
                'currency' => 'VND',
                'week_start' => 'monday',
            ],
            'auth' => [
                'session_lifetime_minutes' => 120,
                'two_factor_required' => false,
                'remember_me_enabled' => true,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedGroups(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return list<string>
     */
    public static function allowedKeysForGroup(string $group): array
    {
        return array_keys(self::all()[$group] ?? []);
    }
}
