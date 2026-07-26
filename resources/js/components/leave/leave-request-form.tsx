import { useTranslation } from 'react-i18next';
import { DateRangePicker } from '@/components/shared/date-range-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { LeaveType } from '@/lib/api/modules/leave';
import { leaveTypeLabel } from '@/lib/i18n/leave-labels';

export type LeaveRequestFormValues = {
    leaveTypeId: string;
    startDate: string;
    endDate: string;
    reason: string;
};

type Props = {
    values: LeaveRequestFormValues;
    onChange: (values: LeaveRequestFormValues) => void;
    types: LeaveType[];
    idPrefix?: string;
};

export function emptyLeaveRequestForm(): LeaveRequestFormValues {
    return {
        leaveTypeId: '',
        startDate: '',
        endDate: '',
        reason: '',
    };
}

export function LeaveRequestForm({
    values,
    onChange,
    types,
    idPrefix = 'leave',
}: Props) {
    const { t } = useTranslation(['leave', 'common']);

    return (
        <div className="grid gap-4">
            <div className="grid gap-1.5">
                <Label htmlFor={`${idPrefix}_type`}>
                    {t('index.leave_type')}
                </Label>
                <select
                    id={`${idPrefix}_type`}
                    className="h-11 rounded-md border border-input bg-background px-3 text-sm sm:h-9"
                    value={values.leaveTypeId}
                    onChange={(e) =>
                        onChange({ ...values, leaveTypeId: e.target.value })
                    }
                    required
                >
                    <option value="">{t('index.select_type')}</option>
                    {types
                        .filter((row) => row.is_active)
                        .map((row) => (
                            <option key={row.id} value={row.id}>
                                {leaveTypeLabel(t, row.code, row.name)}
                            </option>
                        ))}
                </select>
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor={`${idPrefix}_dates`}>
                    {t('index.start_date')}
                    {' – '}
                    {t('index.end_date')}
                </Label>
                <DateRangePicker
                    id={`${idPrefix}_dates`}
                    from={values.startDate}
                    to={values.endDate}
                    onChange={({ from, to }) => {
                        onChange({
                            ...values,
                            startDate: from,
                            endDate: to,
                        });
                    }}
                    required
                    numberOfMonths={1}
                    className="w-full min-w-0"
                />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor={`${idPrefix}_reason`}>
                    {t('index.reason')}
                </Label>
                <Input
                    id={`${idPrefix}_reason`}
                    value={values.reason}
                    onChange={(e) =>
                        onChange({ ...values, reason: e.target.value })
                    }
                    className="min-h-11 sm:min-h-9"
                />
            </div>
        </div>
    );
}
