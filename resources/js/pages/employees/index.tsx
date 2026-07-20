import { Link } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import {
    EmptyState,
    ErrorState,
    LoadingState,
} from '@/components/shared/async-state';
import { DatePicker } from '@/components/shared/date-picker';
import { PermissionGate } from '@/components/shared/permission-gate';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as employeeApi from '@/lib/api/modules/employees';
import type { Employee } from '@/lib/api/modules/employees';

function emptyCreateForm() {
    return {
        code: '',
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        status: 'probation',
        hiredAt: '',
    };
}

export default function EmployeesIndexPage() {
    const { t } = useTranslation(['employees', 'common']);
    const [employees, setEmployees] = useState<Employee[]>([]);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const [createOpen, setCreateOpen] = useState(false);
    const [form, setForm] = useState(emptyCreateForm);
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const result = await employeeApi.listEmployees({
                search: search || undefined,
                status: status || undefined,
                per_page: 50,
            });
            setEmployees(result.data);
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('index.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [search, status, t]);

    useLoadEffect(load, [load]);

    function resetCreateForm() {
        setForm(emptyCreateForm());
    }

    function handleCreateOpenChange(open: boolean) {
        setCreateOpen(open);

        if (!open) {
            resetCreateForm();
        }
    }

    async function handleCreate(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            await employeeApi.createEmployee({
                code: form.code,
                first_name: form.firstName,
                last_name: form.lastName,
                email: form.email || null,
                phone: form.phone || null,
                status: form.status,
                hired_at: form.hiredAt || null,
            });
            toast.success(t('create.toast_success'));
            handleCreateOpenChange(false);
            await load();
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('create.toast_error'),
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <AdminPageShell
            title={t('index.title')}
            description={t('index.description')}
            permission="can_view_employee"
            actions={
                <PermissionGate permission="can_create_employee">
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        {t('index.create')}
                    </Button>
                </PermissionGate>
            }
        >
            <Dialog open={createOpen} onOpenChange={handleCreateOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('create.title')}</DialogTitle>
                        <DialogDescription>
                            {t('create.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="grid gap-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1">
                                <Label htmlFor="employee-code">
                                    {t('code', { ns: 'common' })}
                                </Label>
                                <Input
                                    id="employee-code"
                                    value={form.code}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            code: e.target.value,
                                        }))
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="employee-status">
                                    {t('status', { ns: 'common' })}
                                </Label>
                                <select
                                    id="employee-status"
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={form.status}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            status: e.target.value,
                                        }))
                                    }
                                >
                                    <option value="probation">
                                        {t('status_probation', {
                                            ns: 'common',
                                        })}
                                    </option>
                                    <option value="active">
                                        {t('status_active', { ns: 'common' })}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1">
                                <Label htmlFor="employee-last-name">
                                    {t('create.last_name')}
                                </Label>
                                <Input
                                    id="employee-last-name"
                                    value={form.lastName}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            lastName: e.target.value,
                                        }))
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="employee-first-name">
                                    {t('create.first_name')}
                                </Label>
                                <Input
                                    id="employee-first-name"
                                    value={form.firstName}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            firstName: e.target.value,
                                        }))
                                    }
                                    required
                                />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1">
                                <Label htmlFor="employee-email">
                                    {t('email', { ns: 'common' })}
                                </Label>
                                <Input
                                    id="employee-email"
                                    type="email"
                                    value={form.email}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            email: e.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="employee-phone">
                                    {t('phone', { ns: 'common' })}
                                </Label>
                                <Input
                                    id="employee-phone"
                                    value={form.phone}
                                    onChange={(e) =>
                                        setForm((prev) => ({
                                            ...prev,
                                            phone: e.target.value,
                                        }))
                                    }
                                />
                            </div>
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="employee-hired-at">
                                {t('create.hired_at')}
                            </Label>
                            <DatePicker
                                id="employee-hired-at"
                                value={form.hiredAt}
                                onChange={(hiredAt) =>
                                    setForm((prev) => ({ ...prev, hiredAt }))
                                }
                            />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    {t('cancel', { ns: 'common' })}
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={saving}>
                                {t('index.create')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div className="mb-4">
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void load();
                    }}
                >
                    <div className="space-y-1">
                        <Label htmlFor="search">
                            {t('search', { ns: 'common' })}
                        </Label>
                        <Input
                            id="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t('index.search_placeholder')}
                            className="w-56"
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="status">
                            {t('status', { ns: 'common' })}
                        </Label>
                        <select
                            id="status"
                            className="flex h-9 rounded-md border border-input bg-background px-3 text-sm"
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

            {loading && <LoadingState label={t('index.loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && employees.length === 0 && (
                <EmptyState message={t('index.empty')} />
            )}

            {!loading && !error && employees.length > 0 && (
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_code')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_name')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_department')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_position')}
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    {t('index.col_status')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {employees.map((employee) => (
                                <tr
                                    key={employee.id}
                                    className="border-t border-border"
                                >
                                    <td className="px-3 py-2">
                                        <Link
                                            href={`/employees/${employee.id}`}
                                            className="font-medium text-primary-brand hover:underline"
                                        >
                                            {employee.code}
                                        </Link>
                                    </td>
                                    <td className="px-3 py-2">
                                        {employee.full_name}
                                    </td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {employee.department?.name ??
                                            t('empty_value', { ns: 'common' })}
                                    </td>
                                    <td className="px-3 py-2 text-muted-foreground">
                                        {employee.position?.name ??
                                            t('empty_value', { ns: 'common' })}
                                    </td>
                                    <td className="px-3 py-2 capitalize">
                                        {t(`status_${employee.status}`, {
                                            ns: 'common',
                                        })}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminPageShell>
    );
}
