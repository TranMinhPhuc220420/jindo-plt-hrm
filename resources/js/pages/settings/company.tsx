import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as settingsApi from '@/lib/api/modules/settings';
import type { SettingsMap } from '@/lib/api/modules/settings';
import { useAuth } from '@/lib/auth/auth-context';

const SETTING_KEY_LABELS = [
    'timezone',
    'locale',
    'currency',
    'week_start',
    'session_lifetime_minutes',
    'two_factor_required',
    'remember_me_enabled',
] as const;

export default function CompanySettingsPage() {
    const { t } = useTranslation(['settings', 'common']);
    const { can } = useAuth();
    const canManage = can('can_manage_settings');

    const [settings, setSettings] = useState<SettingsMap | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            setSettings(await settingsApi.getSettings());
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('company.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [t]);

    useEffect(() => {
        void load();
    }, [load]);

    function settingLabel(key: string): string {
        if (
            (SETTING_KEY_LABELS as readonly string[]).includes(key)
        ) {
            return t(`keys.${key}`);
        }

        return key;
    }

    function updateValue(group: string, key: string, value: string) {
        setSettings((current) => {
            if (!current) {
                return current;
            }

            const currentValue = current[group]?.[key];
            let nextValue: unknown = value;

            if (typeof currentValue === 'boolean') {
                nextValue = value === 'true';
            } else if (typeof currentValue === 'number') {
                nextValue = Number(value);
            }

            return {
                ...current,
                [group]: {
                    ...current[group],
                    [key]: nextValue,
                },
            };
        });
    }

    async function handleSubmit(event: FormEvent) {
        event.preventDefault();

        if (!settings) {
            return;
        }

        setSaving(true);

        try {
            const updated = await settingsApi.updateSettings(settings);
            setSettings(updated);
            toast.success(t('company.toast_saved'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('company.toast_save_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <AdminPageShell
            title={t('company.title')}
            description={t('company.description')}
            permission="can_view_settings"
        >
            {loading && <LoadingState label={t('company.loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && settings && (
                <form onSubmit={handleSubmit} className="space-y-8">
                    {Object.keys(settings).length === 0 ? (
                        <EmptyState message={t('company.empty')} />
                    ) : (
                        Object.entries(settings).map(([group, values]) => (
                            <section key={group} className="space-y-3">
                                <h3 className="text-sm font-semibold capitalize">
                                    {group}
                                </h3>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {Object.entries(values).map(
                                        ([key, value]) => (
                                            <div
                                                key={`${group}.${key}`}
                                                className="space-y-1"
                                            >
                                                <Label
                                                    htmlFor={`${group}-${key}`}
                                                >
                                                    {settingLabel(key)}
                                                </Label>
                                                {key === 'locale' ? (
                                                    <select
                                                        id={`${group}-${key}`}
                                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                        value={String(
                                                            value ?? 'vi',
                                                        )}
                                                        disabled={!canManage}
                                                        onChange={(e) =>
                                                            updateValue(
                                                                group,
                                                                key,
                                                                e.target.value,
                                                            )
                                                        }
                                                    >
                                                        <option value="vi">
                                                            {t('locale_vi', {
                                                                ns: 'common',
                                                            })}
                                                        </option>
                                                        <option value="en">
                                                            {t('locale_en', {
                                                                ns: 'common',
                                                            })}
                                                        </option>
                                                    </select>
                                                ) : typeof value ===
                                                  'boolean' ? (
                                                    <select
                                                        id={`${group}-${key}`}
                                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                                        value={String(value)}
                                                        disabled={!canManage}
                                                        onChange={(e) =>
                                                            updateValue(
                                                                group,
                                                                key,
                                                                e.target.value,
                                                            )
                                                        }
                                                    >
                                                        <option value="true">
                                                            {t('true', {
                                                                ns: 'common',
                                                            })}
                                                        </option>
                                                        <option value="false">
                                                            {t('false', {
                                                                ns: 'common',
                                                            })}
                                                        </option>
                                                    </select>
                                                ) : (
                                                    <Input
                                                        id={`${group}-${key}`}
                                                        value={String(
                                                            value ?? '',
                                                        )}
                                                        disabled={!canManage}
                                                        onChange={(e) =>
                                                            updateValue(
                                                                group,
                                                                key,
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                )}
                                            </div>
                                        ),
                                    )}
                                </div>
                            </section>
                        ))
                    )}

                    <PermissionGate permission="can_manage_settings">
                        <Button type="submit" disabled={saving}>
                            {t('company.save')}
                        </Button>
                    </PermissionGate>
                </form>
            )}
        </AdminPageShell>
    );
}
