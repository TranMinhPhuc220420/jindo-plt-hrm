import { useEffect, useState } from 'react';

import { Input } from '@/components/ui/input';
import {
    formatMoneyInput,
    normalizeCurrency,
    toCanonicalMoneyString,
    type AppCurrency,
} from '@/lib/currency';
import { cn } from '@/lib/utils';

type CurrencyInputProps = {
    value: string;
    onChange: (value: string) => void;
    currency?: string;
    id?: string;
    disabled?: boolean;
    required?: boolean;
    placeholder?: string;
    className?: string;
    'aria-invalid'?: boolean;
};

export function CurrencyInput({
    value,
    onChange,
    currency = 'VND',
    id,
    disabled = false,
    required = false,
    placeholder,
    className,
    'aria-invalid': ariaInvalid,
}: CurrencyInputProps) {
    const code = normalizeCurrency(currency);
    const [display, setDisplay] = useState(() =>
        formatMoneyInput(value, code),
    );

    useEffect(() => {
        setDisplay(formatMoneyInput(value, code));
    }, [value, code]);

    return (
        <Input
            id={id}
            type="text"
            inputMode={code === 'USD' ? 'decimal' : 'numeric'}
            value={display}
            disabled={disabled}
            required={required}
            placeholder={placeholder}
            aria-invalid={ariaInvalid}
            className={cn('tabular-nums', className)}
            onChange={(e) => {
                const canonical = toCanonicalMoneyString(e.target.value, code);
                onChange(canonical);
                setDisplay(formatMoneyInput(canonical, code));
            }}
            onBlur={() => {
                // Drop trailing decimal on blur for USD (e.g. "12." → "12").
                if (code === 'USD' && value.endsWith('.')) {
                    const cleaned = value.slice(0, -1);
                    onChange(cleaned);
                    setDisplay(formatMoneyInput(cleaned, code));
                } else {
                    setDisplay(formatMoneyInput(value, code));
                }
            }}
        />
    );
}

type CurrencySelectProps = {
    value: string;
    onChange: (value: AppCurrency) => void;
    id?: string;
    disabled?: boolean;
    className?: string;
};

const CURRENCY_OPTIONS: AppCurrency[] = ['VND', 'USD'];

export function CurrencySelect({
    value,
    onChange,
    id,
    disabled = false,
    className,
}: CurrencySelectProps) {
    const code = normalizeCurrency(value);

    return (
        <select
            id={id}
            disabled={disabled}
            value={code}
            onChange={(e) => onChange(normalizeCurrency(e.target.value))}
            className={cn(
                'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
        >
            {CURRENCY_OPTIONS.map((option) => (
                <option key={option} value={option}>
                    {option}
                </option>
            ))}
        </select>
    );
}
