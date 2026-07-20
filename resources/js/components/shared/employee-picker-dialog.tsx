import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as employeesApi from '@/lib/api/modules/employees';
import type { Employee } from '@/lib/api/modules/employees';
import * as organizationApi from '@/lib/api/modules/organization';
import { cn } from '@/lib/utils';

type DepartmentOption = {
    id: number;
    name: string;
};

type EmployeePickerDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSelect: (employee: Employee) => void;
    selectedId?: number | null;
    statusDefault?: string;
};

function useDebouncedValue<T>(value: T, delayMs: number): T {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = window.setTimeout(() => setDebounced(value), delayMs);

        return () => window.clearTimeout(timer);
    }, [value, delayMs]);

    return debounced;
}

export function EmployeePickerDialog({
    open,
    onOpenChange,
    onSelect,
    selectedId = null,
    statusDefault = 'active',
}: EmployeePickerDialogProps) {
    const { t } = useTranslation('common');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(statusDefault);
    const [departmentId, setDepartmentId] = useState('');
    const [departments, setDepartments] = useState<DepartmentOption[]>([]);
    const [employees, setEmployees] = useState<Employee[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [wasOpen, setWasOpen] = useState(open);

    if (open !== wasOpen) {
        setWasOpen(open);

        if (open) {
            setSearch('');
            setStatus(statusDefault);
            setDepartmentId('');
        }
    }

    const debouncedSearch = useDebouncedValue(search, 300);

    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;

        void (async () => {
            try {
                const tree = await organizationApi.getOrganizationTree();

                if (cancelled) {
                    return;
                }

                const options: DepartmentOption[] = [];

                for (const branch of tree.branches) {
                    for (const dept of branch.departments) {
                        options.push({ id: dept.id, name: dept.name });
                    }
                }

                options.sort((a, b) => a.name.localeCompare(b.name));
                setDepartments(options);
            } catch {
                if (!cancelled) {
                    setDepartments([]);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [open]);

    const loadEmployees = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await employeesApi.listEmployees({
                search: debouncedSearch || undefined,
                status: status || undefined,
                department_id: departmentId ? Number(departmentId) : undefined,
                per_page: 50,
            });
            setEmployees(result.data);
        } catch (err) {
            setError(
                err instanceof ApiError
                    ? err.message
                    : t('employee_picker.error_load'),
            );
            setEmployees([]);
        } finally {
            setLoading(false);
        }
    }, [debouncedSearch, status, departmentId, t]);

    useLoadEffect(() => {
        if (!open) {
            return;
        }

        void loadEmployees();
    }, [open, loadEmployees]);

    function handleSelect(employee: Employee) {
        onSelect(employee);
        onOpenChange(false);
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[min(90vh,720px)] flex-col gap-4 sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{t('employee_picker.title')}</DialogTitle>
                    <DialogDescription>
                        {t('employee_picker.description')}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-wrap items-end gap-3">
                    <div className="min-w-[12rem] flex-1 space-y-1">
                        <Label htmlFor="employee-picker-search">
                            {t('search')}
                        </Label>
                        <Input
                            id="employee-picker-search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t(
                                'employee_picker.search_placeholder',
                            )}
                            autoFocus
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="employee-picker-status">
                            {t('status')}
                        </Label>
                        <select
                            id="employee-picker-status"
                            className="flex h-9 rounded-md border border-input bg-background px-3 text-sm"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                        >
                            <option value="">{t('all')}</option>
                            <option value="probation">
                                {t('status_probation')}
                            </option>
                            <option value="active">{t('status_active')}</option>
                            <option value="suspended">
                                {t('status_suspended')}
                            </option>
                            <option value="resigned">
                                {t('status_resigned')}
                            </option>
                            <option value="archived">
                                {t('status_archived')}
                            </option>
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="employee-picker-department">
                            {t('employee_picker.department')}
                        </Label>
                        <select
                            id="employee-picker-department"
                            className="flex h-9 min-w-[10rem] rounded-md border border-input bg-background px-3 text-sm"
                            value={departmentId}
                            onChange={(e) => setDepartmentId(e.target.value)}
                        >
                            <option value="">{t('all')}</option>
                            {departments.map((dept) => (
                                <option key={dept.id} value={dept.id}>
                                    {dept.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void loadEmployees()}
                    >
                        {t('filter')}
                    </Button>
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto rounded-md border border-border">
                    {loading ? (
                        <div className="p-4">
                            <LoadingState />
                        </div>
                    ) : error ? (
                        <div className="p-4">
                            <ErrorState message={error} />
                        </div>
                    ) : employees.length === 0 ? (
                        <div className="p-4">
                            <EmptyState message={t('employee_picker.empty')} />
                        </div>
                    ) : (
                        <table className="w-full text-left text-sm">
                            <thead className="sticky top-0 bg-background">
                                <tr className="border-b border-border text-muted-foreground">
                                    <th className="px-3 py-2 font-medium">
                                        {t('code')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('name')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('employee_picker.department')}
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        {t('status')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.map((employee) => {
                                    const selected = selectedId === employee.id;

                                    return (
                                        <tr
                                            key={employee.id}
                                            className={cn(
                                                'cursor-pointer border-b border-border/60 hover:bg-accent/60',
                                                selected && 'bg-accent',
                                            )}
                                            onClick={() =>
                                                handleSelect(employee)
                                            }
                                            onKeyDown={(e) => {
                                                if (
                                                    e.key === 'Enter' ||
                                                    e.key === ' '
                                                ) {
                                                    e.preventDefault();
                                                    handleSelect(employee);
                                                }
                                            }}
                                            tabIndex={0}
                                            role="button"
                                            aria-pressed={selected}
                                        >
                                            <td className="px-3 py-2.5 font-medium">
                                                {employee.code}
                                            </td>
                                            <td className="px-3 py-2.5">
                                                {employee.full_name}
                                            </td>
                                            <td className="px-3 py-2.5 text-muted-foreground">
                                                {employee.department?.name ??
                                                    t('empty_value')}
                                            </td>
                                            <td className="px-3 py-2.5 text-muted-foreground">
                                                {t(
                                                    `status_${employee.status}`,
                                                    {
                                                        defaultValue:
                                                            employee.status,
                                                    },
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
