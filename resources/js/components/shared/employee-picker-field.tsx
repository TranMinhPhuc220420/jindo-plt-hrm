import { XIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { MouseEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { EmployeePickerDialog } from '@/components/shared/employee-picker-dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import * as employeesApi from '@/lib/api/modules/employees';
import type { Employee } from '@/lib/api/modules/employees';
import { cn } from '@/lib/utils';

type EmployeePickerFieldProps = {
    value: number | null;
    onChange: (id: number | null, employee: Employee | null) => void;
    label?: string;
    id?: string;
    required?: boolean;
    disabled?: boolean;
    statusDefault?: string;
    allowClear?: boolean;
    className?: string;
};

function formatEmployeeLabel(employee: Employee): string {
    return `${employee.code} — ${employee.full_name}`;
}

export function EmployeePickerField({
    value,
    onChange,
    label,
    id = 'employee-picker',
    required = false,
    disabled = false,
    statusDefault = 'active',
    allowClear = true,
    className,
}: EmployeePickerFieldProps) {
    const { t } = useTranslation('common');
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<Employee | null>(null);
    const [loadingLabel, setLoadingLabel] = useState(false);
    const selectedRef = useRef<Employee | null>(null);
    selectedRef.current = selected;

    useEffect(() => {
        if (value === null) {
            setSelected(null);

            return;
        }

        if (selectedRef.current?.id === value) {
            return;
        }

        let cancelled = false;
        setLoadingLabel(true);

        void (async () => {
            try {
                const employee = await employeesApi.getEmployee(value);

                if (!cancelled) {
                    setSelected(employee);
                }
            } catch {
                if (!cancelled) {
                    setSelected(null);
                }
            } finally {
                if (!cancelled) {
                    setLoadingLabel(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [value]);

    function handleSelect(employee: Employee) {
        setSelected(employee);
        onChange(employee.id, employee);
    }

    function handleClear(event: MouseEvent) {
        event.preventDefault();
        event.stopPropagation();
        setSelected(null);
        onChange(null, null);
    }

    const displayLabel = loadingLabel
        ? t('loading')
        : selected
          ? formatEmployeeLabel(selected)
          : value
            ? `#${value}`
            : t('employee_picker.placeholder');

    return (
        <div className={cn('grid gap-2', className)}>
            {label ? <Label htmlFor={id}>{label}</Label> : null}
            <div className="flex gap-2">
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    onClick={() => setOpen(true)}
                    className={cn(
                        'h-9 min-w-0 flex-1 justify-start font-normal',
                        !selected && !value && 'text-muted-foreground',
                    )}
                    aria-required={required}
                >
                    <span className="truncate">{displayLabel}</span>
                </Button>
                {allowClear && value !== null ? (
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        disabled={disabled}
                        onClick={handleClear}
                        aria-label={t('employee_picker.clear')}
                    >
                        <XIcon />
                    </Button>
                ) : null}
            </div>

            <EmployeePickerDialog
                open={open}
                onOpenChange={setOpen}
                onSelect={handleSelect}
                selectedId={value}
                statusDefault={statusDefault}
            />
        </div>
    );
}
