import {
    addMonths,
    endOfMonth,
    format,
    startOfMonth,
    subMonths,
} from 'date-fns';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
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
import { ApiError } from '@/lib/api/errors';
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
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const queryRange = useMemo(() => {
        if (view === 'calendar') {
            return monthRange(visibleMonth);
        }

        return { from: dateFrom, to: dateTo };
    }, [view, visibleMonth, dateFrom, dateTo]);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await shiftApi.getWorkingCalendar({
                employee_id: employeeId,
                date_from: queryRange.from,
                date_to: queryRange.to,
            });
            setDays(data);
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('my_schedule.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [employeeId, queryRange.from, queryRange.to, t]);

    useEffect(() => {
        void load();
    }, [load, refreshKey]);

    const handleViewChange = (next: ScheduleViewMode) => {
        setView(next);
        storeScheduleView(next);

        if (next === 'calendar') {
            setVisibleMonth(startOfMonth(new Date()));
        }
    };

    const monthLabel = format(visibleMonth, 'MMMM yyyy', { locale });

    return (
        <div>
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
                <ScheduleMonthCalendar month={visibleMonth} days={days} />
            ) : days.length === 0 ? (
                <EmptyState message={emptyMessage ?? t('my_schedule.empty')} />
            ) : (
                <ScheduleTable days={days} />
            )}
        </div>
    );
}
