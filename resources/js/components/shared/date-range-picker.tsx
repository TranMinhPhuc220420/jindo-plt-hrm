import { useRef, useState } from 'react';
import { CalendarIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { DateRange, Matcher } from 'react-day-picker';

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

type DateRangePickerProps = {
    from: string;
    to: string;
    onChange: (range: { from: string; to: string }) => void;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    min?: string;
    max?: string;
    placeholder?: string;
    className?: string;
    numberOfMonths?: number;
};

export function DateRangePicker({
    from,
    to,
    onChange,
    id,
    disabled = false,
    required = false,
    min,
    max,
    placeholder,
    className,
    numberOfMonths = 2,
}: DateRangePickerProps) {
    const { t, i18n } = useTranslation('common');
    const [open, setOpen] = useState(false);
    /** Stay open after the start click so the user can pick a multi-day end. */
    const awaitingEndRef = useRef(false);
    /** Snapshot of the start day while awaiting an end click (same-day confirm). */
    const pendingFromRef = useRef('');
    const locale = dateFnsLocale(i18n.language);

    const selected: DateRange | undefined =
        from || to
            ? {
                  from: parseDateString(from),
                  to: parseDateString(to || from),
              }
            : undefined;

    const disabledMatchers: Matcher[] = [];
    if (min || max) {
        disabledMatchers.push((date) => {
            return isDateBeforeMin(date, min) || isDateAfterMax(date, max);
        });
    }

    const label =
        from && to
            ? from === to
                ? displayDate(from, i18n.language)
                : `${displayDate(from, i18n.language)} – ${displayDate(to, i18n.language)}`
            : from
              ? `${displayDate(from, i18n.language)} – …`
              : (placeholder ?? t('date_picker.pick_range'));

    const commitRange = (nextFrom: string, nextTo: string, close: boolean) => {
        onChange({ from: nextFrom, to: nextTo || nextFrom });
        if (close) {
            awaitingEndRef.current = false;
            pendingFromRef.current = '';
            setOpen(false);
        }
    };

    const handleOpenChange = (next: boolean) => {
        if (!next && awaitingEndRef.current) {
            // Closing while waiting for end: keep a valid single-day (or partial) range.
            const start = from || pendingFromRef.current;
            if (start) {
                onChange({ from: start, to: to || start });
            }
            awaitingEndRef.current = false;
            pendingFromRef.current = '';
        }
        setOpen(next);
    };

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-required={required || undefined}
                    data-empty={!from && !to}
                    className={cn(
                        'border-input h-9 w-full justify-start gap-2 px-3 text-left font-normal shadow-xs',
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="size-4 shrink-0 opacity-60" />
                    <span className="truncate">{label}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="range"
                    locale={locale}
                    numberOfMonths={numberOfMonths}
                    selected={selected}
                    defaultMonth={selected?.from}
                    disabled={
                        disabledMatchers.length ? disabledMatchers : undefined
                    }
                    onSelect={(range) => {
                        // Re-clicking the same day clears the RDP range — treat as
                        // confirming a single-day selection.
                        if (!range?.from && awaitingEndRef.current) {
                            const start = pendingFromRef.current || from;
                            if (start) {
                                commitRange(start, start, true);
                            } else {
                                awaitingEndRef.current = false;
                                pendingFromRef.current = '';
                            }
                            return;
                        }

                        const nextFrom = formatDateString(range?.from);
                        const nextTo = formatDateString(range?.to);

                        if (!nextFrom) {
                            awaitingEndRef.current = false;
                            pendingFromRef.current = '';
                            onChange({ from: '', to: '' });
                            return;
                        }

                        // First click: seed a same-day range and keep open for optional end.
                        if (!awaitingEndRef.current) {
                            pendingFromRef.current = nextFrom;
                            awaitingEndRef.current = true;
                            commitRange(nextFrom, nextTo || nextFrom, false);
                            return;
                        }

                        // Second click: end date chosen (same day or later).
                        commitRange(nextFrom, nextTo || nextFrom, true);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}
