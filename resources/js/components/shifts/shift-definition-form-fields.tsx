import { useTranslation } from 'react-i18next';
import { TimePicker } from '@/components/shared/time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SHIFT_KINDS, shiftKindLabel } from '@/lib/i18n/shift-labels';

export type ShiftDefinitionFormValues = {
    name: string;
    code: string;
    startTime: string;
    endTime: string;
    breakMinutes: string;
    kind: string;
    isNight: boolean;
    isFlexible: boolean;
};

export function emptyShiftDefinitionForm(): ShiftDefinitionFormValues {
    return {
        name: '',
        code: '',
        startTime: '08:00',
        endTime: '17:00',
        breakMinutes: '60',
        kind: 'standard',
        isNight: false,
        isFlexible: false,
    };
}

type Props = {
    idPrefix: string;
    values: ShiftDefinitionFormValues;
    onChange: (next: ShiftDefinitionFormValues) => void;
};

export function ShiftDefinitionFormFields({
    idPrefix,
    values,
    onChange,
}: Props) {
    const { t } = useTranslation(['shifts', 'common']);

    function patch(partial: Partial<ShiftDefinitionFormValues>) {
        onChange({ ...values, ...partial });
    }

    return (
        <div className="grid gap-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1">
                    <Label htmlFor={`${idPrefix}-code`}>
                        {t('code', { ns: 'common' })}
                    </Label>
                    <Input
                        id={`${idPrefix}-code`}
                        value={values.code}
                        onChange={(e) => patch({ code: e.target.value })}
                        required
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`${idPrefix}-name`}>
                        {t('name', { ns: 'common' })}
                    </Label>
                    <Input
                        id={`${idPrefix}-name`}
                        value={values.name}
                        onChange={(e) => patch({ name: e.target.value })}
                        required
                    />
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1">
                    <Label htmlFor={`${idPrefix}-start-time`}>
                        {t('create.start_time')}
                    </Label>
                    <TimePicker
                        id={`${idPrefix}-start-time`}
                        value={values.startTime}
                        onChange={(startTime) => patch({ startTime })}
                        required
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`${idPrefix}-end-time`}>
                        {t('create.end_time')}
                    </Label>
                    <TimePicker
                        id={`${idPrefix}-end-time`}
                        value={values.endTime}
                        onChange={(endTime) => patch({ endTime })}
                        required
                    />
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1">
                    <Label htmlFor={`${idPrefix}-break-minutes`}>
                        {t('create.break_minutes')}
                    </Label>
                    <Input
                        id={`${idPrefix}-break-minutes`}
                        type="number"
                        min={0}
                        value={values.breakMinutes}
                        onChange={(e) =>
                            patch({ breakMinutes: e.target.value })
                        }
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`${idPrefix}-kind`}>
                        {t('create.kind')}
                    </Label>
                    <select
                        id={`${idPrefix}-kind`}
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        value={values.kind}
                        onChange={(e) => {
                            const next = e.target.value;
                            patch({
                                kind: next,
                                isNight: next === 'night',
                                isFlexible: next === 'flexible',
                            });
                        }}
                    >
                        {SHIFT_KINDS.map((value) => (
                            <option key={value} value={value}>
                                {shiftKindLabel(t, value)}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            <div className="flex gap-4 text-sm">
                <label className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={values.isNight}
                        onChange={(e) => patch({ isNight: e.target.checked })}
                    />
                    {t('create.is_night')}
                </label>
                <label className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={values.isFlexible}
                        onChange={(e) =>
                            patch({ isFlexible: e.target.checked })
                        }
                    />
                    {t('create.is_flexible')}
                </label>
            </div>
        </div>
    );
}
