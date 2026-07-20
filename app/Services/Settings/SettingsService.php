<?php

namespace App\Services\Settings;

use App\Exceptions\DomainException;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use App\Support\SettingsDefaults;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(?string $group = null): array
    {
        $companyId = $this->companyContext->id();
        $defaults = SettingsDefaults::all();

        if ($group !== null) {
            if (! in_array($group, SettingsDefaults::allowedGroups(), true)) {
                throw new DomainException(
                    message: 'Unknown settings group.',
                    errorCode: 'SETTINGS_GROUP_INVALID',
                    status: 404,
                );
            }

            $defaults = [$group => $defaults[$group]];
        }

        $stored = Setting::query()
            ->where('company_id', $companyId)
            ->when($group !== null, fn ($q) => $q->where('group', $group))
            ->get()
            ->groupBy('group');

        $result = [];

        foreach ($defaults as $groupName => $keys) {
            $result[$groupName] = $keys;

            foreach ($stored->get($groupName, collect()) as $setting) {
                $result[$groupName][$setting->key] = $setting->value;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $payload
     * @return array<string, array<string, mixed>>
     */
    public function update(array $payload): array
    {
        $companyId = $this->companyContext->id();

        DB::transaction(function () use ($payload, $companyId): void {
            foreach ($payload as $group => $values) {
                if (! is_array($values)) {
                    throw new DomainException(
                        message: 'Settings groups must be objects.',
                        errorCode: 'SETTINGS_PAYLOAD_INVALID',
                        status: 422,
                    );
                }

                if (! in_array($group, SettingsDefaults::allowedGroups(), true)) {
                    throw new DomainException(
                        message: __('domain.settings_group_invalid', [
                            'group' => $group,
                        ]),
                        errorCode: 'SETTINGS_GROUP_INVALID',
                        status: 422,
                    );
                }

                $allowedKeys = SettingsDefaults::allowedKeysForGroup($group);

                foreach ($values as $key => $value) {
                    if (! in_array($key, $allowedKeys, true)) {
                        throw new DomainException(
                            message: __('domain.settings_key_invalid', [
                                'group' => $group,
                                'key' => $key,
                            ]),
                            errorCode: 'SETTINGS_KEY_INVALID',
                            status: 422,
                        );
                    }

                    Setting::query()->updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'group' => $group,
                            'key' => $key,
                        ],
                        ['value' => $value],
                    );
                }
            }

            $this->audit->write(
                action: 'settings.updated',
                payload: ['changes' => $payload],
                companyId: $companyId,
            );
        });

        return $this->all();
    }

    public function seedDefaultsForCompany(int $companyId): void
    {
        foreach (SettingsDefaults::all() as $group => $keys) {
            foreach ($keys as $key => $value) {
                Setting::query()->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'group' => $group,
                        'key' => $key,
                    ],
                    ['value' => $value],
                );
            }
        }
    }
}
