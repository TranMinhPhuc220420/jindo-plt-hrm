import { Link } from '@inertiajs/react';
import { format, isValid, parse } from 'date-fns';
import { useCallback, useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { AttendanceStatusBadge } from '@/components/attendance/attendance-status-badge';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DateTimePicker } from '@/components/shared/date-time-picker';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as attendanceApi from '@/lib/api/modules/attendance';
import type {
    AttendanceCorrection,
    AttendanceRecord,
} from '@/lib/api/modules/attendance';
import { useAuth } from '@/lib/auth/auth-context';
import { dateFnsLocale, formatPunchTime, toApiDateTime } from '@/lib/datetime';

function readRecordIdFromQuery(): string {
    if (typeof window === 'undefined') {
        return '';
    }

    const value = new URLSearchParams(window.location.search).get('record_id');

    return value && /^\d+$/.test(value) ? value : '';
}

function recordLabel(
    record: AttendanceRecord,
    locale: ReturnType<typeof dateFnsLocale>,
): string {
    const parsed = parse(record.work_date, 'yyyy-MM-dd', new Date());
    const datePart = isValid(parsed)
        ? format(parsed, 'EEE, d MMM yyyy', { locale })
        : record.work_date;
    const employeePart = record.employee
        ? `${record.employee.full_name} (${record.employee.code})`
        : `#${record.employee_id}`;

    return `${employeePart} · ${datePart} · ${formatPunchTime(record.check_in_at)}–${formatPunchTime(record.check_out_at)}`;
}

export default function AttendanceCorrectionsPage() {
    const { t, i18n } = useTranslation(['attendance', 'common']);
    const { employeeId, can } = useAuth();
    const canViewOthers =
        can('can_approve_attendance') || can('can_manage_attendance');
    const locale = dateFnsLocale(i18n.language);
    const [rows, setRows] = useState<AttendanceCorrection[]>([]);
    const [records, setRecords] = useState<AttendanceRecord[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [recordId, setRecordId] = useState(() => readRecordIdFromQuery());
    const [proposedIn, setProposedIn] = useState('');
    const [proposedOut, setProposedOut] = useState('');
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [corrections, recent] = await Promise.all([
                attendanceApi.listCorrections({ per_page: 50 }),
                attendanceApi.listRecords({
                    employee_id: canViewOthers
                        ? undefined
                        : (employeeId ?? undefined),
                    per_page: 60,
                }),
            ]);
            setRows(corrections.data);
            setRecords(recent.data);
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('corrections.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [employeeId, canViewOthers, t]);

    useLoadEffect(load, [load]);

    const selectedExists = useMemo(
        () => records.some((row) => String(row.id) === recordId),
        [records, recordId],
    );

    async function handleRequest(event: FormEvent) {
        event.preventDefault();

        if (!recordId) {
            return;
        }

        setSubmitting(true);

        try {
            await attendanceApi.requestCorrection({
                attendance_record_id: Number(recordId),
                proposed_check_in_at: toApiDateTime(proposedIn),
                proposed_check_out_at: toApiDateTime(proposedOut),
                reason,
            });
            toast.success(t('corrections.toast_requested'));
            setRecordId('');
            setProposedIn('');
            setProposedOut('');
            setReason('');
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('corrections.toast_error'),
            );
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <AdminPageShell
            title={t('corrections.title')}
            description={t('corrections.description')}
            permission="can_view_attendance"
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/attendance">{t('corrections.back')}</Link>
                </Button>
            </div>

            <PermissionGate permission="can_request_attendance_correction">
                <form
                    onSubmit={handleRequest}
                    className="mb-8 grid max-w-xl gap-3"
                >
                    <h2 className="text-lg font-medium">
                        {t('corrections.request')}
                    </h2>
                    <div className="space-y-1">
                        <Label htmlFor="record_select">
                            {t('corrections.record')}
                        </Label>
                        {records.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('corrections.no_records')}
                            </p>
                        ) : (
                            <Select
                                value={selectedExists ? recordId : undefined}
                                onValueChange={setRecordId}
                            >
                                <SelectTrigger
                                    id="record_select"
                                    className="w-full"
                                >
                                    <SelectValue
                                        placeholder={t(
                                            'corrections.record_placeholder',
                                        )}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {records.map((record) => (
                                        <SelectItem
                                            key={record.id}
                                            value={String(record.id)}
                                        >
                                            {recordLabel(record, locale)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="space-y-1">
                            <Label htmlFor="proposed_in">
                                {t('corrections.proposed_in')}
                            </Label>
                            <DateTimePicker
                                id="proposed_in"
                                value={proposedIn}
                                onChange={setProposedIn}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="proposed_out">
                                {t('corrections.proposed_out')}
                            </Label>
                            <DateTimePicker
                                id="proposed_out"
                                value={proposedOut}
                                onChange={setProposedOut}
                            />
                        </div>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="reason">
                            {t('corrections.reason')}
                        </Label>
                        <Input
                            id="reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            required
                        />
                    </div>
                    <Button
                        type="submit"
                        disabled={
                            submitting || !recordId || records.length === 0
                        }
                    >
                        {t('corrections.submit')}
                    </Button>
                </form>
            </PermissionGate>

            <h2 className="mb-3 text-lg font-medium">
                {t('corrections.list_title')}
            </h2>

            {loading ? (
                <LoadingState label={t('corrections.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : rows.length === 0 ? (
                <EmptyState message={t('corrections.empty')} />
            ) : (
                <ul className="space-y-3">
                    {rows.map((row) => {
                        const workDate = row.record?.work_date;
                        const dateLabel = workDate
                            ? (() => {
                                  const parsed = parse(
                                      workDate,
                                      'yyyy-MM-dd',
                                      new Date(),
                                  );

                                  return isValid(parsed)
                                      ? format(parsed, 'EEE, d MMM yyyy', {
                                            locale,
                                        })
                                      : workDate;
                              })()
                            : `#${row.attendance_record_id}`;
                        const employee =
                            row.employee ?? row.record?.employee ?? null;
                        const employeeLabel = employee
                            ? `${employee.full_name} (${employee.code})`
                            : `#${row.employee_id}`;

                        return (
                            <li
                                key={row.id}
                                className="rounded-lg border px-4 py-3 text-sm"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium">
                                                {employeeLabel}
                                            </span>
                                            <span className="text-muted-foreground capitalize">
                                                {dateLabel}
                                            </span>
                                            <AttendanceStatusBadge
                                                status={row.status}
                                            />
                                        </div>
                                        <p className="text-muted-foreground tabular-nums">
                                            {t('corrections.col_proposed')}:{' '}
                                            {formatPunchTime(
                                                row.proposed_check_in_at,
                                            )}
                                            {' → '}
                                            {formatPunchTime(
                                                row.proposed_check_out_at,
                                            )}
                                        </p>
                                        <p>{row.reason}</p>
                                    </div>
                                    {row.status === 'pending' ? (
                                        <PermissionGate permission="can_approve_attendance">
                                            <span className="flex gap-2">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    onClick={() =>
                                                        void attendanceApi
                                                            .approveCorrection(
                                                                row.id,
                                                            )
                                                            .then(() => {
                                                                toast.success(
                                                                    t(
                                                                        'corrections.toast_approved',
                                                                    ),
                                                                );

                                                                return load();
                                                            })
                                                            .catch((err) =>
                                                                toast.error(
                                                                    err instanceof
                                                                        ApiError
                                                                        ? err.message
                                                                        : t(
                                                                              'corrections.toast_error',
                                                                          ),
                                                                ),
                                                            )
                                                    }
                                                >
                                                    {t('corrections.approve')}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="secondary"
                                                    onClick={() =>
                                                        void attendanceApi
                                                            .rejectCorrection(
                                                                row.id,
                                                            )
                                                            .then(() => {
                                                                toast.success(
                                                                    t(
                                                                        'corrections.toast_rejected',
                                                                    ),
                                                                );

                                                                return load();
                                                            })
                                                            .catch((err) =>
                                                                toast.error(
                                                                    err instanceof
                                                                        ApiError
                                                                        ? err.message
                                                                        : t(
                                                                              'corrections.toast_error',
                                                                          ),
                                                                ),
                                                            )
                                                    }
                                                >
                                                    {t('corrections.reject')}
                                                </Button>
                                            </span>
                                        </PermissionGate>
                                    ) : null}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </AdminPageShell>
    );
}
