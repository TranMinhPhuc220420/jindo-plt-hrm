import { apiGet, apiPost } from '../client';

export const REPORT_TYPES = [
    'attendance',
    'payroll',
    'leave',
    'employees',
    'departments',
    'performance',
] as const;

export type ReportType = (typeof REPORT_TYPES)[number];

export type ReportRow = Record<string, string | number | boolean | null>;

export type ReportResult = {
    rows: ReportRow[];
    filters: Record<string, unknown>;
};

export type ExportStatus = 'pending' | 'ready' | 'failed';

export type ReportExport = {
    id: number;
    report: string;
    format: string;
    filters: Record<string, unknown>;
    status: ExportStatus;
    path: string | null;
    error_message: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export async function runReport(
    report: ReportType,
    filters: Record<string, string> = {},
): Promise<ReportResult> {
    const query = new URLSearchParams();

    for (const [key, value] of Object.entries(filters)) {
        if (value) {
            query.set(key, value);
        }
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<{ rows: ReportRow[] }>(
        `/api/reports/${report}${suffix}`,
    );

    const meta = res.meta as unknown as
        { filters?: Record<string, unknown> } | undefined;

    return {
        rows: res.data?.rows ?? [],
        filters: meta?.filters ?? {},
    };
}

export async function createExport(payload: {
    report: ReportType;
    filters?: Record<string, unknown>;
}) {
    const res = await apiPost<ReportExport>('/api/reports/exports', {
        report: payload.report,
        format: 'csv',
        filters: payload.filters ?? {},
    });

    return res.data;
}

export async function getExport(id: number) {
    const res = await apiGet<ReportExport>(`/api/reports/exports/${id}`);

    return res.data;
}

/**
 * Build a CSV file from report rows and trigger a browser download.
 * The queued export file lives on a private disk with no download route,
 * so the client materializes the CSV from the rows already fetched.
 *
 * @param headerLabels Optional localized column titles (same order as Object.keys of first row).
 */
export function downloadRowsAsCsv(
    report: string,
    rows: ReportRow[],
    headerLabels?: string[],
): void {
    const headers = rows.length > 0 ? Object.keys(rows[0]) : [];
    const labelRow =
        headerLabels && headerLabels.length === headers.length
            ? headerLabels
            : headers;
    const escape = (value: unknown): string => {
        const text = value === null || value === undefined ? '' : String(value);

        return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
    };

    const lines = [
        labelRow.map(escape).join(','),
        ...rows.map((row) => headers.map((h) => escape(row[h])).join(',')),
    ];
    const csv = lines.join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `report-${report}.csv`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}
