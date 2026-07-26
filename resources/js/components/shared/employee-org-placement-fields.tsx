import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Label } from '@/components/ui/label';
import * as organizationApi from '@/lib/api/modules/organization';

type OrgOption = {
    id: number;
    name: string;
};

type Props = {
    departmentId: string;
    positionId: string;
    onChange: (next: { departmentId: string; positionId: string }) => void;
    disabled?: boolean;
    idPrefix?: string;
};

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm';

export default function EmployeeOrgPlacementFields({
    departmentId,
    positionId,
    onChange,
    disabled = false,
    idPrefix = 'employee',
}: Props) {
    const { t } = useTranslation('employees');
    const [departments, setDepartments] = useState<OrgOption[]>([]);
    const [positions, setPositions] = useState<OrgOption[]>([]);

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            try {
                const tree = await organizationApi.getOrganizationTree();

                if (cancelled) {
                    return;
                }

                const deptOptions: OrgOption[] = [];

                for (const branch of tree.branches) {
                    for (const dept of branch.departments) {
                        deptOptions.push({ id: dept.id, name: dept.name });
                    }
                }

                deptOptions.sort((a, b) => a.name.localeCompare(b.name));

                const positionOptions = [...tree.positions]
                    .map((row) => ({ id: row.id, name: row.name }))
                    .sort((a, b) => a.name.localeCompare(b.name));

                setDepartments(deptOptions);
                setPositions(positionOptions);
            } catch {
                if (!cancelled) {
                    setDepartments([]);
                    setPositions([]);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    const departmentFieldId = `${idPrefix}-department`;
    const positionFieldId = `${idPrefix}-position`;

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1">
                <Label htmlFor={departmentFieldId}>
                    {t('placement.department')}
                </Label>
                <select
                    id={departmentFieldId}
                    className={selectClassName}
                    value={departmentId}
                    disabled={disabled}
                    onChange={(e) =>
                        onChange({
                            departmentId: e.target.value,
                            positionId,
                        })
                    }
                >
                    <option value="">{t('placement.select_department')}</option>
                    {departments.map((dept) => (
                        <option key={dept.id} value={String(dept.id)}>
                            {dept.name}
                        </option>
                    ))}
                </select>
            </div>
            <div className="space-y-1">
                <Label htmlFor={positionFieldId}>
                    {t('placement.position')}
                </Label>
                <select
                    id={positionFieldId}
                    className={selectClassName}
                    value={positionId}
                    disabled={disabled}
                    onChange={(e) =>
                        onChange({
                            departmentId,
                            positionId: e.target.value,
                        })
                    }
                >
                    <option value="">{t('placement.select_position')}</option>
                    {positions.map((position) => (
                        <option key={position.id} value={String(position.id)}>
                            {position.name}
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}

export function orgIdOrNull(value: string): number | null {
    return value === '' ? null : Number(value);
}
