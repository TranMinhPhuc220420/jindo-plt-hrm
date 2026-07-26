import { format, isSameDay, isValid, parse } from 'date-fns';
import { useTranslation } from 'react-i18next';
import { formatDuration } from '@/components/attendance/format-minutes';
import { Badge } from '@/components/ui/badge';
import { useIsMobile } from '@/hooks/use-mobile';
import type { AttendanceRecord } from '@/lib/api/modules/attendance';
import type { WorkingCalendarDay } from '@/lib/api/modules/shifts';
import { dateFnsLocale, formatPunchTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import { leaveCoverageLabel } from './schedule-day-helpers';

type Props = {
    days: WorkingCalendarDay[];
    attendanceByDate: Map<string, AttendanceRecord>;
    onSelectDate?: (date: string) => void;
};

function parseYmd(value: string) {
    const parsed = parse(value, 'yyyy-MM-dd', new Date());

    return isValid(parsed) ? parsed : undefined;
}

export function ScheduleTable({ days, attendanceByDate, onSelectDate }: Props) {
    const { t, i18n } = useTranslation(['shifts', 'common', 'attendance']);
    const locale = dateFnsLocale(i18n.language);
    const today = new Date();
    const isMobile = useIsMobile();

    if (isMobile) {
        return (
            <ul className="space-y-2">
                {days.map((day) => {
                    const date = parseYmd(day.date);
                    const isToday = date ? isSameDay(date, today) : false;
                    const dateLabel = date
                        ? format(date, 'EEE, d MMM yyyy', { locale })
                        : day.date;
                    const attendance = attendanceByDate.get(day.date);
                    const isLate = Boolean(
                        attendance && attendance.late_minutes > 0,
                    );

                    return (
                        <li key={day.date}>
                            <button
                                type="button"
                                onClick={() => onSelectDate?.(day.date)}
                                className={cn(
                                    'flex min-h-11 w-full flex-col gap-2 rounded-lg border border-border bg-card px-3 py-3 text-left shadow-sm transition-colors active:bg-muted/50',
                                    isToday && 'border-primary/40 bg-primary/5',
                                    day.leave &&
                                        !isToday &&
                                        'border-amber-500/30 bg-amber-500/5',
                                    day.rest_kind === 'holiday' &&
                                        !isToday &&
                                        !day.leave &&
                                        'border-rose-500/30 bg-rose-500/5',
                                    day.rest_kind === 'weekend' &&
                                        !isToday &&
                                        !day.leave &&
                                        'border-slate-400/40 bg-slate-500/5',
                                )}
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-medium capitalize">
                                        {dateLabel}
                                    </span>
                                    {isToday ? (
                                        <Badge variant="outline">
                                            {t('my_schedule.today')}
                                        </Badge>
                                    ) : null}
                                </div>

                                <div className="space-y-0.5">
                                    <p className="text-sm font-medium">
                                        {day.shift_name ??
                                            t('empty_value', { ns: 'common' })}
                                    </p>
                                    <p className="text-xs text-muted-foreground tabular-nums">
                                        {day.start_time && day.end_time
                                            ? `${day.start_time} – ${day.end_time}`
                                            : t('empty_value', {
                                                  ns: 'common',
                                              })}
                                    </p>
                                </div>

                                <div className="flex flex-wrap gap-1.5">
                                    {day.rest_kind === 'holiday' ? (
                                        <Badge variant="secondary">
                                            {day.holiday_name ||
                                                t('my_schedule.holiday')}
                                        </Badge>
                                    ) : null}
                                    {day.rest_kind === 'weekend' ? (
                                        <Badge variant="outline">
                                            {t('my_schedule.weekend')}
                                        </Badge>
                                    ) : null}
                                    {day.leave ? (
                                        <Badge variant="outline">
                                            {day.leave.leave_type_name ||
                                                t('my_schedule.on_leave')}
                                            {' · '}
                                            {leaveCoverageLabel(day.leave, t)}
                                        </Badge>
                                    ) : null}
                                </div>

                                {attendance ? (
                                    <div className="space-y-0.5 text-xs">
                                        <p className="tabular-nums">
                                            {formatPunchTime(
                                                attendance.check_in_at,
                                            )}{' '}
                                            –{' '}
                                            {attendance.check_out_at
                                                ? formatPunchTime(
                                                      attendance.check_out_at,
                                                  )
                                                : t(
                                                      'my_schedule.attendance_open',
                                                  )}
                                        </p>
                                        {attendance.worked_minutes > 0 ? (
                                            <p className="text-muted-foreground">
                                                {formatDuration(
                                                    attendance.worked_minutes,
                                                    t,
                                                )}
                                            </p>
                                        ) : null}
                                        {isLate ? (
                                            <Badge
                                                variant="outline"
                                                className="border-orange-500/40 text-orange-700 dark:text-orange-300"
                                            >
                                                {t(
                                                    'my_schedule.attendance_late',
                                                )}{' '}
                                                {formatDuration(
                                                    attendance.late_minutes,
                                                    t,
                                                )}
                                            </Badge>
                                        ) : null}
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground">
                                        {t('empty_value', { ns: 'common' })}
                                    </p>
                                )}
                            </button>
                        </li>
                    );
                })}
            </ul>
        );
    }

    return (
        <div className="overflow-x-auto rounded-md border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-3 py-2">
                            {t('my_schedule.col_date')}
                        </th>
                        <th className="px-3 py-2">
                            {t('my_schedule.col_shift')}
                        </th>
                        <th className="px-3 py-2">
                            {t('my_schedule.col_time')}
                        </th>
                        <th className="px-3 py-2">
                            {t('my_schedule.col_status')}
                        </th>
                        <th className="px-3 py-2">
                            {t('my_schedule.col_attendance')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {days.map((day) => {
                        const date = parseYmd(day.date);
                        const isToday = date ? isSameDay(date, today) : false;
                        const dateLabel = date
                            ? format(date, 'EEE, d MMM yyyy', { locale })
                            : day.date;
                        const attendance = attendanceByDate.get(day.date);
                        const isLate = Boolean(
                            attendance && attendance.late_minutes > 0,
                        );

                        return (
                            <tr
                                key={day.date}
                                className={cn(
                                    'border-t',
                                    isToday && 'bg-primary/5',
                                    day.leave && !isToday && 'bg-amber-500/5',
                                    day.rest_kind === 'holiday' &&
                                        !isToday &&
                                        !day.leave &&
                                        'bg-rose-500/5',
                                    day.rest_kind === 'weekend' &&
                                        !isToday &&
                                        !day.leave &&
                                        'bg-slate-500/5',
                                )}
                            >
                                <td className="px-3 py-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="capitalize">
                                            {dateLabel}
                                        </span>
                                        {isToday ? (
                                            <Badge variant="outline">
                                                {t('my_schedule.today')}
                                            </Badge>
                                        ) : null}
                                    </div>
                                </td>
                                <td className="px-3 py-2 font-medium">
                                    {day.shift_name ??
                                        t('empty_value', { ns: 'common' })}
                                </td>
                                <td className="px-3 py-2 text-muted-foreground tabular-nums">
                                    {day.start_time && day.end_time
                                        ? `${day.start_time} – ${day.end_time}`
                                        : t('empty_value', { ns: 'common' })}
                                </td>
                                <td className="px-3 py-2">
                                    <div className="flex flex-wrap gap-1.5">
                                        {day.rest_kind === 'holiday' ? (
                                            <Badge variant="secondary">
                                                {day.holiday_name ||
                                                    t('my_schedule.holiday')}
                                            </Badge>
                                        ) : null}
                                        {day.rest_kind === 'weekend' ? (
                                            <Badge variant="outline">
                                                {t('my_schedule.weekend')}
                                            </Badge>
                                        ) : null}
                                        {day.leave ? (
                                            <Badge variant="outline">
                                                {day.leave.leave_type_name ||
                                                    t('my_schedule.on_leave')}
                                                {' · '}
                                                {leaveCoverageLabel(
                                                    day.leave,
                                                    t,
                                                )}
                                            </Badge>
                                        ) : null}
                                        {day.rest_kind === 'none' &&
                                        !day.leave ? (
                                            <span className="text-muted-foreground">
                                                {t('empty_value', {
                                                    ns: 'common',
                                                })}
                                            </span>
                                        ) : null}
                                    </div>
                                </td>
                                <td className="px-3 py-2">
                                    {attendance ? (
                                        <div className="space-y-0.5 text-xs">
                                            <p className="tabular-nums">
                                                {formatPunchTime(
                                                    attendance.check_in_at,
                                                )}{' '}
                                                –{' '}
                                                {attendance.check_out_at
                                                    ? formatPunchTime(
                                                          attendance.check_out_at,
                                                      )
                                                    : t(
                                                          'my_schedule.attendance_open',
                                                      )}
                                            </p>
                                            {attendance.worked_minutes > 0 ? (
                                                <p className="text-muted-foreground">
                                                    {formatDuration(
                                                        attendance.worked_minutes,
                                                        t,
                                                    )}
                                                </p>
                                            ) : null}
                                            {isLate ? (
                                                <Badge
                                                    variant="outline"
                                                    className="border-orange-500/40 text-orange-700 dark:text-orange-300"
                                                >
                                                    {t(
                                                        'my_schedule.attendance_late',
                                                    )}{' '}
                                                    {formatDuration(
                                                        attendance.late_minutes,
                                                        t,
                                                    )}
                                                </Badge>
                                            ) : null}
                                        </div>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            {t('empty_value', {
                                                ns: 'common',
                                            })}
                                        </span>
                                    )}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
