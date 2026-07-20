export type AppCurrency = 'VND' | 'USD';

const CURRENCY_LOCALES: Record<AppCurrency, string> = {
    VND: 'vi-VN',
    USD: 'en-US',
};

export function normalizeCurrency(
    code: string | null | undefined,
): AppCurrency {
    const upper = (code ?? '').trim().toUpperCase();

    return upper === 'USD' ? 'USD' : 'VND';
}

function toNumber(amount: string | number | null | undefined): number | null {
    if (amount === null || amount === undefined || amount === '') {
        return null;
    }

    const n = typeof amount === 'number' ? amount : Number(amount);

    if (!Number.isFinite(n)) {
        return null;
    }

    return n;
}

/**
 * Display amount with currency symbol (e.g. `12.000.000 ₫`, `$1,234.56`).
 */
export function formatCurrency(
    amount: string | number | null | undefined,
    currency?: string | null,
): string {
    const n = toNumber(amount);

    if (n === null) {
        return '—';
    }

    const code = normalizeCurrency(currency);

    return new Intl.NumberFormat(CURRENCY_LOCALES[code], {
        style: 'currency',
        currency: code,
        minimumFractionDigits: code === 'VND' ? 0 : 2,
        maximumFractionDigits: code === 'VND' ? 0 : 2,
    }).format(n);
}

/**
 * Parse a masked or raw money string into a finite number.
 * VND: integers only. USD: up to 2 decimal places.
 */
export function parseMoneyInput(
    text: string | null | undefined,
    currency?: string | null,
): number | null {
    if (text === null || text === undefined) {
        return null;
    }

    const code = normalizeCurrency(currency);
    const trimmed = text.trim();

    if (!trimmed) {
        return null;
    }

    if (code === 'VND') {
        // Canonical / API decimal:2 values ("1000000", "1000000.00") must not
        // go through digit-stripping — that would turn ".00" into extra zeros
        // ("1000000.00" → 100000000). Formatted vi-VN input ("1.000.000") has
        // more than two fraction digits or multiple separators, so it falls through.
        if (/^\d+$/.test(trimmed) || /^\d+\.\d{1,2}$/.test(trimmed)) {
            const n = Math.trunc(Number(trimmed));

            return Number.isFinite(n) ? n : null;
        }

        const digits = trimmed.replace(/\D/g, '');

        if (!digits) {
            return null;
        }

        const n = Number(digits);

        return Number.isFinite(n) ? n : null;
    }

    // USD: keep digits and at most one decimal point (last segment = fraction).
    const cleaned = trimmed.replace(/[^\d.]/g, '');

    if (!cleaned || cleaned === '.') {
        return null;
    }

    const parts = cleaned.split('.');
    const intPart = parts[0] ?? '';
    const fracRaw = parts.slice(1).join('').slice(0, 2);
    const normalized =
        parts.length > 1 ? `${intPart || '0'}.${fracRaw}` : intPart;

    const n = Number(normalized);

    return Number.isFinite(n) ? n : null;
}

/**
 * Mask a canonical numeric string for the input field (no currency symbol).
 * VND: `12.000.000` · USD: `1,234.56`
 */
export function formatMoneyInput(
    amount: string | number | null | undefined,
    currency?: string | null,
): string {
    const code = normalizeCurrency(currency);

    if (amount === null || amount === undefined || amount === '') {
        return '';
    }

    // Preserve in-progress USD decimal typing (e.g. "12." / "12.5").
    if (typeof amount === 'string' && code === 'USD') {
        const trimmed = amount.trim();

        if (trimmed.endsWith('.')) {
            const intPart = trimmed.slice(0, -1).replace(/\D/g, '') || '0';
            const intFormatted = new Intl.NumberFormat('en-US', {
                maximumFractionDigits: 0,
            }).format(Number(intPart));

            return `${intFormatted}.`;
        }

        if (/^\d*\.\d{0,2}$/.test(trimmed.replace(/,/g, ''))) {
            const [rawInt = '', rawFrac = ''] = trimmed
                .replace(/,/g, '')
                .split('.');
            const intFormatted = new Intl.NumberFormat('en-US', {
                maximumFractionDigits: 0,
            }).format(Number(rawInt || '0'));

            return rawFrac.length > 0
                ? `${intFormatted}.${rawFrac}`
                : intFormatted;
        }
    }

    const n = toNumber(amount);

    if (n === null) {
        return '';
    }

    if (code === 'VND') {
        return new Intl.NumberFormat('vi-VN', {
            maximumFractionDigits: 0,
        }).format(Math.trunc(n));
    }

    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(n);
}

/**
 * Normalize a raw input string into a canonical numeric string for form state.
 */
export function toCanonicalMoneyString(
    text: string,
    currency?: string | null,
): string {
    const code = normalizeCurrency(currency);
    const trimmed = text.trim();

    if (!trimmed) {
        return '';
    }

    if (code === 'VND') {
        const digits = trimmed.replace(/\D/g, '');

        return digits;
    }

    const cleaned = trimmed.replace(/[^\d.]/g, '');

    if (!cleaned) {
        return '';
    }

    // Allow trailing decimal while typing.
    if (cleaned.endsWith('.') && cleaned.indexOf('.') === cleaned.length - 1) {
        const intPart = cleaned.slice(0, -1).replace(/\D/g, '') || '0';

        return `${intPart}.`;
    }

    const parts = cleaned.split('.');
    const intPart = (parts[0] ?? '').replace(/\D/g, '') || '0';
    const frac = parts.slice(1).join('').replace(/\D/g, '').slice(0, 2);

    if (parts.length > 1) {
        return `${intPart}.${frac}`;
    }

    return intPart;
}
