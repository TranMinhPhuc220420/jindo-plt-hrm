import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { LeaveRequestCreate } from '@/components/leave/leave-request-create';
import { emptyLeaveRequestForm } from '@/components/leave/leave-request-form';
import type { LeaveRequestFormValues } from '@/components/leave/leave-request-form';
import { LeaveRequestSheet } from '@/components/leave/leave-request-sheet';
import { LeaveRequestsTable } from '@/components/leave/leave-requests-table';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { useIsMobile } from '@/hooks/use-mobile';
import { ApiError } from '@/lib/api/errors';
import * as leaveApi from '@/lib/api/modules/leave';
import type {
    LeaveBalance,
    LeaveRequest,
    LeaveType,
} from '@/lib/api/modules/leave';
import { useAuth } from '@/lib/auth/auth-context';
import { leaveTypeLabel } from '@/lib/i18n/leave-labels';

export default function LeaveIndexPage() {
    const { t } = useTranslation(['leave', 'common']);
    const { employeeId, can } = useAuth();
    const isMobile = useIsMobile();
    const [requests, setRequests] = useState<LeaveRequest[]>([]);
    const [balances, setBalances] = useState<LeaveBalance[]>([]);
    const [types, setTypes] = useState<LeaveType[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState<LeaveRequestFormValues>(
        emptyLeaveRequestForm,
    );
    const [busy, setBusy] = useState(false);
    const [selectedRequestId, setSelectedRequestId] = useState<number | null>(
        null,
    );

    const canApproveLeave = can('can_approve_leave');
    const canRequestLeave = can('can_request_leave');
    const canManageTypes = can('can_manage_leave_types');
    const canManageHolidays = can('can_manage_holidays');

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

    useLoadEffect(load, [load]);

    function resetRequestForm() {
        setForm(emptyLeaveRequestForm());
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

    const selectedRequest =
        selectedRequestId != null
            ? (requests.find((row) => row.id === selectedRequestId) ?? null)
            : null;

    const desktopActions = (
        <div className="flex flex-wrap items-center justify-end gap-2">
            <PermissionGate permission="can_manage_leave_types">
                <Button variant="outline" asChild>
                    <Link href="/leave/types">{t('index.types_link')}</Link>
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
                <Button type="button" onClick={() => setCreateOpen(true)}>
                    {t('index.submit')}
                </Button>
            </PermissionGate>
        </div>
    );

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            permission="can_view_leave"
            actions={isMobile ? undefined : desktopActions}
        >
            <LeaveRequestCreate
                open={createOpen}
                onOpenChange={handleCreateOpenChange}
                employeeId={employeeId}
                types={types}
                values={form}
                onChange={setForm}
                busy={busy}
                onSubmit={(event) => void handleCreate(event)}
            />

            {isMobile ? (
                <div className="mb-6 space-y-2">
                    <PermissionGate permission="can_request_leave">
                        <Button
                            type="button"
                            size="lg"
                            className="min-h-11 w-full"
                            onClick={() => setCreateOpen(true)}
                        >
                            {t('index.submit')}
                        </Button>
                    </PermissionGate>
                    {(canManageTypes || canManageHolidays) && (
                        <div className="flex gap-2">
                            {canManageTypes ? (
                                <Button
                                    variant="outline"
                                    className="min-h-11 flex-1"
                                    asChild
                                >
                                    <Link href="/leave/types">
                                        {t('index.types_link')}
                                    </Link>
                                </Button>
                            ) : null}
                            {canManageHolidays ? (
                                <Button
                                    variant="outline"
                                    className="min-h-11 flex-1"
                                    asChild
                                >
                                    <Link href="/leave/holidays">
                                        {t('index.holidays_link')}
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    )}
                </div>
            ) : null}

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
                <LeaveRequestsTable
                    requests={requests}
                    employeeId={employeeId}
                    canApproveLeave={canApproveLeave}
                    canRequestLeave={canRequestLeave}
                    onApprove={(id) => void handleApprove(id)}
                    onReject={(id) => void handleReject(id)}
                    onCancel={(id) => void handleCancel(id)}
                    onSelectRequest={setSelectedRequestId}
                />
            )}

            <LeaveRequestSheet
                open={selectedRequestId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedRequestId(null);
                    }
                }}
                request={selectedRequest}
                canApprove={
                    !!selectedRequest &&
                    selectedRequest.status === 'pending' &&
                    canApproveLeave
                }
                canCancel={
                    !!selectedRequest &&
                    selectedRequest.status === 'pending' &&
                    canRequestLeave &&
                    selectedRequest.employee_id === employeeId
                }
                onApprove={(id) => void handleApprove(id)}
                onReject={(id) => void handleReject(id)}
                onCancel={(id) => void handleCancel(id)}
            />
        </AdminPageShell>
    );
}
