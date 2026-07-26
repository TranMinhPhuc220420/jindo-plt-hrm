import {
    eachDayOfInterval,
    endOfMonth,
    endOfWeek,
    format,
    isSameMonth,
    isToday,
    startOfMonth,
    startOfWeek,
} from 'date-fns';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { formatDuration } from '@/components/attendance/format-minutes';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { AttendanceRecord } from '@/lib/api/modules/attendance';
import type {
    WorkingCalendarDay,
    WorkingCalendarLeave,
} from '@/lib/api/modules/shifts';
import { dateFnsLocale, formatPunchTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    month: Date;
    days: WorkingCalendarDay[];
    attendanceByDate: Map<string, AttendanceRecord>;
};

function indexByDate(
    days: WorkingCalendarDay[],
): Map<string, WorkingCalendarDay> {
    return new Map(days.map((day) => [day.date, day]));
}

function leaveCoverageLabel(
    leave: WorkingCalendarLeave,
    t: (key: string) => string,
): string {
    if (leave.coverage === 'am') {
        return t('my_schedule.leave_am');
    }

    if (leave.coverage === 'pm') {
        return t('my_schedule.leave_pm');
    }

    if (leave.coverage === 'hours') {
        return t('my_schedule.leave_hours');
    }

    return leave.is_paid
        ? t('my_schedule.leave_paid')
        : t('my_schedule.leave_unpaid');
}

function restLabel(
    entry: WorkingCalendarDay,
    t: (key: string) => string,
): string | null {
    if (entry.rest_kind === 'holiday') {
        return entry.holiday_name || t('my_schedule.holiday');
    }

    if (entry.rest_kind === 'weekend') {
        return t('my_schedule.weekend');
    }

    return null;
}

export function ScheduleMonthCalendar({
    month,
    days,
    attendanceByDate,
}: Props) {
    const { t, i18n } = useTranslation(['shifts', 'attendance']);
    const locale = dateFnsLocale(i18n.language);
    const weekStartsOn = locale.options?.weekStartsOn ?? 1;
    const byDate = useMemo(() => indexByDate(days), [days]);

    const gridDays = useMemo(() => {
        const start = startOfWeek(startOfMonth(month), {
            weekStartsOn,
            locale,
        });
        const end = endOfWeek(endOfMonth(month), { weekStartsOn, locale });

        return eachDayOfInterval({ start, end });
    }, [month, weekStartsOn, locale]);

    const weekdayLabels = useMemo(() => {
        const start = startOfWeek(new Date(), { weekStartsOn, locale });

        return Array.from({ length: 7 }, (_, index) => {
            const day = new Date(start);
            day.setDate(start.getDate() + index);

            return format(day, 'EEEEEE', { locale });
        });
    }, [weekStartsOn, locale]);

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm ring-2 ring-primary" />
                    {t('my_schedule.legend_today')}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm border border-dashed border-slate-400 bg-slate-500/15" />
                    {t('my_schedule.legend_weekend')}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-rose-500/25 ring-1 ring-rose-500/40" />
                    {t('my_schedule.legend_holiday')}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-primary/15" />
                    {t('my_schedule.legend_shift')}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-amber-500/25" />
                    {t('my_schedule.legend_leave')}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-emerald-500/30" />
                    {t('my_schedule.legend_present')}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="size-2.5 rounded-sm bg-orange-500/40" />
                    {t('my_schedule.legend_late')}
                </span>
            </div>

            <div className="overflow-x-auto rounded-md border">
                <div className="min-w-[36rem]">
                    <div className="grid grid-cols-7 border-b bg-muted/40 text-center text-xs font-medium text-muted-foreground">
                        {weekdayLabels.map((label, index) => (
                            <div key={index} className="px-1 py-2 capitalize">
                                {label}
                            </div>
                        ))}
                    </div>

                    <TooltipProvider delayDuration={200}>
                        <div className="grid grid-cols-7">
                            {gridDays.map((date, index) => {
                                const key = format(date, 'yyyy-MM-dd');
                                const entry = byDate.get(key);
                                const attendance = attendanceByDate.get(key);
                                const inMonth = isSameMonth(date, month);
                                const today = isToday(date);
                                const isLastCol = (index + 1) % 7 === 0;
                                const onLeave = Boolean(entry?.leave);
                                const rest = entry ? restLabel(entry, t) : null;
                                const isLate = Boolean(
                                    attendance && attendance.late_minutes > 0,
                                );
                                const hasPunch = Boolean(attendance);

                                const content = (
                                    <>
                                        <div className="mb-1 flex items-start justify-between gap-1">
                                            <span
                                                className={cn(
                                                    'inline-flex size-6 items-center justify-center rounded-full text-xs tabular-nums',
                                                    today &&
                                                        'bg-primary font-semibold text-primary-foreground',
                                                )}
                                            >
                                                {format(date, 'd')}
                                            </span>
                                            {rest && inMonth ? (
                                                <span
                                                    className={cn(
                                                        'truncate text-[10px] font-medium',
                                                        entry?.rest_kind ===
                                                            'holiday'
                                                            ? 'text-rose-700 dark:text-rose-300'
                                                            : 'text-slate-600 dark:text-slate-300',
                                                    )}
                                                    title={rest}
                                                >
                                                    {rest}
                                                </span>
                                            ) : null}
                                        </div>

                                        {entry && inMonth ? (
                                            <div
                                                className={cn(
                                                    'space-y-1 rounded-md px-1.5 py-1 text-left text-foreground',
                                                    onLeave
                                                        ? 'bg-amber-500/15 ring-1 ring-amber-500/30'
                                                        : entry.rest_kind ===
                                                            'holiday'
                                                          ? 'bg-rose-500/10 ring-1 ring-rose-500/25'
                                                          : entry.rest_kind ===
                                                              'weekend'
                                                            ? 'border border-dashed border-slate-400/50 bg-slate-500/10'
                                                            : entry.shift_id
                                                              ? 'bg-primary/10'
                                                              : 'bg-muted/40',
                                                )}
                                            >
                                                {entry.shift_name ? (
                                                    <>
                                                        <p className="truncate text-xs leading-tight font-medium">
                                                            {entry.shift_name}
                                                        </p>
                                                        {entry.start_time &&
                                                        entry.end_time ? (
                                                            <p className="text-[10px] text-muted-foreground tabular-nums sm:text-xs">
                                                                {
                                                                    entry.start_time
                                                                }
                                                                –
                                                                {
                                                                    entry.end_time
                                                                }
                                                            </p>
                                                        ) : null}
                                                    </>
                                                ) : rest ? (
                                                    <p className="truncate text-[10px] font-medium text-muted-foreground">
                                                        {t(
                                                            'my_schedule.rest_day',
                                                        )}
                                                    </p>
                                                ) : (
                                                    <p className="text-[10px] text-muted-foreground/70">
                                                        {t(
                                                            'my_schedule.no_shift',
                                                        )}
                                                    </p>
                                                )}
                                                {entry.leave ? (
                                                    <p className="truncate text-[10px] font-medium text-amber-800 dark:text-amber-200">
                                                        {entry.leave
                                                            .leave_type_name ||
                                                            t(
                                                                'my_schedule.on_leave',
                                                            )}
                                                        {' · '}
                                                        {leaveCoverageLabel(
                                                            entry.leave,
                                                            t,
                                                        )}
                                                    </p>
                                                ) : null}
                                                {attendance ? (
                                                    <div
                                                        className={cn(
                                                            'rounded px-1 py-0.5 text-[10px] tabular-nums',
                                                            isLate
                                                                ? 'bg-orange-500/20 font-medium text-orange-900 dark:text-orange-200'
                                                                : 'bg-emerald-500/15 text-emerald-900 dark:text-emerald-200',
                                                        )}
                                                    >
                                                        <p>
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
                                                        {isLate ? (
                                                            <p>
                                                                {t(
                                                                    'my_schedule.attendance_late',
                                                                )}{' '}
                                                                {formatDuration(
                                                                    attendance.late_minutes,
                                                                    t,
                                                                )}
                                                            </p>
                                                        ) : null}
                                                    </div>
                                                ) : null}
                                            </div>
                                        ) : inMonth ? (
                                            <p className="text-[10px] text-muted-foreground/70">
                                                {t('my_schedule.no_shift')}
                                            </p>
                                        ) : null}
                                    </>
                                );

                                const cellClass = cn(
                                    'flex min-h-[6.5rem] w-full flex-col border-b p-1.5 text-left sm:min-h-[7.5rem] sm:p-2',
                                    !isLastCol && 'border-r',
                                    !inMonth &&
                                        'bg-muted/20 text-muted-foreground/60',
                                    inMonth &&
                                        entry?.rest_kind === 'holiday' &&
                                        'bg-rose-500/5',
                                    inMonth &&
                                        entry?.rest_kind === 'weekend' &&
                                        'bg-slate-500/5',
                                    today && 'ring-2 ring-primary ring-inset',
                                    hasPunch &&
                                        !today &&
                                        'border-b-emerald-500/40',
                                );

                                const interactive =
                                    Boolean(entry && inMonth) ||
                                    Boolean(attendance && inMonth);

                                if (!interactive) {
                                    return (
                                        <div key={key} className={cellClass}>
                                            {content}
                                        </div>
                                    );
                                }

                                return (
                                    <Tooltip key={key}>
                                        <TooltipTrigger asChild>
                                            <button
                                                type="button"
                                                className={cellClass}
                                            >
                                                {content}
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            side="top"
                                            className="max-w-xs space-y-1"
                                        >
                                            {entry?.shift_name ? (
                                                <>
                                                    <p className="font-medium">
                                                        {entry.shift_name}
                                                    </p>
                                                    {entry.start_time &&
                                                    entry.end_time ? (
                                                        <p className="tabular-nums opacity-90">
                                                            {entry.start_time} –{' '}
                                                            {entry.end_time}
                                                        </p>
                                                    ) : null}
                                                </>
                                            ) : null}
                                            {rest ? (
                                                <p className="opacity-90">
                                                    {entry?.rest_kind ===
                                                    'holiday'
                                                        ? t(
                                                              'my_schedule.holiday',
                                                          )
                                                        : t(
                                                              'my_schedule.weekend',
                                                          )}
                                                    {entry?.holiday_name
                                                        ? `: ${entry.holiday_name}`
                                                        : ''}
                                                </p>
                                            ) : null}
                                            {entry?.leave ? (
                                                <>
                                                    <p className="font-medium opacity-90">
                                                        {entry.leave
                                                            .leave_type_name ||
                                                            t(
                                                                'my_schedule.on_leave',
                                                            )}
                                                    </p>
                                                    <p className="opacity-90">
                                                        {leaveCoverageLabel(
                                                            entry.leave,
                                                            t,
                                                        )}
                                                    </p>
                                                </>
                                            ) : null}
                                            {attendance ? (
                                                <>
                                                    <p className="font-medium opacity-90">
                                                        {t(
                                                            'my_schedule.col_attendance',
                                                        )}
                                                    </p>
                                                    <p className="tabular-nums opacity-90">
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
                                                    {attendance.worked_minutes >
                                                    0 ? (
                                                        <p className="opacity-90">
                                                            {formatDuration(
                                                                attendance.worked_minutes,
                                                                t,
                                                            )}
                                                        </p>
                                                    ) : null}
                                                    {isLate ? (
                                                        <p className="opacity-90">
                                                            {t(
                                                                'my_schedule.attendance_late',
                                                            )}
                                                            :{' '}
                                                            {formatDuration(
                                                                attendance.late_minutes,
                                                                t,
                                                            )}
                                                        </p>
                                                    ) : null}
                                                    {attendance.overtime_minutes >
                                                    0 ? (
                                                        <p className="opacity-90">
                                                            OT:{' '}
                                                            {formatDuration(
                                                                attendance.overtime_minutes,
                                                                t,
                                                            )}
                                                        </p>
                                                    ) : null}
                                                </>
                                            ) : null}
                                            {today ? (
                                                <p className="opacity-90">
                                                    {t('my_schedule.today')}
                                                </p>
                                            ) : null}
                                        </TooltipContent>
                                    </Tooltip>
                                );
                            })}
                        </div>
                    </TooltipProvider>
                </div>
            </div>
        </div>
    );
}
