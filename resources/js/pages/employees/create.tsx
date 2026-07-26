import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import AdminPageShell from '@/components/shared/admin-page-shell';
import { DatePicker } from '@/components/shared/date-picker';
import EmployeeOrgPlacementFields, {
    orgIdOrNull,
} from '@/components/shared/employee-org-placement-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError } from '@/lib/api/errors';
import * as employeeApi from '@/lib/api/modules/employees';

export default function EmployeeCreatePage() {
    const { t } = useTranslation(['employees', 'common']);
    const [saving, setSaving] = useState(false);
    const [code, setCode] = useState('');
    const [firstName, setFirstName] = useState('');
    const [lastName, setLastName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [status, setStatus] = useState('probation');
    const [hiredAt, setHiredAt] = useState('');
    const [departmentId, setDepartmentId] = useState('');
    const [positionId, setPositionId] = useState('');

    async function handleSubmit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const employee = await employeeApi.createEmployee({
                code,
                first_name: firstName,
                last_name: lastName,
                email: email || null,
                phone: phone || null,
                status,
                hired_at: hiredAt || null,
                department_id: orgIdOrNull(departmentId),
                position_id: orgIdOrNull(positionId),
            });
            toast.success(t('create.toast_success'));
            router.visit(`/employees/${employee.id}`);
        } catch (err) {
            toast.error(
                err instanceof ApiError ? err.message : t('create.toast_error'),
            );
            setSaving(false);
        }
    }

    return (
        <AdminPageShell
            title={t('create.title')}
            description={t('create.description')}
            permission="can_create_employee"
        >
            <form onSubmit={handleSubmit} className="grid max-w-xl gap-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label htmlFor="code">
                            {t('code', { ns: 'common' })}
                        </Label>
                        <Input
                            id="code"
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            required
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="status">
                            {t('status', { ns: 'common' })}
                        </Label>
                        <select
                            id="status"
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                        >
                            <option value="probation">
                                {t('status_probation', { ns: 'common' })}
                            </option>
                            <option value="active">
                                {t('status_active', { ns: 'common' })}
                            </option>
                        </select>
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label htmlFor="last_name">
                            {t('create.last_name')}
                        </Label>
                        <Input
                            id="last_name"
                            value={lastName}
                            onChange={(e) => setLastName(e.target.value)}
                            required
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="first_name">
                            {t('create.first_name')}
                        </Label>
                        <Input
                            id="first_name"
                            value={firstName}
                            onChange={(e) => setFirstName(e.target.value)}
                            required
                        />
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-1">
                        <Label htmlFor="email">
                            {t('email', { ns: 'common' })}
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="phone">
                            {t('phone', { ns: 'common' })}
                        </Label>
                        <Input
                            id="phone"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                        />
                    </div>
                </div>
                <div className="space-y-1">
                    <Label htmlFor="hired_at">{t('create.hired_at')}</Label>
                    <DatePicker
                        id="hired_at"
                        value={hiredAt}
                        onChange={setHiredAt}
                    />
                </div>
                <EmployeeOrgPlacementFields
                    idPrefix="create"
                    departmentId={departmentId}
                    positionId={positionId}
                    disabled={saving}
                    onChange={({ departmentId: nextDept, positionId: nextPos }) => {
                        setDepartmentId(nextDept);
                        setPositionId(nextPos);
                    }}
                />
                <div className="flex gap-2">
                    <Button type="submit" disabled={saving}>
                        {t('create', { ns: 'common' })}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => router.visit('/employees')}
                    >
                        {t('cancel', { ns: 'common' })}
                    </Button>
                </div>
            </form>
        </AdminPageShell>
    );
}
