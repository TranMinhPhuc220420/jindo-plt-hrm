import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as onboardingApi from '@/lib/api/modules/onboarding';
import type {
    OnboardingCase,
    OnboardingTemplate,
} from '@/lib/api/modules/onboarding';
import {
    onboardingProgressLabel,
    onboardingTemplateLabel,
} from '@/lib/i18n/onboarding-labels';

export default function OnboardingIndexPage() {
    const { t } = useTranslation(['onboarding', 'common']);
    const [cases, setCases] = useState<OnboardingCase[]>([]);
    const [templates, setTemplates] = useState<OnboardingTemplate[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const [employeeId, setEmployeeId] = useState<number | null>(null);
    const [templateId, setTemplateId] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const casesResult = await onboardingApi.listCases({ per_page: 50 });
            setCases(casesResult.data);

            try {
                const templatesResult = await onboardingApi.listTemplates({
                    per_page: 50,
                });
                setTemplates(templatesResult.data);
            } catch {
                setTemplates([]);
            }
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [t]);

    useLoadEffect(load, [load]);

    async function handleStart(event: FormEvent) {
        event.preventDefault();

        if (employeeId === null) {
            return;
        }

        setBusy(true);

        try {
            await onboardingApi.startCase({
                employee_id: employeeId,
                template_id: templateId ? Number(templateId) : undefined,
            });
            toast.success(t('index.toast_started'));
            setEmployeeId(null);
            setTemplateId('');
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            any={['can_view_onboarding', 'can_manage_onboarding']}
        >
            <PermissionGate permission="can_manage_onboarding">
                <form
                    onSubmit={handleStart}
                    className="mb-8 grid max-w-xl gap-3 border-b border-border pb-8"
                >
                    <h2 className="text-sm font-medium">
                        {t('index.start_title')}
                    </h2>
                    <div className="grid gap-2 sm:grid-cols-2">
                        <EmployeePickerField
                            id="employee_id"
                            label={t('index.employee')}
                            value={employeeId}
                            onChange={(id) => setEmployeeId(id)}
                            required
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="template_id">
                                {t('index.template')}
                            </Label>
                            <select
                                id="template_id"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                value={templateId}
                                onChange={(e) => setTemplateId(e.target.value)}
                            >
                                <option value="">
                                    {t('index.default_template')}
                                </option>
                                {templates.map((template) => (
                                    <option
                                        key={template.id}
                                        value={template.id}
                                    >
                                        {onboardingTemplateLabel(
                                            t,
                                            template.name,
                                        )}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <Button
                        type="submit"
                        disabled={busy || employeeId === null}
                    >
                        {t('index.start')}
                    </Button>
                </form>
            </PermissionGate>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : cases.length === 0 ? (
                <EmptyState message={t('index.empty')} />
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-border text-muted-foreground">
                                <th className="py-2 pr-4 font-medium">
                                    {t('index.col_employee')}
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    {t('index.col_status')}
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    {t('index.col_progress')}
                                </th>
                                <th className="py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {cases.map((item) => (
                                <tr
                                    key={item.id}
                                    className="border-b border-border/60"
                                >
                                    <td className="py-3 pr-4">
                                        #{item.employee_id}
                                    </td>
                                    <td className="py-3 pr-4">
                                        {t(`status.${item.status}`, {
                                            defaultValue: item.status,
                                        })}
                                    </td>
                                    <td className="py-3 pr-4">
                                        {onboardingProgressLabel(
                                            t,
                                            item.progress,
                                        )}
                                    </td>
                                    <td className="py-3">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/onboarding/${item.id}`}
                                            >
                                                {t('index.open')}
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminPageShell>
    );
}
