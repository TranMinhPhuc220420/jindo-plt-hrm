import { ClockIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatTimeString, parseTimeString } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type TimePickerProps = {
    value: string;
    onChange: (value: string) => void;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    minuteStep?: 1 | 5 | 15;
    placeholder?: string;
    className?: string;
};

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function buildMinutes(step: number): number[] {
    const minutes: number[] = [];

    for (let m = 0; m < 60; m += step) {
        minutes.push(m);
    }

    return minutes;
}

export function TimePicker({
    value,
    onChange,
    id,
    disabled = false,
    required = false,
    minuteStep = 1,
    placeholder,
    className,
}: TimePickerProps) {
    const { t } = useTranslation('common');
    const parsed = parseTimeString(value);
    const hours = parsed?.hours;
    const minutes = parsed?.minutes;
    const minuteOptions = buildMinutes(minuteStep);

    const emit = (h: number | undefined, m: number | undefined) => {
        if (h === undefined || m === undefined) {
            onChange('');

            return;
        }

        onChange(formatTimeString(h, m));
    };

    return (
        <div
            id={id}
            className={cn('flex w-full items-center gap-2', className)}
            data-required={required || undefined}
        >
            <ClockIcon className="size-4 shrink-0 text-muted-foreground opacity-60" />
            <Select
                value={hours !== undefined ? String(hours) : undefined}
                onValueChange={(v) => {
                    const h = Number(v);
                    const m =
                        minutes !== undefined
                            ? minutes
                            : (minuteOptions[0] ?? 0);
                    emit(h, m);
                }}
                disabled={disabled}
            >
                <SelectTrigger
                    className="h-9 flex-1"
                    aria-label={t('date_picker.hour')}
                >
                    <SelectValue
                        placeholder={placeholder ?? t('date_picker.hour')}
                    />
                </SelectTrigger>
                <SelectContent className="z-[100]" position="popper">
                    {Array.from({ length: 24 }, (_, h) => (
                        <SelectItem key={h} value={String(h)}>
                            {pad(h)}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <span className="text-sm text-muted-foreground">:</span>
            <Select
                value={minutes !== undefined ? String(minutes) : undefined}
                onValueChange={(v) => {
                    const m = Number(v);
                    const h = hours !== undefined ? hours : 0;
                    emit(h, m);
                }}
                disabled={disabled}
            >
                <SelectTrigger
                    className="h-9 flex-1"
                    aria-label={t('date_picker.minute')}
                >
                    <SelectValue placeholder={t('date_picker.minute')} />
                </SelectTrigger>
                <SelectContent className="z-[100]" position="popper">
                    {minuteOptions.map((m) => (
                        <SelectItem key={m} value={String(m)}>
                            {pad(m)}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
