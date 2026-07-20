import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as employeeApi from '@/lib/api/modules/employees';
import type { Employee } from '@/lib/api/modules/employees';
import * as payrollApi from '@/lib/api/modules/payroll';
import type { EmployeeSalary } from '@/lib/api/modules/payroll';
import { formatCurrency } from '@/lib/currency';

export default function PayrollCompensationPage() {
    const { t } = useTranslation(['payroll', 'common']);
    const [employees, setEmployees] = useState<Employee[]>([]);
    const [salariesByEmployee, setSalariesByEmployee] = useState<
        Map<number, EmployeeSalary>
    >(() => new Map());
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('active');
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(
        async (pageNumber = page) => {
            setLoading(true);
            setError(null);

            try {
                const [employeeResult, salaryResult] = await Promise.all([
                    employeeApi.listEmployees({
                        search: search || undefined,
                        status: status || undefined,
                        page: pageNumber,
                        per_page: 50,
                    }),
                    payrollApi.listSalaries({
                        current_only: true,
                        per_page: 100,
                    }),
                ]);

                const map = new Map<number, EmployeeSalary>();

                for (const salary of salaryResult.data) {
                    if (!map.has(salary.employee_id)) {
                        map.set(salary.employee_id, salary);
                    }
                }

                setEmployees(employeeResult.data);
                setSalariesByEmployee(map);
                setPage(employeeResult.meta?.current_page ?? pageNumber);
                setLastPage(employeeResult.meta?.last_page ?? 1);
            } catch (err) {
                setError(
                    err instanceof ApiError
                        ? err.message
                        : t('compensation.error_load'),
                );
            } finally {
                setLoading(false);
            }
        },
        [search, status, t],
    );

    useEffect(() => {
        void load(1);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- initial load + filter submit drive refetch
    }, []);

    function handleFilter(event: FormEvent) {
        event.preventDefault();
        void load(1);
    }

    return (
        <AdminPageShell
            title={t('compensation.title')}
            description={t('compensation.description')}
            permission="can_manage_salary"
        >
            <div className="mb-4">
                <Button variant="outline" asChild>
                    <Link href="/payroll">{t('compensation.back')}</Link>
                </Button>
            </div>

            <div className="mb-4">
                <form
                    onSubmit={handleFilter}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="space-y-1">
                        <Label htmlFor="compensation-search">
                            {t('search', { ns: 'common' })}
                        </Label>
                        <Input
                            id="compensation-search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t(
                                'compensation.list_search_placeholder',
                            )}
                            className="w-56"
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="compensation-status">
                            {t('status', { ns: 'common' })}
                        </Label>
                        <select
                            id="compensation-status"
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                        >
                            <option value="">
                                {t('all', { ns: 'common' })}
                            </option>
                            <option value="probation">
                                {t('status_probation', { ns: 'common' })}
                            </option>
                            <option value="active">
                                {t('status_active', { ns: 'common' })}
                            </option>
                            <option value="suspended">
                                {t('status_suspended', { ns: 'common' })}
                            </option>
                            <option value="resigned">
                                {t('status_resigned', { ns: 'common' })}
                            </option>
                            <option value="archived">
                                {t('status_archived', { ns: 'common' })}
                            </option>
                        </select>
                    </div>
                    <Button type="submit" variant="outline">
                        {t('filter', { ns: 'common' })}
                    </Button>
                </form>
            </div>

            {loading && <LoadingState label={t('compensation.loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && employees.length === 0 && (
                <EmptyState message={t('compensation.list_empty')} />
            )}

            {!loading && !error && employees.length > 0 && (
                <div className="space-y-4">
                    <div className="overflow-x-auto rounded-lg border border-border">
                        <table className="min-w-full text-left text-sm">
                            <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-3 py-2 font-medium">
                                        {t('compensation.col_code')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('compensation.col_name')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('compensation.col_department')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('compensation.col_amount')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('compensation.col_currency')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('compensation.col_effective_from')}
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        {t('compensation.col_actions')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.map((employee) => {
                                    const salary = salariesByEmployee.get(
                                        employee.id,
                                    );

                                    return (
                                        <tr
                                            key={employee.id}
                                            className="border-t border-border"
                                        >
                                            <td className="px-3 py-2 font-medium">
                                                {employee.code}
                                            </td>
                                            <td className="px-3 py-2">
                                                {employee.full_name}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {employee.department?.name ??
                                                    '—'}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {salary
                                                    ? formatCurrency(
                                                          salary.amount,
                                                          salary.currency,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {salary?.currency ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {salary?.effective_from ?? '—'}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/payroll/compensation/${employee.id}`}
                                                    >
                                                        {t('compensation.edit')}
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {lastPage > 1 && (
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page <= 1 || loading}
                                onClick={() => void load(page - 1)}
                            >
                                {t('previous', { ns: 'common' })}
                            </Button>
                            <span className="text-sm text-muted-foreground">
                                {t('page_of', {
                                    ns: 'common',
                                    page,
                                    lastPage,
                                })}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page >= lastPage || loading}
                                onClick={() => void load(page + 1)}
                            >
                                {t('next', { ns: 'common' })}
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </AdminPageShell>
    );
}
