export type ScheduleViewMode = 'calendar' | 'table';

export const SCHEDULE_VIEW_STORAGE_KEY = 'hrm.my-schedule.view';

export function readStoredScheduleView(): ScheduleViewMode {
    if (typeof window === 'undefined') {
        return 'calendar';
    }

    try {
        const raw = window.localStorage.getItem(SCHEDULE_VIEW_STORAGE_KEY);

        if (raw === 'table' || raw === 'calendar') {
            return raw;
        }
    } catch {
        // ignore storage errors
    }

    return 'calendar';
}

export function storeScheduleView(view: ScheduleViewMode): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(SCHEDULE_VIEW_STORAGE_KEY, view);
    } catch {
        // ignore storage errors
    }
}
