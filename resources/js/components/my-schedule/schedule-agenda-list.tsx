import { format, isToday, isValid, parse, startOfDay } from 'date-fns';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import type { AttendanceRecord } from '@/lib/api/modules/attendance';
import type { WorkingCalendarDay } from '@/lib/api/modules/shifts';
import { dateFnsLocale, formatPunchTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import {
    leaveCoverageLabel,
    primaryAttendance,
    restLabel,
    shiftNamesLabel,
    shiftWindowsLabel,
} from './schedule-day-helpers';

type Props = {
    days: WorkingCalendarDay[];
    attendanceByDate: Map<string, AttendanceRecord[]>;
    onSelectDate: (date: string) => void;
};

function parseYmd(value: string) {
    const parsed = parse(value, 'yyyy-MM-dd', new Date());

    return isValid(parsed) ? parsed : undefined;
}

export function ScheduleAgendaList({
    days,
    attendanceByDate,
    onSelectDate,
}: Props) {
    const { t, i18n } = useTranslation(['shifts', 'common']);
    const locale = dateFnsLocale(i18n.language);
    const upcoming = useMemo(() => {
        const todayStart = startOfDay(new Date());

        return days
            .filter((day) => {
                const date = parseYmd(day.date);

                return date ? date >= todayStart : false;
            })
            .sort((a, b) => a.date.localeCompare(b.date));
    }, [days]);

    return (
        <div className="space-y-3">
            <h2 className="text-sm font-semibold tracking-tight">
                {t('my_schedule.agenda_title')}
            </h2>

            {upcoming.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('my_schedule.no_upcoming')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {upcoming.map((day) => {
                        const date = parseYmd(day.date);
                        const dayIsToday = date ? isToday(date) : false;
                        const dateLabel = date
                            ? format(date, 'EEE d MMM', { locale })
                            : day.date;
                        const attendance = primaryAttendance(
                            attendanceByDate.get(day.date),
                        );
                        const rest = restLabel(day, t);
                        const isLate = Boolean(
                            attendance && attendance.late_minutes > 0,
                        );

                        return (
                            <li key={day.date}>
                                <button
                                    type="button"
                                    onClick={() => onSelectDate(day.date)}
                                    className={cn(
                                        'flex min-h-11 w-full items-start justify-between gap-3 rounded-lg border border-border bg-card px-3 py-3 text-left shadow-sm transition-colors active:bg-muted/50',
                                        dayIsToday &&
                                            'border-primary/40 bg-primary/5',
                                        day.leave &&
                                            !dayIsToday &&
                                            'border-amber-500/30 bg-amber-500/5',
                                    )}
                                >
                                    <div className="min-w-0 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-sm font-medium capitalize">
                                                {dateLabel}
                                            </span>
                                            {dayIsToday ? (
                                                <Badge variant="outline">
                                                    {t('my_schedule.today')}
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {shiftNamesLabel(day)
                                                ? shiftWindowsLabel(day)
                                                    ? `${shiftNamesLabel(day)} · ${shiftWindowsLabel(day)}`
                                                    : shiftNamesLabel(day)
                                                : rest
                                                  ? rest
                                                  : t('my_schedule.no_shift')}
                                        </p>
                                        {day.leave ? (
                                            <p className="truncate text-xs text-amber-800 dark:text-amber-200">
                                                {day.leave.leave_type_name ||
                                                    t('my_schedule.on_leave')}
                                                {' · '}
                                                {leaveCoverageLabel(
                                                    day.leave,
                                                    t,
                                                )}
                                            </p>
                                        ) : null}
                                    </div>

                                    <div className="shrink-0 text-right text-xs text-muted-foreground tabular-nums">
                                        {attendance ? (
                                            <p
                                                className={cn(
                                                    isLate &&
                                                        'font-medium text-orange-700 dark:text-orange-300',
                                                )}
                                            >
                                                {formatPunchTime(
                                                    attendance.check_in_at,
                                                )}
                                                –
                                                {attendance.check_out_at
                                                    ? formatPunchTime(
                                                          attendance.check_out_at,
                                                      )
                                                    : t(
                                                          'my_schedule.attendance_open',
                                                      )}
                                            </p>
                                        ) : null}
                                    </div>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
