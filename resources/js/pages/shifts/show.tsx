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
import { DatePicker } from '@/components/shared/date-picker';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { Shift, ShiftAssignment } from '@/lib/api/modules/shifts';
import { shiftKindLabel } from '@/lib/i18n/shift-labels';

type Props = {
    id: number;
};

export default function ShiftShowPage({ id }: Props) {
    const { t } = useTranslation(['shifts', 'common']);
    const [shift, setShift] = useState<Shift | null>(null);
    const [assignments, setAssignments] = useState<ShiftAssignment[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [employeeId, setEmployeeId] = useState<number | null>(null);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [shiftData, assignmentData] = await Promise.all([
                shiftApi.getShift(id),
                shiftApi.listShiftAssignments({ shift_id: id, per_page: 50 }),
            ]);
            setShift(shiftData);
            setAssignments(assignmentData.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('show.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useLoadEffect(load, [load]);

    async function handleAssign(event: FormEvent) {
        event.preventDefault();

        if (employeeId === null) {
            return;
        }

        try {
            await shiftApi.createShiftAssignment({
                employee_id: employeeId,
                shift_id: id,
                start_date: startDate,
                end_date: endDate || null,
            });
            toast.success(t('show.toast_assigned'));
            setEmployeeId(null);
            setStartDate('');
            setEndDate('');
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_assign_failed'),
            );
        }
    }

    async function handleRemoveAssignment(assignmentId: number) {
        try {
            await shiftApi.deleteShiftAssignment(assignmentId);
            toast.success(t('show.toast_removed'));
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_assign_failed'),
            );
        }
    }

    const description = shift
        ? `${shift.code} · ${shift.start_time}–${shift.end_time} · ${shiftKindLabel(t, shift.kind)}`
        : t('show.fallback_description');

    return (
        <AdminPageShell
            title={
                shift
                    ? t('show.assign_title', { name: shift.name })
                    : t('show.fallback_title')
            }
            description={description}
            permission="can_view_shifts"
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/shifts">{t('show.back')}</Link>
                </Button>
            </div>

            {loading ? (
                <LoadingState label={t('show.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : shift ? (
                <div className="grid gap-8">
                    <section className="max-w-2xl rounded-lg border border-border px-4 py-3 text-sm">
                        <p className="font-medium">
                            {shift.code} — {shift.name}
                        </p>
                        <p className="mt-1 text-muted-foreground">
                            {shift.start_time} – {shift.end_time}
                            {' · '}
                            {t('index.break_minutes_value', {
                                count: shift.break_minutes,
                            })}
                            {' · '}
                            {shiftKindLabel(t, shift.kind)}
                            {shift.is_night ? ` · ${t('create.is_night')}` : ''}
                            {shift.is_flexible
                                ? ` · ${t('create.is_flexible')}`
                                : ''}
                        </p>
                    </section>

                    <PermissionGate permission="can_assign_shifts">
                        <section className="grid max-w-2xl gap-4">
                            <h2 className="text-lg font-medium">
                                {t('show.section_assignments')}
                            </h2>
                            <form
                                onSubmit={handleAssign}
                                className="flex flex-wrap items-end gap-3"
                            >
                                <EmployeePickerField
                                    id="employee_id"
                                    label={t('show.employee')}
                                    value={employeeId}
                                    onChange={(empId) => setEmployeeId(empId)}
                                    required
                                    className="min-w-[16rem]"
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="start_date">
                                        {t('show.start_date')}
                                    </Label>
                                    <DatePicker
                                        id="start_date"
                                        value={startDate}
                                        onChange={setStartDate}
                                        required
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="end_date">
                                        {t('show.end_date')}
                                    </Label>
                                    <DatePicker
                                        id="end_date"
                                        value={endDate}
                                        onChange={setEndDate}
                                        min={startDate || undefined}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={employeeId === null || !startDate}
                                >
                                    {t('show.assign')}
                                </Button>
                            </form>
                        </section>
                    </PermissionGate>

                    <section className="grid max-w-2xl gap-4">
                        <h2 className="text-lg font-medium">
                            {t('show.section_assigned_list')}
                        </h2>
                        {assignments.length === 0 ? (
                            <EmptyState message={t('show.empty_assignments')} />
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {assignments.map((row) => (
                                    <li
                                        key={row.id}
                                        className="flex items-center justify-between rounded-md border px-3 py-2"
                                    >
                                        <span>
                                            <Link
                                                href={`/employees/${row.employee_id}`}
                                                className="font-medium text-primary-brand hover:underline"
                                            >
                                                #
                                                {row.employee?.code ??
                                                    row.employee_id}
                                                {row.employee?.full_name
                                                    ? ` — ${row.employee.full_name}`
                                                    : ''}
                                            </Link>{' '}
                                            · {row.start_date}
                                            {row.end_date
                                                ? ` → ${row.end_date}`
                                                : ` → ${t('show.open_end')}`}
                                        </span>
                                        <PermissionGate permission="can_assign_shifts">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    void handleRemoveAssignment(
                                                        row.id,
                                                    )
                                                }
                                            >
                                                {t('delete', { ns: 'common' })}
                                            </Button>
                                        </PermissionGate>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            ) : null}
        </AdminPageShell>
    );
}
