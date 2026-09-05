import type { AttendanceRecord } from '@/lib/api/modules/attendance';
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

    if (entry.rest_kind === 'off') {
        return t('my_schedule.scheduled_off');
    }

    return null;
}

export function shiftWindowsLabel(entry: WorkingCalendarDay): string | null {
    const windows =
        entry.windows && entry.windows.length > 0
            ? entry.windows
            : entry.start_time && entry.end_time
              ? [
                    {
                        start_time: entry.start_time,
                        end_time: entry.end_time,
                    },
                ]
              : [];

    if (windows.length === 0) {
        return null;
    }

    return windows
        .map((window) => `${window.start_time}–${window.end_time}`)
        .join(' · ');
}

export function shiftNamesLabel(entry: WorkingCalendarDay): string | null {
    if (entry.windows && entry.windows.length > 0) {
        return [
            ...new Set(entry.windows.map((window) => window.shift_name)),
        ].join(' · ');
    }

    return entry.shift_name;
}

export function primaryAttendance(
    sessions: AttendanceRecord[] | undefined,
): AttendanceRecord | undefined {
    if (!sessions || sessions.length === 0) {
        return undefined;
    }

    return (
        sessions.find((row) => row.check_in_at && !row.check_out_at) ??
        sessions.find((row) => row.late_minutes > 0) ??
        sessions[0]
    );
}

export function indexDaysByDate(
    days: WorkingCalendarDay[],
): Map<string, WorkingCalendarDay> {
    return new Map(days.map((day) => [day.date, day]));
}
