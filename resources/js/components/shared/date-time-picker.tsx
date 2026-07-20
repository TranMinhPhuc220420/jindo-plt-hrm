import { useState } from 'react';
import { CalendarClockIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { Matcher } from 'react-day-picker';

import { TimePicker } from '@/components/shared/time-picker';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    combineDateAndTime,
    dateFnsLocale,
    displayDateTime,
    formatDateTimeLocal,
    formatTimeString,
    isDateAfterMax,
    isDateBeforeMin,
    parseDateTimeLocal,
} from '@/lib/datetime';
import { cn } from '@/lib/utils';

type DateTimePickerProps = {
    value: string;
    onChange: (value: string) => void;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    min?: string;
    max?: string;
    placeholder?: string;
    className?: string;
    minuteStep?: 1 | 5 | 15;
};

export function DateTimePicker({
    value,
    onChange,
    id,
    disabled = false,
    required = false,
    min,
    max,
    placeholder,
    className,
    minuteStep = 1,
}: DateTimePickerProps) {
    const { t, i18n } = useTranslation('common');
    const [open, setOpen] = useState(false);
    const selected = parseDateTimeLocal(value);
    const locale = dateFnsLocale(i18n.language);

    const timeValue = selected
        ? formatTimeString(selected.getHours(), selected.getMinutes())
        : '';

    const disabledMatchers: Matcher[] = [];
    if (min || max) {
        disabledMatchers.push((date) => {
            return isDateBeforeMin(date, min) || isDateAfterMax(date, max);
        });
    }

    const applyDate = (date: Date | undefined) => {
        if (!date) {
            onChange('');
            return;
        }
        onChange(
            formatDateTimeLocal(
                combineDateAndTime(date, timeValue || '00:00'),
            ),
        );
    };

    const applyTime = (time: string) => {
        const base = selected ?? new Date();
        onChange(formatDateTimeLocal(combineDateAndTime(base, time || '00:00')));
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-required={required || undefined}
                    data-empty={!value}
                    className={cn(
                        'border-input h-9 w-full justify-start gap-2 px-3 text-left font-normal shadow-xs',
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarClockIcon className="size-4 shrink-0 opacity-60" />
                    <span className="truncate">
                        {value
                            ? displayDateTime(value, i18n.language)
                            : (placeholder ?? t('date_picker.pick_datetime'))}
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="flex w-auto max-h-[min(36rem,var(--radix-popover-content-available-height))] flex-col overflow-hidden p-0"
                align="start"
                collisionPadding={16}
            >
                <div className="overflow-y-auto p-3">
                    <Calendar
                        mode="single"
                        locale={locale}
                        selected={selected}
                        defaultMonth={selected}
                        disabled={
                            disabledMatchers.length
                                ? disabledMatchers
                                : undefined
                        }
                        onSelect={applyDate}
                        captionLayout="dropdown"
                        fromYear={1950}
                        toYear={2100}
                    />
                </div>
                <div className="border-border bg-popover sticky bottom-0 shrink-0 border-t p-3 shadow-[0_-4px_12px_-8px_rgba(0,0,0,0.15)]">
                    <p className="text-muted-foreground mb-2 text-xs font-medium">
                        {t('date_picker.time')}
                    </p>
                    <TimePicker
                        value={timeValue}
                        onChange={applyTime}
                        disabled={disabled}
                        minuteStep={minuteStep}
                    />
                    <div className="mt-3 flex justify-end">
                        <Button
                            type="button"
                            size="sm"
                            onClick={() => setOpen(false)}
                            disabled={!value}
                        >
                            {t('date_picker.done')}
                        </Button>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
