import type { TFunction } from 'i18next';

/**
 * Format minutes as a readable duration, e.g. "1 hour 30 minutes" / "1 tiếng 30 phút".
 * Uses the `attendance` namespace for unit labels so callers from any page can pass their `t`.
 */
export function formatDuration(
    minutes: number | null | undefined,
    t: TFunction,
): string {
    if (minutes == null || Number.isNaN(minutes)) {
        return '—';
    }

    const total = Math.max(0, Math.round(minutes));
    const hours = Math.floor(total / 60);
    const mins = total % 60;

    if (hours === 0) {
        return t('duration.minutes', { count: mins, ns: 'attendance' });
    }

    if (mins === 0) {
        return t('duration.hours', { count: hours, ns: 'attendance' });
    }

    return `${t('duration.hours', { count: hours, ns: 'attendance' })} ${t('duration.minutes', { count: mins, ns: 'attendance' })}`;
}
