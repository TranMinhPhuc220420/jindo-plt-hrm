import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
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
import * as payrollApi from '@/lib/api/modules/payroll';
import type { PayrollRun } from '@/lib/api/modules/payroll';
import { loadCompanyCurrency } from '@/lib/company-currency';
import { formatCurrency } from '@/lib/currency';
import type { AppCurrency } from '@/lib/currency';

function emptyCreateForm() {
    return { name: '', periodStart: '', periodEnd: '' };
}

export default function PayrollIndexPage() {
    const { t } = useTranslation(['payroll', 'common']);
    const [runs, setRuns] = useState<PayrollRun[]>([]);
    const [companyCurrency, setCompanyCurrency] = useState<AppCurrency>('VND');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyCreateForm);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [result, currency] = await Promise.all([
                payrollApi.listPayrollRuns({ per_page: 30 }),
                loadCompanyCurrency(),
            ]);
            setRuns(result.data);
            setCompanyCurrency(currency);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
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

    async function handleCreate(event: FormEvent) {
        event.preventDefault();
        setBusy(true);

        try {
            await payrollApi.createPayrollRun({
                name: form.name,
                period_start: form.periodStart,
                period_end: form.periodEnd,
            });
            toast.success(t('index.toast_created'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleDelete(run: PayrollRun) {
        if (!window.confirm(t('index.confirm_delete', { name: run.name }))) {
            return;
        }

        try {
            await payrollApi.deletePayrollRun(run.id);
            toast.success(t('index.toast_deleted'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        }
    }

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            any={['can_view_payroll_history', 'can_run_payroll']}
            actions={
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <PermissionGate
                        any={[
                            'can_view_salary',
                            'can_manage_payslips',
                            'can_view_payroll_history',
                        ]}
                    >
                        <Button variant="outline" asChild>
                            <Link href="/payroll/payslips">
                                {t('index.payslips_link')}
                            </Link>
                        </Button>
                    </PermissionGate>
                    <PermissionGate permission="can_manage_salary">
                        <Button variant="outline" asChild>
                            <Link href="/payroll/compensation">
                                {t('index.compensation_link')}
                            </Link>
                        </Button>
                    </PermissionGate>
                    <PermissionGate permission="can_run_payroll">
                        <Button
                            type="button"
                            onClick={() => setCreateOpen(true)}
                        >
                            {t('index.create')}
                        </Button>
                    </PermissionGate>
                </div>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('index.create_title')}</DialogTitle>
                        <DialogDescription>
                            {t('index.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="payroll-name">
                                {t('index.name')}
                            </Label>
                            <Input
                                id="payroll-name"
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
                        <div className="grid gap-2">
                            <Label htmlFor="payroll-period">
                                {t('index.period_start')}
                                {' – '}
                                {t('index.period_end')}
                            </Label>
                            <DateRangePicker
                                id="payroll-period"
                                from={form.periodStart}
                                to={form.periodEnd}
                                onChange={({ from, to }) => {
                                    setForm((prev) => ({
                                        ...prev,
                                        periodStart: from,
                                        periodEnd: to,
                                    }));
                                }}
                                required
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
                                {t('index.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : runs.length === 0 ? (
                <EmptyState message={t('index.empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_name')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_period')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_status')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_employees')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_net')}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {runs.map((run) => (
                                <tr
                                    key={run.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-3">{run.name}</td>
                                    <td className="px-3 py-3 whitespace-nowrap">
                                        {run.period_start} → {run.period_end}
                                    </td>
                                    <td className="px-3 py-3">
                                        {t(`status.${run.status}`, {
                                            defaultValue: run.status,
                                        })}
                                    </td>
                                    <td className="px-3 py-3">
                                        {run.employee_count}
                                    </td>
                                    <td className="px-3 py-3 tabular-nums">
                                        {formatCurrency(
                                            run.total_net,
                                            companyCurrency,
                                        )}
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        <div className="flex flex-wrap items-center justify-end gap-2">
                                            {run.status !== 'finalized' ? (
                                                <PermissionGate permission="can_run_payroll">
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        type="button"
                                                        onClick={() =>
                                                            void handleDelete(
                                                                run,
                                                            )
                                                        }
                                                    >
                                                        {t('index.delete')}
                                                    </Button>
                                                </PermissionGate>
                                            ) : null}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/payroll/${run.id}`}
                                                >
                                                    {t('index.open')}
                                                </Link>
                                            </Button>
                                        </div>
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
