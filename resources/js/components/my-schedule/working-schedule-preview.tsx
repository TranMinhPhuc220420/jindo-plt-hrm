import { Link } from '@inertiajs/react';
import {
    addMonths,
    endOfMonth,
    format,
    startOfMonth,
    subMonths,
} from 'date-fns';
import { useCallback, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MonthSummaryStrip } from '@/components/attendance/month-summary-strip';
import { ScheduleAgendaList } from '@/components/my-schedule/schedule-agenda-list';
import {
    indexDaysByDate,
    primaryAttendance,
} from '@/components/my-schedule/schedule-day-helpers';
import { ScheduleDaySheet } from '@/components/my-schedule/schedule-day-sheet';
import { ScheduleMonthCalendar } from '@/components/my-schedule/schedule-month-calendar';
import { ScheduleTable } from '@/components/my-schedule/schedule-table';
import { ScheduleToolbar } from '@/components/my-schedule/schedule-toolbar';
import {
    readStoredScheduleView,
    storeScheduleView,
} from '@/components/my-schedule/schedule-view';
import type { ScheduleViewMode } from '@/components/my-schedule/schedule-view';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { useIsMobile } from '@/hooks/use-mobile';
import { ApiError } from '@/lib/api/errors';
import * as attendanceApi from '@/lib/api/modules/attendance';
import type {
    AttendanceRecord,
    AttendanceSummary,
} from '@/lib/api/modules/attendance';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { WorkingCalendarDay } from '@/lib/api/modules/shifts';
import { dateFnsLocale, formatDateString } from '@/lib/datetime';

function defaultWeekRange(): { from: string; to: string } {
    const now = new Date();
    const from = new Date(now);
    from.setDate(now.getDate() - ((now.getDay() + 6) % 7));
    const to = new Date(from);
    to.setDate(from.getDate() + 6);

    return {
        from: formatDateString(from),
        to: formatDateString(to),
    };
}

function monthRange(month: Date): { from: string; to: string } {
    return {
        from: formatDateString(startOfMonth(month)),
        to: formatDateString(endOfMonth(month)),
    };
}

type Props = {
    employeeId: number;
    /** Bump to force reload after assignment changes. */
    refreshKey?: number;
    emptyMessage?: string;
};

export function WorkingSchedulePreview({
    employeeId,
    refreshKey = 0,
    emptyMessage,
}: Props) {
    const { t, i18n } = useTranslation(['shifts', 'common']);
    const locale = dateFnsLocale(i18n.language);
    const isMobile = useIsMobile();

    const [view, setView] = useState<ScheduleViewMode>(() =>
        readStoredScheduleView(),
    );
    const [visibleMonth, setVisibleMonth] = useState(() =>
        startOfMonth(new Date()),
    );
    const weekInitial = useMemo(() => defaultWeekRange(), []);
    const [dateFrom, setDateFrom] = useState(weekInitial.from);
    const [dateTo, setDateTo] = useState(weekInitial.to);
    const [days, setDays] = useState<WorkingCalendarDay[]>([]);
    const [attendanceByDate, setAttendanceByDate] = useState<
        Map<string, AttendanceRecord[]>
    >(() => new Map());
    const [summary, setSummary] = useState<AttendanceSummary | null>(null);
    const [summaryLoading, setSummaryLoading] = useState(false);
    const [summaryError, setSummaryError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedDate, setSelectedDate] = useState<string | null>(null);

    const queryRange = useMemo(() => {
        if (view === 'calendar') {
            return monthRange(visibleMonth);
        }

        return { from: dateFrom, to: dateTo };
    }, [view, visibleMonth, dateFrom, dateTo]);

    const daysByDate = useMemo(() => indexDaysByDate(days), [days]);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        setSummaryLoading(true);
        setSummaryError(null);

        try {
            const calendarDays = await shiftApi.getWorkingCalendar({
                employee_id: employeeId,
                date_from: queryRange.from,
                date_to: queryRange.to,
            });
            setDays(calendarDays);

            const [attendanceResult, summaryResult] = await Promise.allSettled([
                attendanceApi.listRecords({
                    employee_id: employeeId,
                    date_from: queryRange.from,
                    date_to: queryRange.to,
                    per_page: 100,
                }),
                attendanceApi.getSummary({
                    employee_id: employeeId,
                    period_start: queryRange.from,
                    period_end: queryRange.to,
                }),
            ]);

            if (attendanceResult.status === 'fulfilled') {
                const map = new Map<string, AttendanceRecord[]>();

                for (const row of attendanceResult.value.data) {
                    const list = map.get(row.work_date) ?? [];
                    list.push(row);
                    map.set(row.work_date, list);
                }

                setAttendanceByDate(map);
            } else {
                setAttendanceByDate(new Map());
            }

            if (summaryResult.status === 'fulfilled') {
                setSummary(summaryResult.value);
                setSummaryError(null);
            } else {
                setSummary(null);
                setSummaryError(t('my_schedule.summary_error'));
            }
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('my_schedule.error_load'),
            );
            setDays([]);
            setAttendanceByDate(new Map());
            setSummary(null);
            setSummaryError(null);
        } finally {
            setLoading(false);
            setSummaryLoading(false);
        }
    }, [employeeId, queryRange.from, queryRange.to, t]);

    useLoadEffect(load, [load, refreshKey]);

    const handleViewChange = (next: ScheduleViewMode) => {
        setView(next);
        storeScheduleView(next);

        if (next === 'calendar') {
            setVisibleMonth(startOfMonth(new Date()));
        }
    };

    const handleSelectDate = (date: string) => {
        setSelectedDate(date);
    };

    const monthLabel = format(visibleMonth, 'MMMM yyyy', { locale });

    return (
        <div className="space-y-4">
            <div className="flex justify-end">
                <Link
                    href="/attendance"
                    className="inline-flex min-h-11 items-center text-sm text-primary underline-offset-4 hover:underline"
                >
                    {t('my_schedule.view_attendance')}
                </Link>
            </div>

            <MonthSummaryStrip
                summary={summary}
                loading={summaryLoading}
                error={summaryError}
            />

            <ScheduleToolbar
                view={view}
                onViewChange={handleViewChange}
                dateFrom={dateFrom}
                dateTo={dateTo}
                onRangeChange={({ from, to }) => {
                    setDateFrom(from);
                    setDateTo(to);
                }}
                monthLabel={monthLabel}
                onPrevMonth={() =>
                    setVisibleMonth((m) => startOfMonth(subMonths(m, 1)))
                }
                onNextMonth={() =>
                    setVisibleMonth((m) => startOfMonth(addMonths(m, 1)))
                }
                onGoToday={() => setVisibleMonth(startOfMonth(new Date()))}
                onSearch={() => void load()}
            />

            {loading ? (
                <LoadingState label={t('my_schedule.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : view === 'calendar' ? (
                <div className="space-y-6">
                    <ScheduleMonthCalendar
                        month={visibleMonth}
                        days={days}
                        attendanceByDate={attendanceByDate}
                        onSelectDate={handleSelectDate}
                    />
                    {isMobile ? (
                        <ScheduleAgendaList
                            days={days}
                            attendanceByDate={attendanceByDate}
                            onSelectDate={handleSelectDate}
                        />
                    ) : null}
                </div>
            ) : days.length === 0 ? (
                <EmptyState message={emptyMessage ?? t('my_schedule.empty')} />
            ) : (
                <ScheduleTable
                    days={days}
                    attendanceByDate={attendanceByDate}
                    onSelectDate={handleSelectDate}
                />
            )}

            <ScheduleDaySheet
                open={selectedDate !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedDate(null);
                    }
                }}
                date={selectedDate}
                entry={selectedDate ? daysByDate.get(selectedDate) : undefined}
                attendance={
                    selectedDate
                        ? primaryAttendance(attendanceByDate.get(selectedDate))
                        : undefined
                }
            />
        </div>
    );
}
