import type {
    WorkingCalendarDay,
    WorkingCalendarLeave,
} from '@/lib/api/modules/shifts';

type Translate = (key: string) => string;

export function leaveCoverageLabel(
    leave: WorkingCalendarLeave,
    t: Translate,
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

export function restLabel(
    entry: WorkingCalendarDay,
    t: Translate,
): string | null {
    if (entry.rest_kind === 'holiday') {
        return entry.holiday_name || t('my_schedule.holiday');
    }

    if (entry.rest_kind === 'weekend') {
        return t('my_schedule.weekend');
    }

    return null;
}

export function indexDaysByDate(
    days: WorkingCalendarDay[],
): Map<string, WorkingCalendarDay> {
    return new Map(days.map((day) => [day.date, day]));
}
