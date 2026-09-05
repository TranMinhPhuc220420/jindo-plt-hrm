import { format, isValid, parse } from 'date-fns';
import { useTranslation } from 'react-i18next';
import { formatDuration } from '@/components/attendance/format-minutes';
import { Badge } from '@/components/ui/badge';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { AttendanceRecord } from '@/lib/api/modules/attendance';
import type { WorkingCalendarDay } from '@/lib/api/modules/shifts';
import { dateFnsLocale, formatPunchTime } from '@/lib/datetime';
import {
    leaveCoverageLabel,
    restLabel,
    shiftNamesLabel,
    shiftWindowsLabel,
} from './schedule-day-helpers';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    date: string | null;
    entry?: WorkingCalendarDay;
    attendance?: AttendanceRecord;
};

function parseYmd(value: string) {
    const parsed = parse(value, 'yyyy-MM-dd', new Date());

    return isValid(parsed) ? parsed : undefined;
}

export function ScheduleDaySheet({
    open,
    onOpenChange,
    date,
    entry,
    attendance,
}: Props) {
    const { t, i18n } = useTranslation(['shifts', 'attendance', 'common']);
    const locale = dateFnsLocale(i18n.language);
    const parsed = date ? parseYmd(date) : undefined;
    const dateLabel = parsed
        ? format(parsed, 'EEEE, d MMMM yyyy', { locale })
        : (date ?? '');
    const rest = entry ? restLabel(entry, t) : null;
    const isLate = Boolean(attendance && attendance.late_minutes > 0);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="max-h-[85vh] gap-0 overflow-y-auto rounded-t-xl pb-[max(1rem,env(safe-area-inset-bottom))]"
            >
                <SheetHeader className="border-b border-border pb-4 text-left">
                    <SheetTitle>{t('my_schedule.day_detail_title')}</SheetTitle>
                    <SheetDescription className="capitalize">
                        {dateLabel}
                    </SheetDescription>
                </SheetHeader>

                <div className="space-y-4 p-4 pt-4">
                    {entry && shiftNamesLabel(entry) ? (
                        <section className="space-y-1">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {t('my_schedule.col_shift')}
                            </p>
                            <p className="text-base font-semibold">
                                {shiftNamesLabel(entry)}
                            </p>
                            {shiftWindowsLabel(entry) ? (
                                <p className="text-sm text-muted-foreground tabular-nums">
                                    {shiftWindowsLabel(entry)}
                                </p>
                            ) : null}
                        </section>
                    ) : (
                        <section className="space-y-1">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {t('my_schedule.col_shift')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {rest
                                    ? t('my_schedule.rest_day')
                                    : t('my_schedule.no_shift')}
                            </p>
                        </section>
                    )}

                    {rest ? (
                        <section className="space-y-2">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {t('my_schedule.col_status')}
                            </p>
                            <Badge
                                variant={
                                    entry?.rest_kind === 'holiday'
                                        ? 'secondary'
                                        : 'outline'
                                }
                            >
                                {rest}
                            </Badge>
                        </section>
                    ) : null}

                    {entry?.leave ? (
                        <section className="space-y-2">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {t('my_schedule.on_leave')}
                            </p>
                            <Badge variant="outline">
                                {entry.leave.leave_type_name ||
                                    t('my_schedule.on_leave')}
                                {' · '}
                                {leaveCoverageLabel(entry.leave, t)}
                            </Badge>
                        </section>
                    ) : null}

                    <section className="space-y-2">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            {t('my_schedule.col_attendance')}
                        </p>
                        {attendance ? (
                            <div className="space-y-1.5 text-sm">
                                <p className="tabular-nums">
                                    {formatPunchTime(attendance.check_in_at)} –{' '}
                                    {attendance.check_out_at
                                        ? formatPunchTime(
                                              attendance.check_out_at,
                                          )
                                        : t('my_schedule.attendance_open')}
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
                                        {t('my_schedule.attendance_late')}{' '}
                                        {formatDuration(
                                            attendance.late_minutes,
                                            t,
                                        )}
                                    </Badge>
                                ) : null}
                                {attendance.overtime_minutes > 0 ? (
                                    <p className="text-muted-foreground">
                                        OT:{' '}
                                        {formatDuration(
                                            attendance.overtime_minutes,
                                            t,
                                        )}
                                    </p>
                                ) : null}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {t('empty_value', { ns: 'common' })}
                            </p>
                        )}
                    </section>
                </div>
            </SheetContent>
        </Sheet>
    );
}
