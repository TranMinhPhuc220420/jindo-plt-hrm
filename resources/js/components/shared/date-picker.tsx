import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import type { Matcher } from 'react-day-picker';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    dateFnsLocale,
    displayDate,
    formatDateString,
    isDateAfterMax,
    isDateBeforeMin,
    parseDateString,
} from '@/lib/datetime';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    value: string;
    onChange: (value: string) => void;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    min?: string;
    max?: string;
    placeholder?: string;
    className?: string;
    'aria-invalid'?: boolean;
};

export function DatePicker({
    value,
    onChange,
    id,
    disabled = false,
    required = false,
    min,
    max,
    placeholder,
    className,
    'aria-invalid': ariaInvalid,
}: DatePickerProps) {
    const { t, i18n } = useTranslation('common');
    const [open, setOpen] = useState(false);
    const selected = parseDateString(value);
    const locale = dateFnsLocale(i18n.language);

    const disabledMatchers: Matcher[] = [];

    if (min || max) {
        disabledMatchers.push((date) => {
            return isDateBeforeMin(date, min) || isDateAfterMax(date, max);
        });
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-required={required || undefined}
                    aria-invalid={ariaInvalid}
                    data-empty={!value}
                    className={cn(
                        'h-9 w-full justify-start gap-2 border-input px-3 text-left font-normal shadow-xs',
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="size-4 shrink-0 opacity-60" />
                    <span className="truncate">
                        {value
                            ? displayDate(value, i18n.language)
                            : (placeholder ?? t('date_picker.pick_date'))}
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    locale={locale}
                    selected={selected}
                    defaultMonth={selected}
                    disabled={
                        disabledMatchers.length ? disabledMatchers : undefined
                    }
                    onSelect={(date) => {
                        onChange(formatDateString(date));
                        setOpen(false);
                    }}
                    captionLayout="dropdown"
                    fromYear={1950}
                    toYear={2100}
                />
            </PopoverContent>
        </Popover>
    );
}
