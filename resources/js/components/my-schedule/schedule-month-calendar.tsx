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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type {
    WorkingCalendarDay,
    WorkingCalendarLeave,
} from '@/lib/api/modules/shifts';
import { dateFnsLocale } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    month: Date;
    days: WorkingCalendarDay[];
};

function indexByDate(
    days: WorkingCalendarDay[],
): Map<string, WorkingCalendarDay> {
    return new Map(days.map((day) => [day.date, day]));
}

function leaveLabel(
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

export function ScheduleMonthCalendar({ month, days }: Props) {
    const { t, i18n } = useTranslation('shifts');
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
                    <span className="size-2.5 rounded-sm bg-muted" />
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
                                const inMonth = isSameMonth(date, month);
                                const today = isToday(date);
                                const isLastCol = (index + 1) % 7 === 0;
                                const onLeave = Boolean(entry?.leave);

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
                                            {entry?.is_holiday && inMonth ? (
                                                <span className="truncate text-[10px] font-medium text-muted-foreground">
                                                    {t('my_schedule.holiday')}
                                                </span>
                                            ) : null}
                                        </div>

                                        {entry && inMonth ? (
                                            <div
                                                className={cn(
                                                    'space-y-1 rounded-md px-1.5 py-1 text-left text-foreground',
                                                    onLeave
                                                        ? 'bg-amber-500/15'
                                                        : 'bg-primary/10',
                                                )}
                                            >
                                                <p className="truncate text-xs leading-tight font-medium">
                                                    {entry.shift_name}
                                                </p>
                                                <p className="mt-0.5 text-[10px] text-muted-foreground tabular-nums sm:text-xs">
                                                    {entry.start_time}–
                                                    {entry.end_time}
                                                </p>
                                                {entry.leave ? (
                                                    <p className="truncate text-[10px] font-medium text-amber-800 dark:text-amber-200">
                                                        {leaveLabel(
                                                            entry.leave,
                                                            t,
                                                        )}
                                                    </p>
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
                                    'flex min-h-[5.5rem] w-full flex-col border-b p-1.5 text-left sm:min-h-[6.5rem] sm:p-2',
                                    !isLastCol && 'border-r',
                                    !inMonth &&
                                        'bg-muted/20 text-muted-foreground/60',
                                    inMonth &&
                                        entry?.is_holiday &&
                                        'bg-muted/40',
                                    today && 'ring-2 ring-primary ring-inset',
                                );

                                if (!entry || !inMonth) {
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
                                            className="max-w-xs"
                                        >
                                            <p className="font-medium">
                                                {entry.shift_name}
                                            </p>
                                            <p className="tabular-nums opacity-90">
                                                {entry.start_time} –{' '}
                                                {entry.end_time}
                                            </p>
                                            {entry.is_holiday ? (
                                                <p className="mt-1 opacity-90">
                                                    {t('my_schedule.holiday')}
                                                </p>
                                            ) : null}
                                            {entry.leave ? (
                                                <>
                                                    <p className="mt-1 font-medium opacity-90">
                                                        {entry.leave
                                                            .leave_type_name ||
                                                            t(
                                                                'my_schedule.on_leave',
                                                            )}
                                                    </p>
                                                    <p className="opacity-90">
                                                        {leaveLabel(
                                                            entry.leave,
                                                            t,
                                                        )}
                                                    </p>
                                                </>
                                            ) : null}
                                            {today ? (
                                                <p className="mt-1 opacity-90">
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
