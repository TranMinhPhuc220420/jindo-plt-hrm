import { Link } from '@inertiajs/react';
import { Download, Eye } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { EmployeePickerField } from '@/components/shared/employee-picker-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ApiError } from '@/lib/api/errors';
import * as payrollApi from '@/lib/api/modules/payroll';
import type { Payslip } from '@/lib/api/modules/payroll';
import { useAuth } from '@/lib/auth/auth-context';
import { loadCompanyCurrency } from '@/lib/company-currency';
import { formatCurrency, type AppCurrency } from '@/lib/currency';
import {
    employeeDisplayName,
    formatPayslipPeriod,
    groupPayslipComponents,
    localizePayslipLabel,
    localizePayslipType,
    parsePayslipComponents,
    type PayslipComponent,
} from '@/lib/payroll/payslip-components';
import { cn } from '@/lib/utils';

export default function PayrollPayslipsPage() {
    const { t, i18n } = useTranslation(['payroll', 'common']);
    const { can } = useAuth();
    const [payslips, setPayslips] = useState<Payslip[]>([]);
    const [selected, setSelected] = useState<Payslip | null>(null);
    const [detailOpen, setDetailOpen] = useState(false);
    const [detailLoading, setDetailLoading] = useState(false);
    const [companyCurrency, setCompanyCurrency] = useState<AppCurrency>('VND');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [employeeFilter, setEmployeeFilter] = useState<number | null>(null);
    const [downloadingId, setDownloadingId] = useState<number | null>(null);

    const canManageList =
        can('can_manage_payslips') || can('can_view_payroll_history');
    const canOpenRun =
        can('can_view_payroll_history') || can('can_run_payroll');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [result, currency] = await Promise.all([
                payrollApi.listPayslips({
                    per_page: 50,
                    employee_id: employeeFilter ?? undefined,
                }),
                loadCompanyCurrency(),
            ]);
            setPayslips(result.data);
            setCompanyCurrency(currency);
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('payslips.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [employeeFilter, t]);

    useEffect(() => {
        void load();
    }, [load]);

    const breakdown = useMemo(() => {
        if (!selected) {
            return null;
        }

        return groupPayslipComponents(
            parsePayslipComponents(selected.components),
        );
    }, [selected]);

    async function handleDownload(id: number) {
        setDownloadingId(id);

        try {
            await payrollApi.downloadPayslip(id);
            toast.success(t('payslips.toast_downloaded'));
            setPayslips((prev) =>
                prev.map((slip) =>
                    slip.id === id ? { ...slip, has_pdf: true } : slip,
                ),
            );
            setSelected((prev) =>
                prev?.id === id ? { ...prev, has_pdf: true } : prev,
            );
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('payslips.toast_error'),
            );
        } finally {
            setDownloadingId(null);
        }
    }

    async function handleDetail(id: number) {
        setDetailOpen(true);
        setDetailLoading(true);

        try {
            const slip = await payrollApi.getPayslip(id);
            setSelected(slip ?? null);
        } catch (err) {
            setDetailOpen(false);
            setSelected(null);
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('payslips.toast_error'),
            );
        } finally {
            setDetailLoading(false);
        }
    }

    function handleDialogOpenChange(open: boolean) {
        setDetailOpen(open);
        if (!open) {
            setSelected(null);
            setDetailLoading(false);
        }
    }

    return (
        <AdminPageShell
            title={t('payslips.title')}
            description={t('payslips.description')}
            any={[
                'can_view_salary',
                'can_manage_payslips',
                'can_view_payroll_history',
            ]}
            actions={
                canOpenRun ? (
                    <Button variant="outline" asChild>
                        <Link href="/payroll">{t('payslips.back')}</Link>
                    </Button>
                ) : undefined
            }
        >
            {canManageList && (
                <div className="mb-6 max-w-md">
                    <EmployeePickerField
                        value={employeeFilter}
                        onChange={(id) => setEmployeeFilter(id)}
                        label={t('payslips.filter_employee')}
                        allowClear
                    />
                </div>
            )}

            {loading ? (
                <LoadingState label={t('payslips.loading')} />
            ) : error ? (
                <ErrorState message={error} />
            ) : payslips.length === 0 ? (
                <EmptyState message={t('payslips.empty')} />
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-border text-muted-foreground">
                                <th className="py-2 pr-4 font-medium">
                                    {t('payslips.col_employee')}
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    {t('payslips.col_period')}
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    {t('payslips.col_gross')}
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    {t('payslips.col_net')}
                                </th>
                                <th className="py-2 pr-4 font-medium">
                                    {t('payslips.col_pdf')}
                                </th>
                                <th className="py-2 font-medium">
                                    <span className="sr-only">
                                        {t('payslips.col_actions')}
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {payslips.map((slip) => (
                                <tr
                                    key={slip.id}
                                    className="border-b border-border/60"
                                >
                                    <td className="py-3 pr-4">
                                        {employeeDisplayName(slip)}
                                    </td>
                                    <td className="py-3 pr-4 whitespace-nowrap">
                                        {formatPayslipPeriod(
                                            slip.period_start,
                                            slip.period_end,
                                            i18n.language,
                                        )}
                                    </td>
                                    <td className="py-3 pr-4 tabular-nums text-muted-foreground">
                                        {formatCurrency(
                                            slip.gross,
                                            companyCurrency,
                                        )}
                                    </td>
                                    <td className="py-3 pr-4 tabular-nums font-medium">
                                        {formatCurrency(
                                            slip.net,
                                            companyCurrency,
                                        )}
                                    </td>
                                    <td className="py-3 pr-4">
                                        <Badge
                                            variant={
                                                slip.has_pdf
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {slip.has_pdf
                                                ? t('payslips.pdf_ready')
                                                : t('payslips.pdf_generate')}
                                        </Badge>
                                    </td>
                                    <td className="py-3">
                                        <div className="flex flex-wrap justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    void handleDetail(slip.id)
                                                }
                                            >
                                                <Eye className="size-4" />
                                                {t('payslips.detail')}
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                disabled={
                                                    downloadingId === slip.id
                                                }
                                                onClick={() =>
                                                    void handleDownload(
                                                        slip.id,
                                                    )
                                                }
                                            >
                                                <Download className="size-4" />
                                                {t('payslips.download')}
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <Dialog open={detailOpen} onOpenChange={handleDialogOpenChange}>
                <DialogContent className="flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg">
                    <DialogHeader className="border-b border-border px-6 py-4 text-left">
                        <DialogTitle>
                            {t('payslips.detail_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {selected
                                ? `${employeeDisplayName(selected)} · ${formatPayslipPeriod(
                                      selected.period_start,
                                      selected.period_end,
                                      i18n.language,
                                  )}`
                                : t('payslips.loading_detail')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex-1 space-y-6 overflow-y-auto px-6 py-4">
                        {detailLoading ? (
                            <LoadingState
                                label={t('payslips.loading_detail')}
                            />
                        ) : selected && breakdown ? (
                            <>
                                {canOpenRun && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-fit"
                                        asChild
                                    >
                                        <Link
                                            href={`/payroll/${selected.payroll_run_id}`}
                                        >
                                            {t('payslips.open_run')}
                                        </Link>
                                    </Button>
                                )}

                                <section aria-label={t('payslips.summary')}>
                                    <div className="grid grid-cols-3 gap-3 rounded-lg border border-border bg-muted/30 p-3 text-sm">
                                        <div>
                                            <p className="text-muted-foreground">
                                                {t('payslips.col_gross')}
                                            </p>
                                            <p className="mt-1 tabular-nums">
                                                {formatCurrency(
                                                    selected.gross,
                                                    companyCurrency,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">
                                                {t(
                                                    'payslips.deductions_total',
                                                )}
                                            </p>
                                            <p className="mt-1 tabular-nums text-destructive">
                                                −
                                                {formatCurrency(
                                                    breakdown.deductionTotal,
                                                    companyCurrency,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground">
                                                {t('payslips.col_net')}
                                            </p>
                                            <p className="mt-1 text-base font-semibold tabular-nums">
                                                {formatCurrency(
                                                    selected.net,
                                                    companyCurrency,
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                <ComponentSection
                                    title={t('payslips.earnings')}
                                    hint={t('payslips.earnings_hint')}
                                    empty={t('payslips.empty_earnings')}
                                    rows={breakdown.earnings}
                                    currency={companyCurrency}
                                    t={t}
                                />

                                <ComponentSection
                                    title={t('payslips.deductions')}
                                    hint={t('payslips.deductions_hint')}
                                    empty={t('payslips.empty_deductions')}
                                    rows={breakdown.deductions}
                                    currency={companyCurrency}
                                    t={t}
                                    deduction
                                />
                            </>
                        ) : (
                            <EmptyState
                                message={t('payslips.error_detail')}
                            />
                        )}
                    </div>

                    <DialogFooter className="border-t border-border px-6 py-4 sm:justify-end">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                {t('payslips.close')}
                            </Button>
                        </DialogClose>
                        {selected && (
                            <Button
                                type="button"
                                disabled={downloadingId === selected.id}
                                onClick={() =>
                                    void handleDownload(selected.id)
                                }
                            >
                                <Download className="size-4" />
                                {t('payslips.download')}
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminPageShell>
    );
}

type ComponentSectionProps = {
    title: string;
    hint: string;
    empty: string;
    rows: PayslipComponent[];
    currency: AppCurrency;
    t: (key: string, options?: Record<string, unknown>) => string;
    deduction?: boolean;
};

function ComponentSection({
    title,
    hint,
    empty,
    rows,
    currency,
    t,
    deduction = false,
}: ComponentSectionProps) {
    return (
        <section>
            <div className="mb-2">
                <h3 className="text-sm font-medium">{title}</h3>
                <p className="text-xs text-muted-foreground">{hint}</p>
            </div>
            {rows.length === 0 ? (
                <p className="text-sm text-muted-foreground">{empty}</p>
            ) : (
                <ul className="divide-y divide-border rounded-lg border border-border">
                    {rows.map((row, index) => (
                        <li
                            key={`${row.type}-${row.label}-${index}`}
                            className="flex items-start justify-between gap-3 px-3 py-2.5 text-sm"
                        >
                            <div className="min-w-0">
                                <p className="truncate font-medium">
                                    {localizePayslipLabel(row, t)}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {localizePayslipType(row.type, t)}
                                </p>
                            </div>
                            <p
                                className={cn(
                                    'shrink-0 tabular-nums',
                                    deduction && 'text-destructive',
                                )}
                            >
                                {deduction ? '−' : ''}
                                {formatCurrency(
                                    Math.abs(Number(row.amount) || 0),
                                    currency,
                                )}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
