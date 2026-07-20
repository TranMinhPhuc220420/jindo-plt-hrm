import { format, isValid, parse, parseISO, startOfDay } from 'date-fns';
import type { Locale } from 'date-fns';
import { enUS, vi } from 'date-fns/locale';

export type AppDateLocale = 'vi' | 'en';

const DATE_FORMAT = 'yyyy-MM-dd';
const TIME_FORMAT = 'HH:mm';
const DATETIME_LOCAL_FORMAT = "yyyy-MM-dd'T'HH:mm";

export function dateFnsLocale(lang: string | undefined): Locale {
    return lang?.startsWith('vi') ? vi : enUS;
}

export function parseDateString(
    value: string | null | undefined,
): Date | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = parse(value, DATE_FORMAT, new Date());

    return isValid(parsed) ? startOfDay(parsed) : undefined;
}

export function formatDateString(date: Date | undefined): string {
    if (!date || !isValid(date)) {
        return '';
    }

    return format(date, DATE_FORMAT);
}

export function parseTimeString(value: string | null | undefined):
    | {
          hours: number;
          minutes: number;
      }
    | undefined {
    if (!value) {
        return undefined;
    }

    const match = /^(\d{1,2}):(\d{2})(?::\d{2})?$/.exec(value.trim());

    if (!match) {
        return undefined;
    }

    const hours = Number(match[1]);
    const minutes = Number(match[2]);

    if (
        !Number.isInteger(hours) ||
        !Number.isInteger(minutes) ||
        hours < 0 ||
        hours > 23 ||
        minutes < 0 ||
        minutes > 59
    ) {
        return undefined;
    }

    return { hours, minutes };
}

export function formatTimeString(hours: number, minutes: number): string {
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

export function parseDateTimeLocal(
    value: string | null | undefined,
): Date | undefined {
    if (!value) {
        return undefined;
    }

    const normalized = value.length === 16 ? value : value.slice(0, 16);
    const parsed = parse(normalized, DATETIME_LOCAL_FORMAT, new Date());

    if (isValid(parsed)) {
        return parsed;
    }

    const iso = parseISO(value);

    return isValid(iso) ? iso : undefined;
}

export function formatDateTimeLocal(date: Date | undefined): string {
    if (!date || !isValid(date)) {
        return '';
    }

    return format(date, DATETIME_LOCAL_FORMAT);
}

/**
 * Convert a datetime-local wall-clock value (or an already-offset ISO string)
 * into an ISO-8601 UTC string for API payloads.
 *
 * Naive values like `2026-07-16T08:00` are interpreted in the browser's local
 * timezone, then converted to UTC (`…Z`). Values that already include `Z` or
 * an offset are normalized via `Date`.
 */
export function toApiDateTime(value: string | null | undefined): string | null {
    if (!value?.trim()) {
        return null;
    }

    const trimmed = value.trim();

    if (/(?:[zZ]|[+-]\d{2}:?\d{2})$/.test(trimmed)) {
        const iso = parseISO(trimmed);

        return isValid(iso) ? iso.toISOString() : null;
    }

    const local = parseDateTimeLocal(trimmed);

    if (!local) {
        return null;
    }

    return local.toISOString();
}

/** Format an API ISO datetime as local HH:mm for punch / correction lists. */
export function formatPunchTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return format(date, TIME_FORMAT);
}

export function displayDate(
    value: string | Date | null | undefined,
    lang: string | undefined,
): string {
    const date =
        typeof value === 'string'
            ? (parseDateString(value) ?? parseDateTimeLocal(value))
            : (value ?? undefined);

    if (!date || !isValid(date)) {
        return '';
    }

    const locale = dateFnsLocale(lang);

    return lang?.startsWith('vi')
        ? format(date, 'dd/MM/yyyy', { locale })
        : format(date, 'MMM d, yyyy', { locale });
}

export function displayTime(value: string | null | undefined): string {
    const parsed = parseTimeString(value);

    if (!parsed) {
        return value ?? '';
    }

    return formatTimeString(parsed.hours, parsed.minutes);
}

export function displayDateTime(
    value: string | Date | null | undefined,
    lang: string | undefined,
): string {
    const date =
        typeof value === 'string'
            ? parseDateTimeLocal(value)
            : (value ?? undefined);

    if (!date || !isValid(date)) {
        return '';
    }

    const locale = dateFnsLocale(lang);
    const datePart = lang?.startsWith('vi')
        ? format(date, 'dd/MM/yyyy', { locale })
        : format(date, 'MMM d, yyyy', { locale });

    return `${datePart} ${format(date, TIME_FORMAT)}`;
}

export function isDateBeforeMin(date: Date, min?: string): boolean {
    if (!min) {
        return false;
    }

    const minDate = parseDateString(min) ?? parseDateTimeLocal(min);

    if (!minDate) {
        return false;
    }

    return startOfDay(date) < startOfDay(minDate);
}

export function isDateAfterMax(date: Date, max?: string): boolean {
    if (!max) {
        return false;
    }

    const maxDate = parseDateString(max) ?? parseDateTimeLocal(max);

    if (!maxDate) {
        return false;
    }

    return startOfDay(date) > startOfDay(maxDate);
}

export function combineDateAndTime(date: Date, time: string | undefined): Date {
    const next = new Date(date);
    const parsed = parseTimeString(time);
    next.setHours(parsed?.hours ?? 0, parsed?.minutes ?? 0, 0, 0);

    return next;
}
