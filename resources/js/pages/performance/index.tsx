import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as performanceApi from '@/lib/api/modules/performance';
import type {
    ReviewCycle,
    ReviewCycleFramework,
} from '@/lib/api/modules/performance';

const FRAMEWORKS: ReviewCycleFramework[] = ['goal', 'kpi', 'okr', 'mixed'];

function emptyCreateForm() {
    return {
        name: '',
        framework: 'goal' as ReviewCycleFramework,
        startsOn: '',
        endsOn: '',
    };
}

export default function PerformanceIndexPage() {
    const { t } = useTranslation(['performance', 'common']);
    const [cycles, setCycles] = useState<ReviewCycle[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyCreateForm);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await performanceApi.listCycles({ per_page: 50 });
            setCycles(result.data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('error_load'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    useEffect(() => {
        void load();
    }, [load]);

    function resetCreateForm() {
        setForm(emptyCreateForm());
    }

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);
        if (!open) {
            resetCreateForm();
        }
    }

    async function handleCreate(e: FormEvent) {
        e.preventDefault();
        setBusy(true);

        try {
            await performanceApi.createCycle({
                name: form.name,
                framework: form.framework,
                starts_on: form.startsOn || undefined,
                ends_on: form.endsOn || undefined,
            });
            toast.success(t('toast_created'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(err instanceof ApiError ? err.message : t('toast_error'));
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminPageShell
            title={t('title')}
            description={t('description')}
            permission="can_view_performance"
            actions={
                <PermissionGate permission="can_manage_review_cycles">
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        {t('create_title')}
                    </Button>
                </PermissionGate>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('create_title')}</DialogTitle>
                        <DialogDescription>{t('description')}</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="cycle_name">{t('name')}</Label>
                            <Input
                                id="cycle_name"
                                value={form.name}
                                onChange={(e) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        name: e.target.value,
                                    }))
                                }
                                required
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="cycle_framework">
                                {t('framework')}
                            </Label>
                            <select
                                id="cycle_framework"
                                className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                value={form.framework}
                                onChange={(e) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        framework: e.target
                                            .value as ReviewCycleFramework,
                                    }))
                                }
                            >
                                {FRAMEWORKS.map((fw) => (
                                    <option key={fw} value={fw}>
                                        {t(`framework_option.${fw}`)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="cycle_dates">
                                {t('starts_on')}
                                {' – '}
                                {t('ends_on')}
                            </Label>
                            <DateRangePicker
                                id="cycle_dates"
                                from={form.startsOn}
                                to={form.endsOn}
                                onChange={({ from, to }) => {
                                    setForm((prev) => ({
                                        ...prev,
                                        startsOn: from,
                                        endsOn: to,
                                    }));
                                }}
                                numberOfMonths={1}
                            />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={busy}>
                                {t('create', { ns: 'common' })}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {loading ? (
                <LoadingState label={t('loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : cycles.length === 0 ? (
                <EmptyState message={t('empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="bg-muted/50 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('col_name')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('col_framework')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('col_status')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('col_period')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('col_participants')}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {cycles.map((cycle) => (
                                <tr
                                    key={cycle.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-2 font-medium">
                                        {cycle.name}
                                    </td>
                                    <td className="px-3 py-2">
                                        {t(
                                            `framework_option.${cycle.framework}`,
                                            { defaultValue: cycle.framework },
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {t(`status.${cycle.status}`, {
                                            defaultValue: cycle.status,
                                        })}
                                    </td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {cycle.starts_on ?? '—'}
                                        {cycle.ends_on
                                            ? ` → ${cycle.ends_on}`
                                            : ''}
                                    </td>
                                    <td className="px-3 py-2">
                                        {cycle.participants_count ??
                                            cycle.participant_employee_ids
                                                .length}
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/performance/cycles/${cycle.id}`}
                                            >
                                                {t('open')}
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
