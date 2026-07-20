import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import { ErrorState, LoadingState } from '@/components/shared/async-state';
import {
    CurrencyInput,
    CurrencySelect,
} from '@/components/shared/currency-input';
import { DatePicker } from '@/components/shared/date-picker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as employeeApi from '@/lib/api/modules/employees';
import type { Employee } from '@/lib/api/modules/employees';
import * as payrollApi from '@/lib/api/modules/payroll';
import type { CompensationComponent } from '@/lib/api/modules/payroll';
import { normalizeCurrency, parseMoneyInput } from '@/lib/currency';
import type { AppCurrency } from '@/lib/currency';

type CompRow = {
    code: string;
    name: string;
    amount: string;
    is_taxable: boolean;
    is_active: boolean;
};

type Props = {
    id: number;
};

function toRows(
    items: CompensationComponent[],
    currency: AppCurrency,
): CompRow[] {
    return items.map((item) => ({
        code: item.code,
        name: item.name,
        amount: String(parseMoneyInput(String(item.amount), currency) ?? '0'),
        is_taxable: item.is_taxable,
        is_active: item.is_active,
    }));
}

function emptyRow(): CompRow {
    return {
        code: '',
        name: '',
        amount: '0',
        is_taxable: false,
        is_active: true,
    };
}

export default function PayrollCompensationShowPage({ id }: Props) {
    const { t } = useTranslation(['payroll', 'common']);
    const [employee, setEmployee] = useState<Employee | null>(null);
    const [amount, setAmount] = useState('');
    const [currency, setCurrency] = useState<AppCurrency>('VND');
    const [effectiveFrom, setEffectiveFrom] = useState('');
    const [allowances, setAllowances] = useState<CompRow[]>([]);
    const [deductions, setDeductions] = useState<CompRow[]>([]);
    const [bonuses, setBonuses] = useState<CompRow[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    const loadCompensation = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [
                employeeData,
                salaryResult,
                allowanceItems,
                deductionItems,
                bonusItems,
            ] = await Promise.all([
                employeeApi.getEmployee(id),
                payrollApi.listSalaries({ employee_id: id, per_page: 1 }),
                payrollApi.listAllowances(id),
                payrollApi.listDeductions(id),
                payrollApi.listBonuses(id),
            ]);

            setEmployee(employeeData ?? null);

            const salary = salaryResult.data[0];
            const code = salary
                ? normalizeCurrency(salary.currency)
                : ('VND' as AppCurrency);

            if (salary) {
                setCurrency(code);
                setAmount(
                    String(parseMoneyInput(String(salary.amount), code) ?? ''),
                );
                setEffectiveFrom(salary.effective_from);
            } else {
                setAmount('');
                setCurrency('VND');
                setEffectiveFrom(new Date().toISOString().slice(0, 10));
            }

            setAllowances(toRows(allowanceItems, code));
            setDeductions(toRows(deductionItems, code));
            setBonuses(toRows(bonusItems, code));
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('compensation.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [id, t]);

    useEffect(() => {
        void loadCompensation();
    }, [loadCompensation]);

    async function handleSaveSalary(event: FormEvent) {
        event.preventDefault();

        setBusy(true);

        try {
            await payrollApi.upsertSalary(id, {
                amount: parseMoneyInput(amount, currency) ?? 0,
                currency,
                effective_from: effectiveFrom,
                strategy: 'monthly',
            });
            toast.success(t('compensation.toast_salary'));
            await loadCompensation();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('compensation.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    async function saveComponents(
        kind: 'allowances' | 'deductions' | 'bonuses',
        rows: CompRow[],
    ) {
        setBusy(true);

        try {
            const payload = rows
                .filter((row) => row.code && row.name)
                .map((row) => ({
                    code: row.code,
                    name: row.name,
                    amount: parseMoneyInput(row.amount, currency) ?? 0,
                    is_taxable: row.is_taxable,
                    is_active: row.is_active,
                }));

            if (kind === 'allowances') {
                await payrollApi.replaceAllowances(id, payload);
            } else if (kind === 'deductions') {
                await payrollApi.replaceDeductions(id, payload);
            } else {
                await payrollApi.replaceBonuses(id, payload);
            }

            toast.success(t('compensation.toast_components'));
            await loadCompensation();
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('compensation.toast_error'),
            );
        } finally {
            setBusy(false);
        }
    }

    function renderRows(
        title: string,
        rows: CompRow[],
        setRows: (rows: CompRow[]) => void,
        kind: 'allowances' | 'deductions' | 'bonuses',
    ) {
        return (
            <div className="space-y-3 border-t border-border pt-6">
                <h2 className="text-sm font-medium">{title}</h2>
                {rows.map((row, index) => (
                    <div
                        key={`${kind}-${index}`}
                        className="grid gap-2 sm:grid-cols-5"
                    >
                        <Input
                            placeholder={t('compensation.code')}
                            value={row.code}
                            onChange={(e) => {
                                const next = [...rows];
                                next[index] = { ...row, code: e.target.value };
                                setRows(next);
                            }}
                        />
                        <Input
                            placeholder={t('compensation.name')}
                            value={row.name}
                            onChange={(e) => {
                                const next = [...rows];
                                next[index] = { ...row, name: e.target.value };
                                setRows(next);
                            }}
                        />
                        <CurrencyInput
                            placeholder={t('compensation.amount')}
                            value={row.amount}
                            currency={currency}
                            onChange={(nextAmount) => {
                                const next = [...rows];
                                next[index] = {
                                    ...row,
                                    amount: nextAmount,
                                };
                                setRows(next);
                            }}
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={row.is_active}
                                onChange={(e) => {
                                    const next = [...rows];
                                    next[index] = {
                                        ...row,
                                        is_active: e.target.checked,
                                    };
                                    setRows(next);
                                }}
                            />
                            {t('compensation.active')}
                        </label>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() =>
                                setRows(rows.filter((_, i) => i !== index))
                            }
                        >
                            {t('compensation.remove')}
                        </Button>
                    </div>
                ))}
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setRows([...rows, emptyRow()])}
                    >
                        {t('compensation.add_row')}
                    </Button>
                    <Button
                        type="button"
                        disabled={busy}
                        onClick={() => void saveComponents(kind, rows)}
                    >
                        {t('compensation.save_components')}
                    </Button>
                </div>
            </div>
        );
    }

    const employeeLabel = employee
        ? `${employee.code} — ${employee.full_name}`
        : t('compensation.edit_title');

    return (
        <AdminPageShell
            title={employeeLabel}
            description={t('compensation.edit_description')}
            permission="can_manage_salary"
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/payroll/compensation">
                        {t('compensation.back_to_list')}
                    </Link>
                </Button>
            </div>

            {loading && <LoadingState label={t('compensation.loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && (
                <div className="space-y-6">
                    <form
                        onSubmit={handleSaveSalary}
                        className="grid max-w-xl gap-3"
                    >
                        <h2 className="text-sm font-medium">
                            {t('compensation.salary_title')}
                        </h2>
                        <div className="grid gap-2 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="amount">
                                    {t('compensation.amount')}
                                </Label>
                                <CurrencyInput
                                    id="amount"
                                    value={amount}
                                    currency={currency}
                                    onChange={setAmount}
                                    required
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="currency">
                                    {t('compensation.currency')}
                                </Label>
                                <CurrencySelect
                                    id="currency"
                                    value={currency}
                                    onChange={(next) => {
                                        setCurrency(next);
                                        const remap = (raw: string) => {
                                            const n = parseMoneyInput(
                                                raw,
                                                next,
                                            );

                                            return n === null ? '' : String(n);
                                        };
                                        setAmount(remap(amount));
                                        setAllowances((rows) =>
                                            rows.map((row) => ({
                                                ...row,
                                                amount: remap(row.amount),
                                            })),
                                        );
                                        setDeductions((rows) =>
                                            rows.map((row) => ({
                                                ...row,
                                                amount: remap(row.amount),
                                            })),
                                        );
                                        setBonuses((rows) =>
                                            rows.map((row) => ({
                                                ...row,
                                                amount: remap(row.amount),
                                            })),
                                        );
                                    }}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="effective_from">
                                    {t('compensation.effective_from')}
                                </Label>
                                <DatePicker
                                    id="effective_from"
                                    value={effectiveFrom}
                                    onChange={setEffectiveFrom}
                                    required
                                />
                            </div>
                        </div>
                        <Button type="submit" disabled={busy}>
                            {t('compensation.save_salary')}
                        </Button>
                    </form>

                    {renderRows(
                        t('compensation.allowances'),
                        allowances,
                        setAllowances,
                        'allowances',
                    )}
                    {renderRows(
                        t('compensation.deductions'),
                        deductions,
                        setDeductions,
                        'deductions',
                    )}
                    {renderRows(
                        t('compensation.bonuses'),
                        bonuses,
                        setBonuses,
                        'bonuses',
                    )}
                </div>
            )}
        </AdminPageShell>
    );
}
