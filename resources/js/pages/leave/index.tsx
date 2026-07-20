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
import * as leaveApi from '@/lib/api/modules/leave';
import type {
    LeaveBalance,
    LeaveRequest,
    LeaveType,
} from '@/lib/api/modules/leave';
import { useAuth } from '@/lib/auth/auth-context';
import { leaveTypeLabel } from '@/lib/i18n/leave-labels';

function emptyRequestForm() {
    return {
        leaveTypeId: '',
        startDate: '',
        endDate: '',
        reason: '',
    };
}

export default function LeaveIndexPage() {
    const { t } = useTranslation(['leave', 'common']);
    const { employeeId, can } = useAuth();
    const [requests, setRequests] = useState<LeaveRequest[]>([]);
    const [balances, setBalances] = useState<LeaveBalance[]>([]);
    const [types, setTypes] = useState<LeaveType[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyRequestForm);
    const [busy, setBusy] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [reqResult, typeResult] = await Promise.all([
                leaveApi.listRequests({ per_page: 30 }),
                leaveApi.listLeaveTypes({ per_page: 50 }),
            ]);
            setRequests(reqResult.data);
            setTypes(typeResult.data);

            if (employeeId) {
                const bal = await leaveApi.listBalances({
                    employee_id: employeeId,
                    year: new Date().getFullYear(),
                });
                setBalances(bal);
            }
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [employeeId, t]);

    useEffect(() => {
        void load();
    }, [load]);

    function resetRequestForm() {
        setForm(emptyRequestForm());
    }

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);

        if (!open) {
            resetRequestForm();
        }
    }

    async function handleCreate(e: FormEvent) {
        e.preventDefault();

        const startDate = form.startDate;
        const endDate = form.endDate || form.startDate;

        if (!form.leaveTypeId || !startDate) {
            toast.error(t('index.toast_error'));

            return;
        }

        setBusy(true);

        try {
            await leaveApi.createRequest({
                leave_type_id: Number(form.leaveTypeId),
                start_date: startDate,
                end_date: endDate,
                reason: form.reason || undefined,
                unit: 'day',
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

    async function handleApprove(id: number) {
        try {
            await leaveApi.approveRequest(id);
            toast.success(t('index.toast_approved'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        }
    }

    async function handleReject(id: number) {
        try {
            await leaveApi.rejectRequest(id);
            toast.success(t('index.toast_rejected'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        }
    }

    async function handleCancel(id: number) {
        try {
            await leaveApi.cancelRequest(id);
            toast.success(t('index.toast_cancelled'));
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
            permission="can_view_leave"
            actions={
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <PermissionGate permission="can_manage_leave_types">
                        <Button variant="outline" asChild>
                            <Link href="/leave/types">
                                {t('index.types_link')}
                            </Link>
                        </Button>
                    </PermissionGate>
                    <PermissionGate permission="can_manage_holidays">
                        <Button variant="outline" asChild>
                            <Link href="/leave/holidays">
                                {t('index.holidays_link')}
                            </Link>
                        </Button>
                    </PermissionGate>
                    <PermissionGate permission="can_request_leave">
                        <Button
                            type="button"
                            onClick={() => setCreateOpen(true)}
                        >
                            {t('index.submit')}
                        </Button>
                    </PermissionGate>
                </div>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('index.request_title')}</DialogTitle>
                        <DialogDescription>
                            {t('index.description')}
                        </DialogDescription>
                    </DialogHeader>
                    {!employeeId ? (
                        <p className="text-sm text-muted-foreground">
                            {t('index.no_employee')}
                        </p>
                    ) : (
                        <form onSubmit={handleCreate} className="grid gap-4">
                            <div className="grid gap-1.5">
                                <Label htmlFor="leave_type">
                                    {t('index.leave_type')}
                                </Label>
                                <select
                                    id="leave_type"
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    value={form.leaveTypeId}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            leaveTypeId: e.target.value,
                                        }))
                                    }
                                    required
                                >
                                    <option value="">
                                        {t('index.select_type')}
                                    </option>
                                    {types
                                        .filter((row) => row.is_active)
                                        .map((row) => (
                                            <option key={row.id} value={row.id}>
                                                {leaveTypeLabel(
                                                    t,
                                                    row.code,
                                                    row.name,
                                                )}
                                            </option>
                                        ))}
                                </select>
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="leave_dates">
                                    {t('index.start_date')}
                                    {' – '}
                                    {t('index.end_date')}
                                </Label>
                                <DateRangePicker
                                    id="leave_dates"
                                    from={form.startDate}
                                    to={form.endDate}
                                    onChange={({ from, to }) => {
                                        setForm((prev) => ({
                                            ...prev,
                                            startDate: from,
                                            endDate: to,
                                        }));
                                    }}
                                    required
                                    numberOfMonths={1}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="reason">
                                    {t('index.reason')}
                                </Label>
                                <Input
                                    id="reason"
                                    value={form.reason}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            reason: e.target.value,
                                        }))
                                    }
                                />
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
                                        busy ||
                                        !form.leaveTypeId ||
                                        !form.startDate
                                    }
                                >
                                    {t('index.submit')}
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            {balances.length > 0 && (
                <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {balances.map((b) => (
                        <div
                            key={`${b.leave_type_id}-${b.period_key}`}
                            className="rounded-lg border border-border bg-card p-3 text-sm"
                        >
                            <div className="font-medium">
                                {leaveTypeLabel(
                                    t,
                                    b.leave_type_code,
                                    b.leave_type_name,
                                )}
                            </div>
                            <div className="text-muted-foreground">
                                {t('index.remaining')}: {b.remaining} /{' '}
                                {b.entitled}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : requests.length === 0 ? (
                <EmptyState message={t('index.empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_employee')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_type')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_dates')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_qty')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_status')}
                                </th>
                                <th className="px-3 py-2 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {requests.map((row) => (
                                <tr
                                    key={row.id}
                                    className="border-t border-border/60"
                                >
                                    <td className="px-3 py-2">
                                        {row.employee_name ??
                                            row.employee_code ??
                                            row.employee_id}
                                    </td>
                                    <td className="px-3 py-2">
                                        {leaveTypeLabel(
                                            t,
                                            row.leave_type_code,
                                            row.leave_type_name,
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {row.start_date}
                                        {row.end_date !== row.start_date
                                            ? ` → ${row.end_date}`
                                            : ''}
                                    </td>
                                    <td className="px-3 py-2">
                                        {row.quantity}
                                    </td>
                                    <td className="px-3 py-2">
                                        {t(`status.${row.status}`, {
                                            defaultValue: row.status,
                                        })}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap justify-end gap-2">
                                            {row.status === 'pending' &&
                                                can('can_approve_leave') && (
                                                    <>
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                void handleApprove(
                                                                    row.id,
                                                                )
                                                            }
                                                        >
                                                            {t('index.approve')}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                void handleReject(
                                                                    row.id,
                                                                )
                                                            }
                                                        >
                                                            {t('index.reject')}
                                                        </Button>
                                                    </>
                                                )}
                                            {row.status === 'pending' &&
                                                can('can_request_leave') &&
                                                row.employee_id ===
                                                    employeeId && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            void handleCancel(
                                                                row.id,
                                                            )
                                                        }
                                                    >
                                                        {t('index.cancel')}
                                                    </Button>
                                                )}
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
