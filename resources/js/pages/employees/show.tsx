import { Link, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import { ErrorState, LoadingState } from '@/components/shared/async-state';
import { AvatarEditor } from '@/components/shared/avatar-editor';
import { PermissionGate } from '@/components/shared/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLoadEffect } from '@/hooks/use-load-effect';
import { ApiError } from '@/lib/api/errors';
import * as employeeApi from '@/lib/api/modules/employees';
import type { Employee, EmployeeStatus } from '@/lib/api/modules/employees';
import * as shiftApi from '@/lib/api/modules/shifts';
import type { ShiftAssignment } from '@/lib/api/modules/shifts';
import { useAuth } from '@/lib/auth/auth-context';
import { formatDateString } from '@/lib/datetime';

type Props = {
    id: number;
};

export default function EmployeeShowPage({ id }: Props) {
    const { t } = useTranslation(['employees', 'common']);
    const { can, employeeId, refreshMe } = useAuth();
    const [employee, setEmployee] = useState<Employee | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [status, setStatus] = useState<EmployeeStatus>('active');
    const [statusReason, setStatusReason] = useState('');

    const [contactName, setContactName] = useState('');
    const [contactPhone, setContactPhone] = useState('');
    const [contacts, setContacts] = useState<Array<Record<string, unknown>>>(
        [],
    );

    const [bankName, setBankName] = useState('');
    const [accountNumber, setAccountNumber] = useState('');
    const [taxCode, setTaxCode] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [currentAssignment, setCurrentAssignment] =
        useState<ShiftAssignment | null>(null);
    const [scheduleLoading, setScheduleLoading] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await employeeApi.getEmployee(id);
            setEmployee(data);
            setPhone(data.phone ?? '');
            setEmail(data.email ?? '');
            setStatus(data.status);

            const emergency = await employeeApi.listEmergencyContacts(id);
            setContacts(emergency);

            if (can('can_manage_employee_sensitive')) {
                const [bank, tax] = await Promise.all([
                    employeeApi.getBankAccount(id),
                    employeeApi.getTaxProfile(id),
                ]);
                setBankName(String(bank?.bank_name ?? ''));
                setAccountNumber(String(bank?.account_number ?? ''));
                setTaxCode(String(tax?.tax_code ?? ''));
            }

            if (can('can_view_shifts')) {
                setScheduleLoading(true);

                try {
                    const result = await shiftApi.listShiftAssignments({
                        employee_id: id,
                        per_page: 20,
                    });
                    const today = formatDateString(new Date());
                    const active =
                        result.data.find((row) => {
                            const startOk = row.start_date <= today;
                            const endOk =
                                row.end_date === null || row.end_date >= today;

                            return startOk && endOk;
                        }) ?? null;
                    setCurrentAssignment(active);
                } catch {
                    setCurrentAssignment(null);
                } finally {
                    setScheduleLoading(false);
                }
            }
        } catch (err) {
            setError(
                err instanceof ApiError ? err.message : t('show.error_load'),
            );
        } finally {
            setLoading(false);
        }
    }, [can, id, t]);

    useLoadEffect(load, [load]);

    async function handleSaveProfile(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const updated = await employeeApi.updateEmployee(id, {
                phone: phone || null,
                email: email || null,
            });
            setEmployee(updated);
            toast.success(t('show.toast_profile_updated'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_update_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleStatus(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const updated = await employeeApi.changeEmployeeStatus(id, {
                status,
                reason: statusReason || undefined,
            });
            setEmployee(updated);
            toast.success(t('show.toast_status_updated'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_status_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleSaveContact(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const next = [
                ...contacts.map((item) => ({
                    name: String(item.name ?? ''),
                    phone: item.phone ? String(item.phone) : null,
                    relationship: item.relationship
                        ? String(item.relationship)
                        : null,
                    email: item.email ? String(item.email) : null,
                    is_primary: Boolean(item.is_primary),
                })),
                {
                    name: contactName,
                    phone: contactPhone || null,
                    is_primary: contacts.length === 0,
                },
            ];
            const saved = await employeeApi.replaceEmergencyContacts(id, next);
            setContacts(saved);
            setContactName('');
            setContactPhone('');
            toast.success(t('show.toast_contacts_updated'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_contact_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleSaveSensitive(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            await Promise.all([
                employeeApi.updateBankAccount(id, {
                    bank_name: bankName,
                    account_number: accountNumber,
                }),
                employeeApi.updateTaxProfile(id, {
                    tax_code: taxCode || null,
                }),
            ]);
            toast.success(t('show.toast_sensitive_updated'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_sensitive_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleSetPassword(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            await employeeApi.updateEmployeePassword(id, {
                password: newPassword,
                password_confirmation: confirmPassword,
            });
            setNewPassword('');
            setConfirmPassword('');
            toast.success(t('show.toast_password_updated'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_password_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleResetPassword() {
        setSaving(true);

        try {
            await employeeApi.updateEmployeePassword(id, {
                use_default: true,
            });
            setNewPassword('');
            setConfirmPassword('');
            toast.success(t('show.toast_password_reset'));
        } catch (err) {
            toast.error(
                err instanceof ApiError
                    ? err.message
                    : t('show.toast_password_failed'),
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <AdminPageShell
            title={employee ? employee.full_name : t('show.fallback_title')}
            description={
                employee
                    ? `${employee.code} · ${t(`status_${employee.status}`, {
                          ns: 'common',
                      })}`
                    : t('show.fallback_description')
            }
            permission="can_view_employee"
        >
            <div className="mb-4">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/employees">{t('show.back')}</Link>
                </Button>
            </div>

            {loading && <LoadingState label={t('show.loading')} />}
            {error && <ErrorState message={error} />}

            {!loading && !error && employee && (
                <div className="space-y-8">
                    {(can('can_update_employee') ||
                        employeeId === employee.id) && (
                        <div className="grid max-w-xl gap-3 border-b border-border pb-6">
                            <h3 className="text-sm font-semibold">
                                {t('show.section_avatar')}
                            </h3>
                            <AvatarEditor
                                name={employee.full_name}
                                avatarUrl={employee.avatar_url}
                                onUpload={async (file) => {
                                    const updated =
                                        await employeeApi.uploadEmployeeAvatar(
                                            id,
                                            file,
                                        );
                                    setEmployee(updated);

                                    if (employeeId === employee.id) {
                                        await refreshMe();
                                        router.reload({ only: ['auth'] });
                                    }
                                }}
                                onRemove={async () => {
                                    const updated =
                                        await employeeApi.deleteEmployeeAvatar(
                                            id,
                                        );
                                    setEmployee(updated);

                                    if (employeeId === employee.id) {
                                        await refreshMe();
                                        router.reload({ only: ['auth'] });
                                    }
                                }}
                            />
                        </div>
                    )}

                    <PermissionGate permission="can_update_employee">
                        <form
                            onSubmit={handleSaveProfile}
                            className="grid max-w-xl gap-3 border-b border-border pb-6"
                        >
                            <h3 className="text-sm font-semibold">
                                {t('show.section_profile')}
                            </h3>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <Label htmlFor="email">
                                        {t('email', { ns: 'common' })}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={email}
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="phone">
                                        {t('phone', { ns: 'common' })}
                                    </Label>
                                    <Input
                                        id="phone"
                                        value={phone}
                                        onChange={(e) =>
                                            setPhone(e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <Button type="submit" disabled={saving} size="sm">
                                {t('show.save_profile')}
                            </Button>
                        </form>
                    </PermissionGate>

                    <PermissionGate permission="can_view_shifts">
                        <div className="grid max-w-xl gap-3 border-b border-border pb-6">
                            <h3 className="text-sm font-semibold">
                                {t('show.section_schedule')}
                            </h3>
                            {scheduleLoading ? (
                                <p className="text-sm text-muted-foreground">
                                    {t('show.schedule_loading')}
                                </p>
                            ) : currentAssignment ? (
                                <p className="text-sm">
                                    {t('show.schedule_current', {
                                        shift:
                                            currentAssignment.shift?.name ??
                                            `#${currentAssignment.shift_id}`,
                                        from: currentAssignment.start_date,
                                        to: currentAssignment.end_date
                                            ? ` → ${currentAssignment.end_date}`
                                            : t('show.schedule_open_end'),
                                    })}
                                </p>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {t('show.schedule_none')}
                                </p>
                            )}
                            <div>
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href={
                                            currentAssignment
                                                ? `/shifts/${currentAssignment.shift_id}`
                                                : '/shifts'
                                        }
                                    >
                                        {t('show.schedule_manage')}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </PermissionGate>

                    <PermissionGate permission="can_update_employee">
                        <div className="grid max-w-xl gap-3 border-b border-border pb-6">
                            <h3 className="text-sm font-semibold">
                                {t('show.section_password')}
                            </h3>
                            {employee.user_id == null ? (
                                <p className="text-sm text-muted-foreground">
                                    {t('show.password_no_account')}
                                </p>
                            ) : (
                                <>
                                    <form
                                        onSubmit={handleSetPassword}
                                        className="grid gap-3"
                                    >
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="space-y-1">
                                                <Label htmlFor="new-password">
                                                    {t('show.new_password')}
                                                </Label>
                                                <Input
                                                    id="new-password"
                                                    type="password"
                                                    autoComplete="new-password"
                                                    value={newPassword}
                                                    onChange={(e) =>
                                                        setNewPassword(
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="space-y-1">
                                                <Label htmlFor="confirm-password">
                                                    {t('show.confirm_password')}
                                                </Label>
                                                <Input
                                                    id="confirm-password"
                                                    type="password"
                                                    autoComplete="new-password"
                                                    value={confirmPassword}
                                                    onChange={(e) =>
                                                        setConfirmPassword(
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="submit"
                                                disabled={saving}
                                                size="sm"
                                            >
                                                {t('show.save_password')}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={saving}
                                                size="sm"
                                                onClick={handleResetPassword}
                                            >
                                                {t('show.reset_default')}
                                            </Button>
                                        </div>
                                    </form>
                                </>
                            )}
                        </div>
                    </PermissionGate>

                    <PermissionGate permission="can_change_employee_status">
                        <form
                            onSubmit={handleStatus}
                            className="grid max-w-xl gap-3 border-b border-border pb-6"
                        >
                            <h3 className="text-sm font-semibold">
                                {t('show.section_status')}
                            </h3>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <Label htmlFor="new-status">
                                        {t('show.new_status')}
                                    </Label>
                                    <select
                                        id="new-status"
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        value={status}
                                        onChange={(e) =>
                                            setStatus(
                                                e.target
                                                    .value as EmployeeStatus,
                                            )
                                        }
                                    >
                                        <option value="probation">
                                            {t('status_probation', {
                                                ns: 'common',
                                            })}
                                        </option>
                                        <option value="active">
                                            {t('status_active', {
                                                ns: 'common',
                                            })}
                                        </option>
                                        <option value="suspended">
                                            {t('status_suspended', {
                                                ns: 'common',
                                            })}
                                        </option>
                                        <option value="resigned">
                                            {t('status_resigned', {
                                                ns: 'common',
                                            })}
                                        </option>
                                        <option value="archived">
                                            {t('status_archived', {
                                                ns: 'common',
                                            })}
                                        </option>
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="reason">
                                        {t('show.reason')}
                                    </Label>
                                    <Input
                                        id="reason"
                                        value={statusReason}
                                        onChange={(e) =>
                                            setStatusReason(e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <Button type="submit" disabled={saving} size="sm">
                                {t('show.change_status')}
                            </Button>
                        </form>
                    </PermissionGate>

                    <PermissionGate permission="can_update_employee">
                        <div className="space-y-3 border-b border-border pb-6">
                            <h3 className="text-sm font-semibold">
                                {t('show.section_emergency')}
                            </h3>
                            <ul className="space-y-1 text-sm">
                                {contacts.map((contact, index) => (
                                    <li key={index}>
                                        {String(contact.name)}
                                        {contact.phone
                                            ? ` · ${String(contact.phone)}`
                                            : ''}
                                    </li>
                                ))}
                            </ul>
                            <form
                                onSubmit={handleSaveContact}
                                className="flex flex-wrap items-end gap-3"
                            >
                                <div className="space-y-1">
                                    <Label htmlFor="contact-name">
                                        {t('name', { ns: 'common' })}
                                    </Label>
                                    <Input
                                        id="contact-name"
                                        value={contactName}
                                        onChange={(e) =>
                                            setContactName(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="contact-phone">
                                        {t('phone', { ns: 'common' })}
                                    </Label>
                                    <Input
                                        id="contact-phone"
                                        value={contactPhone}
                                        onChange={(e) =>
                                            setContactPhone(e.target.value)
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={saving}
                                    size="sm"
                                >
                                    {t('show.add_contact')}
                                </Button>
                            </form>
                        </div>
                    </PermissionGate>

                    <PermissionGate permission="can_manage_employee_sensitive">
                        <form
                            onSubmit={handleSaveSensitive}
                            className="grid max-w-xl gap-3"
                        >
                            <h3 className="text-sm font-semibold">
                                {t('show.section_sensitive')}
                            </h3>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <Label htmlFor="bank">
                                        {t('show.bank_name')}
                                    </Label>
                                    <Input
                                        id="bank"
                                        value={bankName}
                                        onChange={(e) =>
                                            setBankName(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="account">
                                        {t('show.account_number')}
                                    </Label>
                                    <Input
                                        id="account"
                                        value={accountNumber}
                                        onChange={(e) =>
                                            setAccountNumber(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="tax">
                                    {t('show.tax_code')}
                                </Label>
                                <Input
                                    id="tax"
                                    value={taxCode}
                                    onChange={(e) => setTaxCode(e.target.value)}
                                />
                            </div>
                            <Button type="submit" disabled={saving} size="sm">
                                {t('show.save_sensitive')}
                            </Button>
                        </form>
                    </PermissionGate>
                </div>
            )}
        </AdminPageShell>
    );
}
