import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    AttendancePeriodFilter,
    rangeForPreset,
} from '@/components/attendance/attendance-period-filter';
import type { AttendancePeriodPreset } from '@/components/attendance/attendance-period-filter';
import { AttendanceRecordSheet } from '@/components/attendance/attendance-record-sheet';
import { AttendanceRecordsTable } from '@/components/attendance/attendance-records-table';
import { MonthSummaryStrip } from '@/components/attendance/month-summary-strip';
import { PunchEvidenceDialog } from '@/components/attendance/punch-evidence-dialog';
import type { PunchEvidencePayload } from '@/components/attendance/punch-evidence-dialog';
import { PunchPendingSyncBanner } from '@/components/attendance/punch-pending-sync-banner';
import { TodayStatusCard } from '@/components/attendance/today-status-card';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useLoadEffect } from '@/hooks/use-load-effect';
import {
    ApiError,
    classifyPunchError,
    isRetryablePunchError,
    punchErrorToastKey,
} from '@/lib/api/errors';
import * as attendanceApi from '@/lib/api/modules/attendance';
import type {
    AttendanceRecord,
    AttendanceSummary,
} from '@/lib/api/modules/attendance';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { WorkingCalendarDay } from '@/lib/api/modules/shifts';
import * as punchQueue from '@/lib/attendance/punch-queue';
import type { PendingPunch } from '@/lib/attendance/punch-queue';
import { useAuth } from '@/lib/auth/auth-context';
import { formatDateString } from '@/lib/datetime';

function todayIso(): string {
    return formatDateString(new Date());
}

function yesterdayIso(): string {
    const d = new Date();
    d.setDate(d.getDate() - 1);

    return formatDateString(d);
}

function isOpenSession(row: AttendanceRecord | null | undefined): boolean {
    return !!row?.check_in_at && !row.check_out_at;
}

export default function AttendanceIndexPage() {
    const { t } = useTranslation(['attendance', 'common']);
    const { employeeId, can } = useAuth();
    const canViewOthers =
        can('can_approve_attendance') || can('can_manage_attendance');
    const canApprove = can('can_approve_attendance');
    const canSeeCorrections =
        canViewOthers || can('can_request_attendance_correction');
    const initial = rangeForPreset('today');
    const [periodPreset, setPeriodPreset] =
        useState<AttendancePeriodPreset>('today');
    const [dateFrom, setDateFrom] = useState(initial.from);
    const [dateTo, setDateTo] = useState(initial.to);
    const [records, setRecords] = useState<AttendanceRecord[]>([]);
    const [pendingCorrectionCounts, setPendingCorrectionCounts] = useState<
        Record<number, number>
    >({});
    const [todayRecord, setTodayRecord] = useState<AttendanceRecord | null>(
        null,
    );
    const [hasShiftToday, setHasShiftToday] = useState<boolean | null>(null);
    const [summary, setSummary] = useState<AttendanceSummary | null>(null);
    const [loading, setLoading] = useState(true);
    const [summaryLoading, setSummaryLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [summaryError, setSummaryError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [bulkBusy, setBulkBusy] = useState(false);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [confirmBulkOpen, setConfirmBulkOpen] = useState(false);
    const [punchMode, setPunchMode] = useState<'check_in' | 'check_out' | null>(
        null,
    );
    const [selectedRecordId, setSelectedRecordId] = useState<number | null>(
        null,
    );
    const [pendingPunches, setPendingPunches] = useState<PendingPunch[]>([]);
    const [syncingQueue, setSyncingQueue] = useState(false);
    const syncingRef = useRef(false);

    const refreshPending = useCallback(async () => {
        try {
            setPendingPunches(await punchQueue.listPending());
        } catch {
            // IndexedDB unavailable — punch still works online-only.
        }
    }, []);

    const loadToday = useCallback(async () => {
        if (!employeeId) {
            setTodayRecord(null);
            setHasShiftToday(null);

            return null as AttendanceRecord | null;
        }

        const day = todayIso();
        const yesterday = yesterdayIso();

        try {
            const [result, calendar] = await Promise.all([
                attendanceApi.listRecords({
                    employee_id: employeeId,
                    date_from: yesterday,
                    date_to: day,
                    per_page: 10,
                }),
                shiftApi
                    .getWorkingCalendar({
                        employee_id: employeeId,
                        date_from: day,
                        date_to: day,
                    })
                    .catch((): WorkingCalendarDay[] | null => null),
            ]);

            if (calendar === null) {
                setHasShiftToday(true);
            } else {
                const todayCal = calendar.find((row) => row.date === day);
                setHasShiftToday(
                    todayCal != null && todayCal.shift_id != null,
                );
            }
            const todayRow =
                result.data.find((item) => item.work_date === day) ?? null;

            if (todayRow) {
                setTodayRecord(todayRow);

                return todayRow;
            }

            // Post-midnight: still show yesterday's open session so Check-out stays available.
            const openYesterday =
                result.data.find(
                    (item) =>
                        item.work_date === yesterday && isOpenSession(item),
                ) ?? null;

            setTodayRecord(openYesterday);

            return openYesterday;
        } catch {
            return null;
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

    useLoadEffect(loadRecords, [loadRecords]);

    useLoadEffect(loadSummary, [loadSummary]);

    useLoadEffect(() => {
        void loadToday();
    }, [loadToday]);

    useEffect(() => {
        const dayRef = { current: todayIso() };

        function maybeRefreshToday() {
            const next = todayIso();

            if (next !== dayRef.current) {
                dayRef.current = next;
                void loadToday();
            }
        }

        const id = window.setInterval(maybeRefreshToday, 30_000);

        function onVisibility() {
            if (document.visibilityState === 'visible') {
                maybeRefreshToday();
            }
        }

        document.addEventListener('visibilitychange', onVisibility);

        return () => {
            window.clearInterval(id);
            document.removeEventListener('visibilitychange', onVisibility);
        };
    }, [loadToday]);

    const refreshAll = useCallback(async () => {
        await Promise.all([loadRecords(), loadSummary(), loadToday()]);
    }, [loadRecords, loadSummary, loadToday]);

    const syncPendingPunches = useCallback(async () => {
        if (syncingRef.current) {
            return;
        }

        if (typeof navigator !== 'undefined' && !navigator.onLine) {
            await refreshPending();

            return;
        }

        syncingRef.current = true;
        setSyncingQueue(true);

        try {
            const result = await punchQueue.drain(async (row) => {
                const photo = punchQueue.toPunchFile(row);
                const payload = {
                    latitude: row.latitude,
                    longitude: row.longitude,
                    accuracy_meters: row.accuracy_meters,
                    address: row.address,
                    photo,
                    captured_at: row.captured_at,
                    idempotencyKey: row.idempotencyKey,
                };

                try {
                    if (row.mode === 'check_in') {
                        await attendanceApi.checkIn(payload);
                    } else {
                        await attendanceApi.checkOut(payload);
                    }
                } catch (err) {
                    if (
                        err instanceof ApiError &&
                        (err.errorCode === 'ATTENDANCE_ALREADY_CHECKED_IN' ||
                            err.errorCode === 'ATTENDANCE_INVALID_TRANSITION')
                    ) {
                        const today = await loadToday();

                        if (row.mode === 'check_in' && today?.check_in_at) {
                            return;
                        }

                        if (row.mode === 'check_out' && today?.check_out_at) {
                            return;
                        }
                    }

                    throw err;
                }
            });

            await refreshPending();

            if (result.synced > 0) {
                toast.success(t('index.toast_synced'));
                await refreshAll();
            }
        } catch {
            await refreshPending();
        } finally {
            syncingRef.current = false;
            setSyncingQueue(false);
        }
    }, [refreshPending, loadToday, refreshAll, t]);

    useLoadEffect(() => {
        void refreshPending();
        void syncPendingPunches();
    }, [refreshPending, syncPendingPunches]);

    useEffect(() => {
        function onOnline() {
            void syncPendingPunches();
        }

        window.addEventListener('online', onOnline);

        return () => window.removeEventListener('online', onOnline);
    }, [syncPendingPunches]);

    function applyPeriod(
        preset: AttendancePeriodPreset,
        range: { from: string; to: string },
    ) {
        setPeriodPreset(preset);
        setDateFrom(range.from);
        setDateTo(range.to);
        setSelectedIds(new Set());
    }

    const pendingRecordIds = useMemo(
        () =>
            records
                .filter((row) => row.status === 'pending')
                .map((row) => row.id),
        [records],
    );

    const activeSelectedIds = useMemo(() => {
        if (selectedIds.size === 0) {
            return selectedIds;
        }

        const pendingSet = new Set(pendingRecordIds);
        const next = new Set<number>();

        for (const id of selectedIds) {
            if (pendingSet.has(id)) {
                next.add(id);
            }
        }

        return next.size === selectedIds.size ? selectedIds : next;
    }, [selectedIds, pendingRecordIds]);

    function toggleSelect(id: number) {
        setSelectedIds((prev) => {
            const next = new Set(prev);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    }

    function toggleSelectAllPending() {
        setSelectedIds((prev) => {
            const allSelected =
                pendingRecordIds.length > 0 &&
                pendingRecordIds.every((id) => prev.has(id));

            if (allSelected) {
                return new Set();
            }

            return new Set(pendingRecordIds);
        });
    }

    function clearSelection() {
        setSelectedIds(new Set());
    }

    const selectedCorrectionWarnCount = useMemo(() => {
        let count = 0;

        for (const id of activeSelectedIds) {
            if ((pendingCorrectionCounts[id] ?? 0) > 0) {
                count += 1;
            }
        }

        return count;
    }, [activeSelectedIds, pendingCorrectionCounts]);

    async function runBulkApprove(ids: number[]) {
        if (ids.length === 0 || bulkBusy) {
            return;
        }

        setBulkBusy(true);

        try {
            const result = await attendanceApi.bulkApproveRecords(ids);
            toast.success(
                t('index.toast_bulk_approved', {
                    count: result.approved_count,
                }),
            );
            setSelectedIds(new Set());
            setConfirmBulkOpen(false);
            await loadRecords();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('index.toast_error'),
            );
        } finally {
            setBulkBusy(false);
        }
    }

    function requestBulkApprove() {
        const ids = [...activeSelectedIds];

        if (ids.length === 0) {
            return;
        }

        if (ids.length >= 2) {
            setConfirmBulkOpen(true);

            return;
        }

        void runBulkApprove(ids);
    }

    async function queuePunch(
        mode: 'check_in' | 'check_out',
        payload: PunchEvidencePayload,
        idempotencyKey: string,
    ) {
        await punchQueue.enqueue({
            mode,
            idempotencyKey,
            latitude: payload.latitude,
            longitude: payload.longitude,
            accuracy_meters: payload.accuracy_meters,
            address: payload.address,
            captured_at: payload.captured_at,
            photo: payload.photo,
        });
        await refreshPending();
    }

    async function handlePunchSubmit(payload: PunchEvidencePayload) {
        if (!punchMode) {
            return;
        }

        const mode = punchMode;
        const idempotencyKey = crypto.randomUUID();

        setBusy(true);

        try {
            if (typeof navigator !== 'undefined' && !navigator.onLine) {
                await queuePunch(mode, payload, idempotencyKey);
                toast.message(t('index.toast_queued'));
                setPunchMode(null);

                return;
            }

            const request = {
                ...payload,
                idempotencyKey,
            };

            if (mode === 'check_in') {
                await attendanceApi.checkIn(request);
                toast.success(t('index.toast_in'));
            } else {
                await attendanceApi.checkOut(request);
                toast.success(t('index.toast_out'));
            }

            setPunchMode(null);
            await refreshAll();
        } catch (err) {
            if (isRetryablePunchError(err)) {
                try {
                    await queuePunch(mode, payload, idempotencyKey);
                    const kind = classifyPunchError(err);
                    toast.error(t(punchErrorToastKey(kind)));
                    setPunchMode(null);
                } catch {
                    toast.error(t('index.queue_full'));
                }

                return;
            }

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
            toast.success(t('index.toast_approved'));
            setSelectedIds((prev) => {
                if (!prev.has(id)) {
                    return prev;
                }

                const next = new Set(prev);
                next.delete(id);

                return next;
            });
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

    const pendingCheckIn = pendingPunches.some(
        (row) => row.mode === 'check_in',
    );
    const pendingCheckOut = pendingPunches.some(
        (row) => row.mode === 'check_out',
    );
    const allPendingSelected =
        pendingRecordIds.length > 0 &&
        pendingRecordIds.every((id) => activeSelectedIds.has(id));
    const showBulkBar = canApprove && activeSelectedIds.size > 0;

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            permission={permission}
        >
            <PunchPendingSyncBanner
                pending={pendingPunches}
                syncing={syncingQueue}
                onSync={() => void syncPendingPunches()}
            />

            <TodayStatusCard
                employeeId={employeeId}
                today={todayRecord}
                busy={busy || syncingQueue}
                pendingCheckIn={pendingCheckIn}
                pendingCheckOut={pendingCheckOut}
                hasShiftToday={hasShiftToday}
                onCheckIn={() => setPunchMode('check_in')}
                onCheckOut={() => setPunchMode('check_out')}
            />

            <PunchEvidenceDialog
                open={punchMode !== null}
                mode={punchMode ?? 'check_in'}
                busy={busy}
                onOpenChange={(open) => {
                    if (!open && !busy) {
                        setPunchMode(null);
                    }
                }}
                onSubmit={handlePunchSubmit}
            />

            <AttendancePeriodFilter
                preset={periodPreset}
                dateFrom={dateFrom}
                dateTo={dateTo}
                onPresetChange={applyPeriod}
                onCustomRangeChange={(range) => {
                    applyPeriod('custom', range);
                }}
            />

            <MonthSummaryStrip
                summary={summary}
                loading={summaryLoading}
                error={summaryError}
            />

            <h2 className="mb-2 text-lg font-medium">{t('index.recent')}</h2>

            {showBulkBar ? (
                <div className="mb-3 flex flex-wrap items-center gap-2 rounded-md border border-border bg-muted/40 px-3 py-2">
                    <p className="mr-auto text-sm font-medium">
                        {t('index.selected_count', {
                            count: activeSelectedIds.size,
                        })}
                    </p>
                    {!allPendingSelected ? (
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={bulkBusy || pendingRecordIds.length === 0}
                            onClick={toggleSelectAllPending}
                        >
                            {t('index.select_all_pending')}
                        </Button>
                    ) : null}
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        disabled={bulkBusy}
                        onClick={clearSelection}
                    >
                        {t('index.clear_selection')}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        disabled={bulkBusy}
                        onClick={requestBulkApprove}
                    >
                        {bulkBusy
                            ? t('index.bulk_approving')
                            : t('index.bulk_approve')}
                    </Button>
                </div>
            ) : canApprove && pendingRecordIds.length > 0 && !loading ? (
                <div className="mb-3 flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={toggleSelectAllPending}
                    >
                        {t('index.select_all_pending')}
                    </Button>
                </div>
            ) : null}

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
                    onSelectRecord={setSelectedRecordId}
                    canApprove={canApprove}
                    selectedIds={activeSelectedIds}
                    onToggleSelect={toggleSelect}
                    onToggleSelectAllPending={toggleSelectAllPending}
                />
            )}

            <Dialog open={confirmBulkOpen} onOpenChange={setConfirmBulkOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('index.bulk_approve_confirm_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('index.bulk_approve_confirm', {
                                count: activeSelectedIds.size,
                            })}
                            {selectedCorrectionWarnCount > 0
                                ? ` ${t('index.bulk_approve_correction_warn', {
                                      count: selectedCorrectionWarnCount,
                                  })}`
                                : null}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={bulkBusy}
                            onClick={() => setConfirmBulkOpen(false)}
                        >
                            {t('cancel', { ns: 'common' })}
                        </Button>
                        <Button
                            type="button"
                            disabled={bulkBusy}
                            onClick={() =>
                                void runBulkApprove([...activeSelectedIds])
                            }
                        >
                            {bulkBusy
                                ? t('index.bulk_approving')
                                : t('index.bulk_approve')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <AttendanceRecordSheet
                open={selectedRecordId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedRecordId(null);
                    }
                }}
                record={
                    selectedRecordId != null
                        ? (records.find((row) => row.id === selectedRecordId) ??
                          null)
                        : null
                }
                pendingCount={
                    selectedRecordId != null
                        ? (pendingCorrectionCounts[selectedRecordId] ?? 0)
                        : 0
                }
                onApprove={(id) => void handleApprove(id)}
            />
        </AdminPageShell>
    );
}
