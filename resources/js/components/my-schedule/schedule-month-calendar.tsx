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
import type { TFunction } from 'i18next';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { formatDuration } from '@/components/attendance/format-minutes';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';
import type { AttendanceRecord } from '@/lib/api/modules/attendance';
import type { WorkingCalendarDay } from '@/lib/api/modules/shifts';
import { dateFnsLocale, formatPunchTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import {
    indexDaysByDate,
    leaveCoverageLabel,
    primaryAttendance,
    restLabel,
    shiftNamesLabel,
    shiftWindowsLabel,
} from './schedule-day-helpers';

type Props = {
    month: Date;
    days: WorkingCalendarDay[];
    attendanceByDate: Map<string, AttendanceRecord[]>;
    onSelectDate?: (date: string) => void;
};

function ScheduleLegend() {
    const { t } = useTranslation('shifts');

    return (
        <div className="-mx-1 flex gap-3 overflow-x-auto px-1 pb-1 text-xs text-muted-foreground md:flex-wrap md:overflow-visible">
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm ring-2 ring-primary" />
                {t('my_schedule.legend_today')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm border border-dashed border-slate-400 bg-slate-500/15" />
                {t('my_schedule.legend_weekend')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm border border-dashed border-sky-400 bg-sky-500/15" />
                {t('my_schedule.legend_off')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm bg-rose-500/25 ring-1 ring-rose-500/40" />
                {t('my_schedule.legend_holiday')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm bg-primary/15" />
                {t('my_schedule.legend_shift')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm bg-amber-500/25" />
                {t('my_schedule.legend_leave')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm bg-emerald-500/30" />
                {t('my_schedule.legend_present')}
            </span>
            <span className="inline-flex shrink-0 items-center gap-1.5">
                <span className="size-2.5 rounded-sm bg-orange-500/40" />
                {t('my_schedule.legend_late')}
            </span>
        </div>
    );
}

function CompactDayCell({
    date,
    entry,
    attendance,
    inMonth,
    today,
    isLastCol,
    onSelectDate,
}: {
    date: Date;
    entry?: WorkingCalendarDay;
    attendance?: AttendanceRecord;
    inMonth: boolean;
    today: boolean;
    isLastCol: boolean;
    onSelectDate?: (date: string) => void;
}) {
    const key = format(date, 'yyyy-MM-dd');
    const onLeave = Boolean(entry?.leave);
    const isLate = Boolean(attendance && attendance.late_minutes > 0);
    const hasPunch = Boolean(attendance);
    const interactive = inMonth;

    const tone = cn(
        inMonth && onLeave && 'bg-amber-500/20',
        inMonth &&
            !onLeave &&
            entry?.rest_kind === 'holiday' &&
            'bg-rose-500/15',
        inMonth &&
            !onLeave &&
            entry?.rest_kind === 'weekend' &&
            'border border-dashed border-slate-400/60 bg-slate-500/10',
        inMonth &&
            !onLeave &&
            entry?.rest_kind === 'off' &&
            'border border-dashed border-sky-400/60 bg-sky-500/10',
        inMonth &&
            !onLeave &&
            entry?.rest_kind === 'none' &&
            entry?.shift_id &&
            'bg-primary/15',
        inMonth &&
            !onLeave &&
            entry?.rest_kind === 'none' &&
            !entry?.shift_id &&
            'bg-muted/40',
    );

    const cellClass = cn(
        'flex min-h-11 w-full flex-col items-center justify-center gap-1 border-b p-1',
        !isLastCol && 'border-r',
        !inMonth && 'bg-muted/20 text-muted-foreground/50',
        tone,
        today && 'ring-2 ring-primary ring-inset',
        hasPunch && !today && isLate && 'border-b-orange-500/50',
        hasPunch && !today && !isLate && 'border-b-emerald-500/50',
        interactive && 'cursor-pointer active:bg-muted/60',
    );

    const dots = (
        <div className="flex h-1.5 items-center justify-center gap-0.5">
            {onLeave ? (
                <span className="size-1.5 rounded-full bg-amber-500" />
            ) : null}
            {hasPunch ? (
                <span
                    className={cn(
                        'size-1.5 rounded-full',
                        isLate ? 'bg-orange-500' : 'bg-emerald-500',
                    )}
                />
            ) : null}
        </div>
    );

    const content = (
        <>
            <span
                className={cn(
                    'inline-flex size-7 items-center justify-center rounded-full text-xs tabular-nums',
                    today && 'bg-primary font-semibold text-primary-foreground',
                )}
            >
                {format(date, 'd')}
            </span>
            {inMonth ? dots : <div className="h-1.5" />}
        </>
    );

    if (!interactive) {
        return (
            <div key={key} className={cellClass}>
                {content}
            </div>
        );
    }

    return (
        <button
            key={key}
            type="button"
            className={cellClass}
            onClick={() => onSelectDate?.(key)}
            aria-label={key}
        >
            {content}
        </button>
    );
}

function RichDayCell({
    date,
    entry,
    attendance,
    inMonth,
    today,
    isLastCol,
    t,
}: {
    date: Date;
    entry?: WorkingCalendarDay;
    attendance?: AttendanceRecord;
    inMonth: boolean;
    today: boolean;
    isLastCol: boolean;
    t: TFunction;
}) {
    const key = format(date, 'yyyy-MM-dd');
    const onLeave = Boolean(entry?.leave);
    const rest = entry ? restLabel(entry, t) : null;
    const isLate = Boolean(attendance && attendance.late_minutes > 0);
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
                            entry?.rest_kind === 'holiday'
                                ? 'text-rose-700 dark:text-rose-300'
                                : entry?.rest_kind === 'off'
                                  ? 'text-sky-700 dark:text-sky-300'
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
                            : entry.rest_kind === 'holiday'
                              ? 'bg-rose-500/10 ring-1 ring-rose-500/25'
                              : entry.rest_kind === 'weekend'
                                ? 'border border-dashed border-slate-400/50 bg-slate-500/10'
                                : entry.rest_kind === 'off'
                                  ? 'border border-dashed border-sky-400/50 bg-sky-500/10'
                                  : entry.shift_id
                                    ? 'bg-primary/10'
                                    : 'bg-muted/40',
                    )}
                >
                    {shiftNamesLabel(entry) ? (
                        <>
                            <p className="truncate text-xs leading-tight font-medium">
                                {shiftNamesLabel(entry)}
                            </p>
                            {shiftWindowsLabel(entry) ? (
                                <p className="text-[10px] text-muted-foreground tabular-nums sm:text-xs">
                                    {shiftWindowsLabel(entry)}
                                </p>
                            ) : null}
                        </>
                    ) : rest ? (
                        <p className="truncate text-[10px] font-medium text-muted-foreground">
                            {t('my_schedule.rest_day')}
                        </p>
                    ) : (
                        <p className="text-[10px] text-muted-foreground/70">
                            {t('my_schedule.no_shift')}
                        </p>
                    )}
                    {entry.leave ? (
                        <p className="truncate text-[10px] font-medium text-amber-800 dark:text-amber-200">
                            {entry.leave.leave_type_name ||
                                t('my_schedule.on_leave')}
                            {' · '}
                            {leaveCoverageLabel(entry.leave, t)}
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
                                {formatPunchTime(attendance.check_in_at)}–
                                {attendance.check_out_at
                                    ? formatPunchTime(attendance.check_out_at)
                                    : t('my_schedule.attendance_open')}
                            </p>
                            {isLate ? (
                                <p>
                                    {t('my_schedule.attendance_late')}{' '}
                                    {formatDuration(attendance.late_minutes, t)}
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
        !inMonth && 'bg-muted/20 text-muted-foreground/60',
        inMonth && entry?.rest_kind === 'holiday' && 'bg-rose-500/5',
        inMonth && entry?.rest_kind === 'weekend' && 'bg-slate-500/5',
        inMonth && entry?.rest_kind === 'off' && 'bg-sky-500/5',
        today && 'ring-2 ring-primary ring-inset',
        hasPunch && !today && 'border-b-emerald-500/40',
    );

    const interactive =
        Boolean(entry && inMonth) || Boolean(attendance && inMonth);

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
                <button type="button" className={cellClass}>
                    {content}
                </button>
            </TooltipTrigger>
            <TooltipContent side="top" className="max-w-xs space-y-1">
                {entry?.shift_name ? (
                    <>
                        <p className="font-medium">{entry.shift_name}</p>
                        {entry.start_time && entry.end_time ? (
                            <p className="tabular-nums opacity-90">
                                {entry.start_time} – {entry.end_time}
                            </p>
                        ) : null}
                    </>
                ) : null}
                {rest ? <p className="opacity-90">{rest}</p> : null}
                {entry?.leave ? (
                    <>
                        <p className="font-medium opacity-90">
                            {entry.leave.leave_type_name ||
                                t('my_schedule.on_leave')}
                        </p>
                        <p className="opacity-90">
                            {leaveCoverageLabel(entry.leave, t)}
                        </p>
                    </>
                ) : null}
                {attendance ? (
                    <>
                        <p className="font-medium opacity-90">
                            {t('my_schedule.col_attendance')}
                        </p>
                        <p className="tabular-nums opacity-90">
                            {formatPunchTime(attendance.check_in_at)} –{' '}
                            {attendance.check_out_at
                                ? formatPunchTime(attendance.check_out_at)
                                : t('my_schedule.attendance_open')}
                        </p>
                        {attendance.worked_minutes > 0 ? (
                            <p className="opacity-90">
                                {formatDuration(attendance.worked_minutes, t)}
                            </p>
                        ) : null}
                        {isLate ? (
                            <p className="opacity-90">
                                {t('my_schedule.attendance_late')}:{' '}
                                {formatDuration(attendance.late_minutes, t)}
                            </p>
                        ) : null}
                        {attendance.overtime_minutes > 0 ? (
                            <p className="opacity-90">
                                OT:{' '}
                                {formatDuration(attendance.overtime_minutes, t)}
                            </p>
                        ) : null}
                    </>
                ) : null}
                {today ? (
                    <p className="opacity-90">{t('my_schedule.today')}</p>
                ) : null}
            </TooltipContent>
        </Tooltip>
    );
}

export function ScheduleMonthCalendar({
    month,
    days,
    attendanceByDate,
    onSelectDate,
}: Props) {
    const { t, i18n } = useTranslation(['shifts', 'attendance']);
    const isMobile = useIsMobile();
    const locale = dateFnsLocale(i18n.language);
    const weekStartsOn = locale.options?.weekStartsOn ?? 1;
    const byDate = useMemo(() => indexDaysByDate(days), [days]);

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
            <ScheduleLegend />

            <div
                className={cn(
                    'rounded-md border',
                    !isMobile && 'overflow-x-auto',
                )}
            >
                <div className={cn(!isMobile && 'min-w-[36rem]')}>
                    <div className="grid grid-cols-7 border-b bg-muted/40 text-center text-xs font-medium text-muted-foreground">
                        {weekdayLabels.map((label, index) => (
                            <div key={index} className="px-1 py-2 capitalize">
                                {label}
                            </div>
                        ))}
                    </div>

                    {isMobile ? (
                        <div className="grid grid-cols-7">
                            {gridDays.map((date, index) => {
                                const key = format(date, 'yyyy-MM-dd');

                                return (
                                    <CompactDayCell
                                        key={key}
                                        date={date}
                                        entry={byDate.get(key)}
                                        attendance={primaryAttendance(
                                            attendanceByDate.get(key),
                                        )}
                                        inMonth={isSameMonth(date, month)}
                                        today={isToday(date)}
                                        isLastCol={(index + 1) % 7 === 0}
                                        onSelectDate={onSelectDate}
                                    />
                                );
                            })}
                        </div>
                    ) : (
                        <TooltipProvider delayDuration={200}>
                            <div className="grid grid-cols-7">
                                {gridDays.map((date, index) => {
                                    const key = format(date, 'yyyy-MM-dd');

                                    return (
                                        <RichDayCell
                                            key={key}
                                            date={date}
                                            entry={byDate.get(key)}
                                            attendance={primaryAttendance(
                                                attendanceByDate.get(key),
                                            )}
                                            inMonth={isSameMonth(date, month)}
                                            today={isToday(date)}
                                            isLastCol={(index + 1) % 7 === 0}
                                            t={t}
                                        />
                                    );
                                })}
                            </div>
                        </TooltipProvider>
                    )}
                </div>
            </div>
        </div>
    );
}
