import type { TFunction } from 'i18next';
import { formatDuration } from '@/components/attendance/format-minutes';
import type { ReportRow, ReportType } from '@/lib/api/modules/reports';
import { formatCurrency } from '@/lib/currency';
import { leaveTypeLabel } from '@/lib/i18n/leave-labels';

/** Internal columns kept for localization but not shown in the table/CSV. */
export const REPORT_HIDDEN_COLUMNS = new Set(['leave_type_code']);

const DURATION_COLUMNS = new Set([
    'late_minutes',
    'overtime_minutes',
    'worked_minutes',
]);

const MONEY_COLUMNS = new Set(['total_gross', 'total_net']);

const DATE_COLUMNS = new Set([
    'start_date',
    'end_date',
    'hired_at',
    'period_start',
    'period_end',
]);

const DATETIME_COLUMNS = new Set(['submitted_at']);

export type ReportCellOptions = {
    currency?: string | null;
    locale?: string;
    row?: ReportRow;
};

/**
 * Localize a report table / CSV column key.
 */
export function reportColumnLabel(t: TFunction, column: string): string {
    return t(`columns.${column}`, {
        ns: 'reports',
        defaultValue: column,
    });
}

export function reportVisibleColumns(row: ReportRow | undefined): string[] {
    if (!row) {
        return [];
    }

    return Object.keys(row).filter((key) => !REPORT_HIDDEN_COLUMNS.has(key));
}

function formatDateValue(
    value: string,
    locale: string | undefined,
    withTime: boolean,
): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const lang = locale || undefined;

    if (withTime) {
        return date.toLocaleString(lang);
    }

    // Date-only strings (YYYY-MM-DD) should not shift by timezone.
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [y, m, d] = value.split('-').map(Number);

        return new Date(y!, m! - 1, d!).toLocaleDateString(lang);
    }

    return date.toLocaleDateString(lang);
}

/**
 * Format a cell for display, including status enums, leave types, dates,
 * durations, and money — scoped to the report type that produced the row.
 */
export function reportCellLabel(
    t: TFunction,
    report: ReportType,
    column: string,
    value: string | number | boolean | null | undefined,
    options: ReportCellOptions = {},
): string {
    if (value === null || value === undefined || value === '') {
        return t('empty_value', { ns: 'common' });
    }

    if (DURATION_COLUMNS.has(column) && typeof value !== 'boolean') {
        return formatDuration(Number(value), t);
    }

    if (MONEY_COLUMNS.has(column) && typeof value !== 'boolean') {
        return formatCurrency(value, options.currency);
    }

    if (
        typeof value === 'string' &&
        (DATE_COLUMNS.has(column) || DATETIME_COLUMNS.has(column))
    ) {
        return formatDateValue(
            value,
            options.locale,
            DATETIME_COLUMNS.has(column),
        );
    }

    if (column === 'leave_type') {
        const code = options.row?.leave_type_code;

        if (typeof code === 'string' && code !== '') {
            return leaveTypeLabel(t, code, String(value));
        }
    }

    if (column === 'status' && typeof value === 'string') {
        if (report === 'employees') {
            return t(`status_${value}`, {
                ns: 'common',
                defaultValue: value,
            });
        }

        if (report === 'leave') {
            return t(`status.${value}`, {
                ns: 'leave',
                defaultValue: value,
            });
        }

        if (report === 'payroll') {
            return t(`status.${value}`, {
                ns: 'payroll',
                defaultValue: value,
            });
        }
    }

    if (typeof value === 'boolean') {
        return value
            ? t('true', { ns: 'common' })
            : t('false', { ns: 'common' });
    }

    return String(value);
}
