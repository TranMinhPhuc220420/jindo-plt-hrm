import { Link, router } from '@inertiajs/react';
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
import type { PayrollItem, PayrollRun } from '@/lib/api/modules/payroll';
import { loadCompanyCurrency } from '@/lib/company-currency';
import { formatCurrency } from '@/lib/currency';
import type { AppCurrency } from '@/lib/currency';

type Props = {
    id: number;
};

export default function PayrollShowPage({ id }: Props) {
    const { t } = useTranslation(['payroll', 'common']);
    const [run, setRun] = useState<PayrollRun | null>(null);
    const [items, setItems] = useState<PayrollItem[]>([]);
    const [companyCurrency, setCompanyCurrency] = useState<AppCurrency>('VND');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [editForm, setEditForm] = useState({
        name: '',
        periodStart: '',
        periodEnd: '',
    });

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [runData, itemsResult, currency] = await Promise.all([
                payrollApi.getPayrollRun(id),
                payrollApi.listPayrollItems(id, { per_page: 100 }),
                loadCompanyCurrency(),
            ]);
            setRun(runData ?? null);
            setItems(itemsResult.data);
            setCompanyCurrency(currency);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('show.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useEffect(() => {
        void load();
    }, [load]);

    function openEditDialog() {
        if (!run) {
            return;
        }

        setEditForm({
            name: run.name,
            periodStart: run.period_start,
            periodEnd: run.period_end,
        });
        setEditOpen(true);
    }

    function handleEditOpenChange(open: boolean) {
        setEditOpen(open);

        if (!open && run) {
            setEditForm({
                name: run.name,
                periodStart: run.period_start,
                periodEnd: run.period_end,
            });
        }
    }

    async function handleUpdate(event: FormEvent) {
        event.preventDefault();
        setBusy(true);

        try {
            await payrollApi.updatePayrollRun(id, {
                name: editForm.name,
                period_start: editForm.periodStart,
                period_end: editForm.periodEnd,
            });
            toast.success(t('show.toast_updated'));
            handleEditOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('show.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleDelete() {
        if (!window.confirm(t('show.confirm_delete'))) {
            return;
        }

        setBusy(true);

        try {
            await payrollApi.deletePayrollRun(id);
            toast.success(t('show.toast_deleted'));
            router.visit('/payroll');
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('show.toast_error'),
            );
            setBusy(false);
        }
    }

    async function runAction(
        action: 'calculate' | 'approve' | 'finalize',
        successKey: string,
    ) {
        setBusy(true);

        try {
            const fn =
                action === 'calculate'
                    ? payrollApi.calculatePayrollRun
                    : action === 'approve'
                      ? payrollApi.approvePayrollRun
                      : payrollApi.finalizePayrollRun;
            await fn(id);
            toast.success(t(successKey));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('show.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminPageShell
            title={run?.name ?? t('show.title')}
            description={t('show.description')}
            any={[
                'can_view_payroll_history',
                'can_run_payroll',
                'can_approve_payroll',
            ]}
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/payroll">{t('show.back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('show.loading')} />
            ) : error || !run ? (
                <ErrorState message={error ?? t('show.error_load')} />
            ) : (
                <div className="space-y-8">
                    <div className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.period')}
                            </p>
                            <p>
                                {run.period_start} → {run.period_end}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.status')}
                            </p>
                            <p>
                                {t(`status.${run.status}`, {
                                    defaultValue: run.status,
                                })}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.gross')}
                            </p>
                            <p className="tabular-nums">
                                {formatCurrency(
                                    run.total_gross,
                                    companyCurrency,
                                )}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                {t('show.net')}
                            </p>
                            <p className="tabular-nums">
                                {formatCurrency(run.total_net, companyCurrency)}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <PermissionGate permission="can_run_payroll">
                            {run.status === 'draft' ? (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    disabled={busy}
                                    onClick={openEditDialog}
                                >
                                    {t('show.edit')}
                                </Button>
                            ) : null}
                            {run.status !== 'finalized' ? (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    disabled={busy}
                                    onClick={() => void handleDelete()}
                                >
                                    {t('show.delete')}
                                </Button>
                            ) : null}
                        </PermissionGate>
                        <PermissionGate permission="can_run_payroll">
                            <Button
                                disabled={busy || run.status !== 'draft'}
                                onClick={() =>
                                    void runAction(
                                        'calculate',
                                        'show.toast_calculated',
                                    )
                                }
                            >
                                {t('show.calculate')}
                            </Button>
                        </PermissionGate>
                        <PermissionGate permission="can_approve_payroll">
                            <Button
                                variant="secondary"
                                disabled={busy || run.status !== 'calculated'}
                                onClick={() =>
                                    void runAction(
                                        'approve',
                                        'show.toast_approved',
                                    )
                                }
                            >
                                {t('show.approve')}
                            </Button>
                        </PermissionGate>
                        <PermissionGate permission="can_approve_payroll">
                            <Button
                                variant="outline"
                                disabled={busy || run.status !== 'approved'}
                                onClick={() =>
                                    void runAction(
                                        'finalize',
                                        'show.toast_finalized',
                                    )
                                }
                            >
                                {t('show.finalize')}
                            </Button>
                        </PermissionGate>
                    </div>

                    <Dialog open={editOpen} onOpenChange={handleEditOpenChange}>
                        <DialogContent className="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>
                                    {t('show.edit_title')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('show.edit_description')}
                                </DialogDescription>
                            </DialogHeader>
                            <form
                                onSubmit={handleUpdate}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="payroll-edit-name">
                                        {t('index.name')}
                                    </Label>
                                    <Input
                                        id="payroll-edit-name"
                                        value={editForm.name}
                                        onChange={(e) =>
                                            setEditForm((prev) => ({
                                                ...prev,
                                                name: e.target.value,
                                            }))
                                        }
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="payroll-edit-period">
                                        {t('index.period_start')}
                                        {' – '}
                                        {t('index.period_end')}
                                    </Label>
                                    <DateRangePicker
                                        id="payroll-edit-period"
                                        from={editForm.periodStart}
                                        to={editForm.periodEnd}
                                        onChange={({ from, to }) => {
                                            setEditForm((prev) => ({
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
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            {t('cancel', { ns: 'common' })}
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={busy}>
                                        {t('show.save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <div>
                        <h2 className="mb-3 text-sm font-medium">
                            {t('show.items_title')}
                        </h2>
                        {items.length === 0 ? (
                            <EmptyState message={t('show.empty_items')} />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">
                                                {t('show.col_employee')}
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                {t('show.col_gross')}
                                            </th>
                                            <th className="py-2 font-medium">
                                                {t('show.col_net')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b border-border/60"
                                            >
                                                <td className="py-3 pr-4">
                                                    {item.employee_code
                                                        ? `${item.employee_code} — ${item.employee_name}`
                                                        : (item.employee_name ??
                                                          `#${item.employee_id}`)}
                                                </td>
                                                <td className="py-3 pr-4 tabular-nums">
                                                    {formatCurrency(
                                                        item.gross,
                                                        companyCurrency,
                                                    )}
                                                </td>
                                                <td className="py-3 tabular-nums">
                                                    {formatCurrency(
                                                        item.net,
                                                        companyCurrency,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AdminPageShell>
    );
}
