import { endOfMonth, startOfMonth } from 'date-fns';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { AttendanceRecordsTable } from '@/components/attendance/attendance-records-table';
import { MonthSummaryStrip } from '@/components/attendance/month-summary-strip';
import { TodayStatusCard } from '@/components/attendance/today-status-card';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as attendanceApi from '@/lib/api/modules/attendance';
import type {
    AttendanceRecord,
    AttendanceSummary,
} from '@/lib/api/modules/attendance';
import { useAuth } from '@/lib/auth/auth-context';
import { formatDateString } from '@/lib/datetime';

function todayIso(): string {
    return formatDateString(new Date());
}

function defaultMonthRange(): { from: string; to: string } {
    const now = new Date();

    return {
        from: formatDateString(startOfMonth(now)),
        to: formatDateString(endOfMonth(now)),
    };
}

export default function AttendanceIndexPage() {
    const { t } = useTranslation(['attendance', 'common']);
    const { employeeId, can } = useAuth();
    const canViewOthers =
        can('can_approve_attendance') || can('can_manage_attendance');
    const canSeeCorrections =
        canViewOthers || can('can_request_attendance_correction');
    const initial = defaultMonthRange();
    const [dateFrom, setDateFrom] = useState(initial.from);
    const [dateTo, setDateTo] = useState(initial.to);
    const [records, setRecords] = useState<AttendanceRecord[]>([]);
    const [pendingCorrectionCounts, setPendingCorrectionCounts] = useState<
        Record<number, number>
    >({});
    const [todayRecord, setTodayRecord] = useState<AttendanceRecord | null>(
        null,
    );
    const [summary, setSummary] = useState<AttendanceSummary | null>(null);
    const [loading, setLoading] = useState(true);
    const [summaryLoading, setSummaryLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [summaryError, setSummaryError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const loadToday = useCallback(async () => {
        if (!employeeId) {
            setTodayRecord(null);

            return;
        }

        const day = todayIso();

        try {
            const result = await attendanceApi.listRecords({
                employee_id: employeeId,
                date_from: day,
                date_to: day,
                per_page: 5,
            });
            setTodayRecord(
                result.data.find((row) => row.work_date === day) ?? null,
            );
        } catch {
            // Hero stays empty; list error handled separately.
        }
    }, [employeeId]);

    const loadRecords = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [result, pending] = await Promise.all([
                attendanceApi.listRecords({
                    employee_id: canViewOthers
                        ? undefined
                        : (employeeId ?? undefined),
                    date_from: dateFrom,
                    date_to: dateTo,
                    per_page: 100,
                }),
                canSeeCorrections
                    ? attendanceApi.listCorrections({
                          status: 'pending',
                          per_page: 100,
                      })
                    : Promise.resolve({
                          data: [] as Awaited<
                              ReturnType<typeof attendanceApi.listCorrections>
                          >['data'],
                      }),
            ]);
            setRecords(result.data);

            const counts: Record<number, number> = {};

            for (const row of pending.data) {
                const key = row.attendance_record_id;
                counts[key] = (counts[key] ?? 0) + 1;
            }

            setPendingCorrectionCounts(counts);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [employeeId, canViewOthers, canSeeCorrections, dateFrom, dateTo, t]);

    const loadSummary = useCallback(async () => {
        if (!employeeId) {
            setSummary(null);
            setSummaryError(null);
            setSummaryLoading(false);

            return;
        }

        setSummaryLoading(true);
        setSummaryError(null);

        try {
            const data = await attendanceApi.getSummary({
                employee_id: employeeId,
                period_start: dateFrom,
                period_end: dateTo,
            });
            setSummary(data);
        } catch (err) {
            setSummary(null);
            setSummaryError(
                err instanceof ApiError
                    ? err.message
                    : t('index.summary_error'),
            );
        } finally {
            setSummaryLoading(false);
        }
    }, [employeeId, dateFrom, dateTo, t]);

    useEffect(() => {
        void loadRecords();
    }, [loadRecords]);

    useEffect(() => {
        void loadSummary();
    }, [loadSummary]);

    useEffect(() => {
        void loadToday();
    }, [loadToday]);

    async function refreshAll() {
        await Promise.all([loadRecords(), loadSummary(), loadToday()]);
    }

    async function handleCheckIn() {
        setBusy(true);

        try {
            await attendanceApi.checkIn();
            toast.success(t('index.toast_in'));
            await refreshAll();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function handleCheckOut() {
        setBusy(true);

        try {
            await attendanceApi.checkOut();
            toast.success(t('index.toast_out'));
            await refreshAll();
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
            await attendanceApi.approveRecord(id);
            await loadRecords();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        }
    }

    const permission = can('can_view_attendance')
        ? 'can_view_attendance'
        : 'can_check_in_out';

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            permission={permission}
        >
            <TodayStatusCard
                employeeId={employeeId}
                today={todayRecord}
                busy={busy}
                onCheckIn={() => void handleCheckIn()}
                onCheckOut={() => void handleCheckOut()}
            />

            <MonthSummaryStrip
                summary={summary}
                loading={summaryLoading}
                error={summaryError}
            />

            <form
                className="mb-4 flex flex-wrap items-end gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    void Promise.all([loadRecords(), loadSummary()]);
                }}
            >
                <div className="space-y-1">
                    <Label htmlFor="attendance_range">
                        {t('index.date_range')}
                    </Label>
                    <DateRangePicker
                        id="attendance_range"
                        from={dateFrom}
                        to={dateTo}
                        onChange={({ from, to }) => {
                            setDateFrom(from);
                            setDateTo(to);
                        }}
                        numberOfMonths={1}
                        className="min-w-[16rem]"
                    />
                </div>
                <Button type="submit" variant="secondary">
                    {t('search', { ns: 'common' })}
                </Button>
            </form>

            <h2 className="mb-2 text-lg font-medium">{t('index.recent')}</h2>

            {loading ? (
                <LoadingState label={t('index.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : records.length === 0 ? (
                <EmptyState message={t('index.empty')} />
            ) : (
                <AttendanceRecordsTable
                    records={records}
                    pendingCorrectionCounts={pendingCorrectionCounts}
                    onApprove={(id) => void handleApprove(id)}
                />
            )}
        </AdminPageShell>
    );
}
