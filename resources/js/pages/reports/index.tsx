import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as reportsApi from '@/lib/api/modules/reports';
import { REPORT_TYPES } from '@/lib/api/modules/reports';
import type {
    ExportStatus,
    ReportRow,
    ReportType,
} from '@/lib/api/modules/reports';
import { loadCompanyCurrency } from '@/lib/company-currency';
import type { AppCurrency } from '@/lib/currency';
import {
    reportCellLabel,
    reportColumnLabel,
    reportVisibleColumns,
} from '@/lib/i18n/report-labels';

const REPORT_VIEW_PERMISSIONS = [
    'can_view_attendance_reports',
    'can_view_payroll_reports',
    'can_view_leave_reports',
    'can_view_employee_reports',
    'can_view_performance_reports',
];

export default function ReportsPage() {
    const { t, i18n } = useTranslation([
        'reports',
        'common',
        'leave',
        'payroll',
        'attendance',
    ]);
    const [report, setReport] = useState<ReportType>('employees');
    /** Report type that produced the current `rows` (for correct cell i18n). */
    const [appliedReport, setAppliedReport] = useState<ReportType>('employees');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [rows, setRows] = useState<ReportRow[]>([]);
    const [hasRun, setHasRun] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [exportStatus, setExportStatus] = useState<ExportStatus | null>(null);
    const [exporting, setExporting] = useState(false);
    const [companyCurrency, setCompanyCurrency] = useState<AppCurrency>('VND');
    const [refreshToken, setRefreshToken] = useState(0);
    const pollRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const hasDateFilter = Boolean(dateFrom || dateTo);

    useEffect(() => {
        void loadCompanyCurrency().then(setCompanyCurrency);
    }, []);

    useEffect(
        () => () => {
            if (pollRef.current) {
                clearTimeout(pollRef.current);
            }
        },
        [],
    );

    const buildFilters = useCallback((): Record<string, string> => {
        const filters: Record<string, string> = {};

        if (dateFrom) {
            filters.date_from = dateFrom;
        }

        if (dateTo) {
            filters.date_to = dateTo;
        }

        return filters;
    }, [dateFrom, dateTo]);

    useEffect(() => {
        let cancelled = false;
        const filters = buildFilters();

        if (pollRef.current) {
            clearTimeout(pollRef.current);
            pollRef.current = null;
        }

        const timer = setTimeout(() => {
            void (async () => {
                setLoading(true);
                setError(null);
                setExportStatus(null);
                setExporting(false);

                try {
                    const result = await reportsApi.runReport(report, filters);

                    if (cancelled) {
                        return;
                    }

                    setRows(result.rows);
                    setAppliedReport(report);
                    setHasRun(true);
                } catch (err) {
                    if (cancelled) {
                        return;
                    }

                    setError(
                        err instanceof ApiError ? err.message : t('error_run'),
                    );
                    setRows([]);
                    setAppliedReport(report);
                    setHasRun(true);
                } finally {
                    if (!cancelled) {
                        setLoading(false);
                    }
                }
            })();
        }, 300);

        return () => {
            cancelled = true;
            clearTimeout(timer);
        };
    }, [report, buildFilters, refreshToken, t]);

    function handleReload() {
        setRefreshToken((token) => token + 1);
    }

    function handleClearFilters() {
        setDateFrom('');
        setDateTo('');

        if (!dateFrom && !dateTo) {
            setRefreshToken((token) => token + 1);
        }
    }

    const pollFnRef = useRef<(id: number) => void>(() => {});

    const poll = useCallback(
        (id: number) => {
            pollRef.current = setTimeout(async () => {
                try {
                    const model = await reportsApi.getExport(id);
                    setExportStatus(model.status);

                    if (model.status === 'pending') {
                        pollFnRef.current(id);
                    } else if (model.status === 'ready') {
                        setExporting(false);
                        toast.success(t('export_ready'));
                    } else {
                        setExporting(false);
                        toast.error(model.error_message ?? t('export_failed'));
                    }
                } catch {
                    setExporting(false);
                    setExportStatus('failed');
                    toast.error(t('export_failed'));
                }
            }, 1500);
        },
        [t],
    );

    useEffect(() => {
        pollFnRef.current = poll;
    }, [poll]);

    async function handleExport() {
        setExporting(true);
        setExportStatus('pending');

        try {
            const model = await reportsApi.createExport({
                report,
                filters: buildFilters(),
            });
            toast.success(t('export_queued'));
            poll(model.id);
        } catch (err) {
            setExporting(false);
            setExportStatus(null);
            toast.error(
                err instanceof ApiError ? err.message : t('export_failed'),
            );
        }
    }

    function handleDownload() {
        const keys = reportVisibleColumns(rows[0]);
        const headerLabels = keys.map((h) => reportColumnLabel(t, h));
        const formattedRows = rows.map((row) => {
            const next: ReportRow = {};

            for (const key of keys) {
                next[key] = reportCellLabel(t, appliedReport, key, row[key], {
                    currency: companyCurrency,
                    locale: i18n.language,
                    row,
                });
            }

            return next;
        });
        reportsApi.downloadRowsAsCsv(
            appliedReport,
            formattedRows,
            headerLabels,
        );
    }

    const headers = reportVisibleColumns(rows[0]);

    return (
        <AdminPageShell
            title={t('title')}
            description={t('description')}
            any={REPORT_VIEW_PERMISSIONS}
        >
            <div className="mb-6 grid gap-3 rounded-lg border border-border p-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="grid gap-1.5">
                    <Label htmlFor="report_type">{t('report_type')}</Label>
                    <select
                        id="report_type"
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        value={report}
                        onChange={(e) =>
                            setReport(e.target.value as ReportType)
                        }
                    >
                        {REPORT_TYPES.map((type) => (
                            <option key={type} value={type}>
                                {t(`type.${type}`)}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="grid gap-1.5 sm:col-span-2">
                    <Label htmlFor="report_dates">
                        {t('date_from')}
                        {' – '}
                        {t('date_to')}
                    </Label>
                    <DateRangePicker
                        id="report_dates"
                        from={dateFrom}
                        to={dateTo}
                        onChange={({ from, to }) => {
                            setDateFrom(from);
                            setDateTo(to);
                        }}
                        numberOfMonths={1}
                    />
                </div>
                <div className="flex items-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={!hasDateFilter || loading}
                        onClick={handleClearFilters}
                    >
                        {t('clear')}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        disabled={loading}
                        onClick={handleReload}
                    >
                        {t('reload')}
                    </Button>
                </div>
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <PermissionGate permission="can_export_reports">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={exporting}
                        onClick={() => void handleExport()}
                    >
                        {t('queue_export')}
                    </Button>
                </PermissionGate>
                {exportStatus && (
                    <span className="text-sm text-muted-foreground">
                        {t('export_status')}: {t(`status.${exportStatus}`)}
                    </span>
                )}
                {exportStatus === 'ready' && (
                    <Button size="sm" onClick={handleDownload}>
                        {t('download_csv')}
                    </Button>
                )}
            </div>

            {loading ? (
                <LoadingState label={t('loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : !hasRun ? (
                <EmptyState message={t('hint')} />
            ) : rows.length === 0 ? (
                <EmptyState message={t('empty')} />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground">
                            <tr>
                                {headers.map((header) => (
                                    <th
                                        key={header}
                                        className="px-3 py-2 font-medium whitespace-nowrap"
                                    >
                                        {reportColumnLabel(t, header)}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => (
                                <tr
                                    key={index}
                                    className="border-t border-border"
                                >
                                    {headers.map((header) => (
                                        <td
                                            key={header}
                                            className="px-3 py-2 whitespace-nowrap"
                                        >
                                            {reportCellLabel(
                                                t,
                                                appliedReport,
                                                header,
                                                row[header],
                                                {
                                                    currency: companyCurrency,
                                                    locale: i18n.language,
                                                    row,
                                                },
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminPageShell>
    );
}
