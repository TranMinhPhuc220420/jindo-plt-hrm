import { Link } from '@inertiajs/react';
import { PlusIcon, XIcon } from 'lucide-react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { CycleStatusBadge, ProgressMeter } from '@/components/performance/performance-status';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { EmployeePickerDialog } from '@/components/shared/employee-picker-dialog';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Badge } from '@/components/ui/badge';
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
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import type { Employee } from '@/lib/api/modules/employees';
import * as performanceApi from '@/lib/api/modules/performance';
import type {
    ReviewCycle,
    ReviewCycleFramework,
} from '@/lib/api/modules/performance';

const FRAMEWORKS: ReviewCycleFramework[] = ['goal', 'kpi', 'okr', 'mixed'];

type ParticipantChip = {
    id: number;
    label: string;
};

function emptyCreateForm() {
    return {
        name: '',
        framework: 'goal' as ReviewCycleFramework,
        startsOn: '',
        endsOn: '',
        participants: [] as ParticipantChip[],
    };
}

function cycleProgress(cycle: ReviewCycle): {
    evaluated: number;
    total: number;
} {
    const total =
        cycle.participants_count ?? cycle.participant_employee_ids.length;
    const evaluated = cycle.evaluations_count ?? 0;

    return { evaluated, total };
}

export default function PerformanceIndexPage() {
    const { t } = useTranslation(['performance', 'common']);
    const [cycles, setCycles] = useState<ReviewCycle[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [pickerOpen, setPickerOpen] = useState(false);
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

    useLoadEffect(load, [load]);

    function resetCreateForm() {
        setForm(emptyCreateForm());
    }

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);

        if (!open) {
            resetCreateForm();
            setPickerOpen(false);
        }
    }

    function addParticipant(employee: Employee) {
        setForm((prev) => {
            if (prev.participants.some((p) => p.id === employee.id)) {
                return prev;
            }

            return {
                ...prev,
                participants: [
                    ...prev.participants,
                    {
                        id: employee.id,
                        label: `${employee.code} — ${employee.full_name}`,
                    },
                ],
            };
        });
    }

    function removeParticipant(id: number) {
        setForm((prev) => ({
            ...prev,
            participants: prev.participants.filter((p) => p.id !== id),
        }));
    }

    async function handleCreate(e: FormEvent) {
        e.preventDefault();

        if (form.participants.length < 1) {
            toast.error(t('participants_required'));

            return;
        }

        setBusy(true);

        try {
            await performanceApi.createCycle({
                name: form.name,
                framework: form.framework,
                starts_on: form.startsOn || undefined,
                ends_on: form.endsOn || undefined,
                participant_employee_ids: form.participants.map((p) => p.id),
            });
            toast.success(t('toast_created'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleDeleteCycle(cycle: ReviewCycle) {
        const confirmed = window.confirm(t('confirm_delete_draft'));

        if (!confirmed) {
            return;
        }

        setBusy(true);

        try {
            await performanceApi.deleteCycle(cycle.id);
            toast.success(t('toast_deleted'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('toast_error'),
            );
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
                        <PlusIcon className="size-4" />
                        {t('create_title')}
                    </Button>
                </PermissionGate>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('create_title')}</DialogTitle>
                        <DialogDescription>
                            {t('create_description')}
                        </DialogDescription>
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
                                placeholder={t('name_placeholder')}
                                required
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="cycle_framework">
                                {t('framework')}
                            </Label>
                            <select
                                id="cycle_framework"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
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
                        <div className="grid gap-1.5">
                            <div className="flex items-center justify-between gap-2">
                                <Label>{t('participants_label')}</Label>
                                <span className="text-xs text-muted-foreground">
                                    {t('participants_selected', {
                                        count: form.participants.length,
                                    })}
                                </span>
                            </div>
                            <div className="min-h-11 rounded-md border border-dashed border-border bg-muted/20 p-2">
                                {form.participants.length === 0 ? (
                                    <p className="px-1 py-1.5 text-xs text-muted-foreground">
                                        {t('participants_help')}
                                    </p>
                                ) : (
                                    <div className="flex flex-wrap gap-1.5">
                                        {form.participants.map(
                                            (participant) => (
                                                <span
                                                    key={participant.id}
                                                    className="inline-flex max-w-full items-center gap-1 rounded-md border border-border bg-background px-2 py-1 text-xs"
                                                >
                                                    <span className="truncate">
                                                        {participant.label}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        className="rounded p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                                                        onClick={() =>
                                                            removeParticipant(
                                                                participant.id,
                                                            )
                                                        }
                                                        aria-label={t(
                                                            'remove_participant',
                                                        )}
                                                    >
                                                        <XIcon className="size-3.5" />
                                                    </button>
                                                </span>
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="w-fit"
                                onClick={() => setPickerOpen(true)}
                            >
                                <PlusIcon className="size-3.5" />
                                {t('add_participant')}
                            </Button>
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={
                                    busy || form.participants.length < 1
                                }
                            >
                                {t('create', { ns: 'common' })}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <EmployeePickerDialog
                open={pickerOpen}
                onOpenChange={setPickerOpen}
                onSelect={addParticipant}
            />

            {loading ? (
                <LoadingState label={t('loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : cycles.length === 0 ? (
                <div className="flex flex-col items-start gap-4 rounded-lg border border-dashed border-border px-6 py-10">
                    <EmptyState message={t('empty')} />
                    <PermissionGate permission="can_manage_review_cycles">
                        <Button
                            type="button"
                            onClick={() => setCreateOpen(true)}
                        >
                            <PlusIcon className="size-4" />
                            {t('create_title')}
                        </Button>
                    </PermissionGate>
                </div>
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2.5 font-medium">
                                    {t('col_name')}
                                </th>
                                <th className="px-3 py-2.5 font-medium">
                                    {t('col_framework')}
                                </th>
                                <th className="px-3 py-2.5 font-medium">
                                    {t('col_status')}
                                </th>
                                <th className="px-3 py-2.5 font-medium">
                                    {t('col_period')}
                                </th>
                                <th className="px-3 py-2.5 font-medium">
                                    {t('col_progress')}
                                </th>
                                <th className="px-3 py-2.5 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {cycles.map((cycle) => {
                                const { evaluated, total } =
                                    cycleProgress(cycle);

                                return (
                                    <tr
                                        key={cycle.id}
                                        className="border-t border-border/60 transition-colors hover:bg-muted/30"
                                    >
                                        <td className="px-3 py-3">
                                            <Link
                                                href={`/performance/cycles/${cycle.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {cycle.name}
                                            </Link>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {t('participants_count_short', {
                                                    count:
                                                        cycle.participants_count ??
                                                        cycle
                                                            .participant_employee_ids
                                                            .length,
                                                })}
                                            </p>
                                        </td>
                                        <td className="px-3 py-3">
                                            <Badge variant="outline">
                                                {t(
                                                    `framework_option.${cycle.framework}`,
                                                    {
                                                        defaultValue:
                                                            cycle.framework,
                                                    },
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-3 py-3">
                                            <CycleStatusBadge
                                                status={cycle.status}
                                            />
                                        </td>
                                        <td className="px-3 py-3 text-muted-foreground">
                                            {cycle.starts_on ?? '—'}
                                            {cycle.ends_on
                                                ? ` → ${cycle.ends_on}`
                                                : ''}
                                        </td>
                                        <td className="min-w-[10rem] px-3 py-3">
                                            {cycle.status === 'draft' ? (
                                                <span className="text-xs text-muted-foreground">
                                                    {t('progress_not_started')}
                                                </span>
                                            ) : (
                                                <ProgressMeter
                                                    value={evaluated}
                                                    max={Math.max(total, 1)}
                                                    label={t(
                                                        'progress_evaluated_short',
                                                        {
                                                            evaluated,
                                                            total,
                                                        },
                                                    )}
                                                />
                                            )}
                                        </td>
                                        <td className="px-3 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                {cycle.status === 'draft' ? (
                                                    <PermissionGate permission="can_manage_review_cycles">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive hover:text-destructive"
                                                            disabled={busy}
                                                            onClick={() =>
                                                                void handleDeleteCycle(
                                                                    cycle,
                                                                )
                                                            }
                                                        >
                                                            {t('delete_cycle')}
                                                        </Button>
                                                    </PermissionGate>
                                                ) : null}
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
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminPageShell>
    );
}
