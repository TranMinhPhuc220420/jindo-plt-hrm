import { format, isSameDay, isValid, parse } from 'date-fns';
import { useTranslation } from 'react-i18next';
import { Badge } from '@/components/ui/badge';
import type {
    WorkingCalendarDay,
    WorkingCalendarLeave,
} from '@/lib/api/modules/shifts';
import { dateFnsLocale } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Props = {
    days: WorkingCalendarDay[];
};

function parseYmd(value: string) {
    const parsed = parse(value, 'yyyy-MM-dd', new Date());

    return isValid(parsed) ? parsed : undefined;
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

export function ScheduleTable({ days }: Props) {
    const { t, i18n } = useTranslation(['shifts', 'common']);
    const locale = dateFnsLocale(i18n.language);
    const today = new Date();

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
                    </tr>
                </thead>
                <tbody>
                    {days.map((day) => {
                        const date = parseYmd(day.date);
                        const isToday = date ? isSameDay(date, today) : false;
                        const dateLabel = date
                            ? format(date, 'EEE, d MMM yyyy', { locale })
                            : day.date;

                        return (
                            <tr
                                key={day.date}
                                className={cn(
                                    'border-t',
                                    isToday && 'bg-primary/5',
                                    day.leave && !isToday && 'bg-amber-500/5',
                                    day.is_holiday &&
                                        !isToday &&
                                        !day.leave &&
                                        'bg-muted/30',
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
                                    {day.shift_name}
                                </td>
                                <td className="px-3 py-2 text-muted-foreground tabular-nums">
                                    {day.start_time} – {day.end_time}
                                </td>
                                <td className="px-3 py-2">
                                    <div className="flex flex-wrap gap-1.5">
                                        {day.is_holiday ? (
                                            <Badge variant="secondary">
                                                {t('my_schedule.holiday')}
                                            </Badge>
                                        ) : null}
                                        {day.leave ? (
                                            <Badge variant="outline">
                                                {day.leave.leave_type_name ||
                                                    t('my_schedule.on_leave')}
                                                {' · '}
                                                {leaveLabel(day.leave, t)}
                                            </Badge>
                                        ) : null}
                                        {!day.is_holiday && !day.leave ? (
                                            <span className="text-muted-foreground">
                                                {t('empty_value', {
                                                    ns: 'common',
                                                })}
                                            </span>
                                        ) : null}
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
