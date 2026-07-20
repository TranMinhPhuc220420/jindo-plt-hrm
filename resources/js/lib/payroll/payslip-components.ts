export type PayslipComponent = {
    type: string;
    label: string;
    amount: string;
    code?: string;
};

export type PayslipBreakdown = {
    earnings: PayslipComponent[];
    deductions: PayslipComponent[];
    deductionTotal: number;
};

type TranslateFn = (key: string, options?: Record<string, unknown>) => string;

/** Calculator + seeder earning-side types */
const EARNING_TYPES = new Set([
    'salary',
    'allowance',
    'bonus',
    'overtime',
    'earning',
]);

/** Known English names / calculator labels → i18n keys */
const SYSTEM_LABEL_KEYS: Record<string, string> = {
    base: 'payslips.system_labels.base',
    'base salary': 'payslips.system_labels.base',
    ot: 'payslips.system_labels.ot',
    overtime: 'payslips.system_labels.ot',
    'unpaid leave': 'payslips.system_labels.unpaid_leave',
    unpaid_leave: 'payslips.system_labels.unpaid_leave',
    'meal allowance': 'payslips.system_labels.meal_allowance',
    'social insurance': 'payslips.system_labels.social_insurance',
    earning: 'payslips.system_labels.earning_generic',
    deduction: 'payslips.system_labels.deduction_generic',
};

/** Seed / component codes → i18n keys */
const SYSTEM_CODE_KEYS: Record<string, string> = {
    BASE: 'payslips.system_labels.base',
    MEAL: 'payslips.system_labels.meal_allowance',
    SI: 'payslips.system_labels.social_insurance',
    OT: 'payslips.system_labels.ot',
};

function asAmountString(value: unknown): string | null {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return String(value);
    }

    if (typeof value === 'string' && value.trim() !== '') {
        return value;
    }

    return null;
}

function asOptionalString(value: unknown): string | undefined {
    if (typeof value === 'string' && value.trim() !== '') {
        return value.trim();
    }

    return undefined;
}

/**
 * Normalize API / seeder / calculator component rows.
 * Supports `{ label }` (calculator) and `{ name, code }` (seeder).
 */
export function parsePayslipComponents(
    raw: Array<Record<string, unknown>> | PayslipComponent[] | null | undefined,
): PayslipComponent[] {
    if (!Array.isArray(raw)) {
        return [];
    }

    const result: PayslipComponent[] = [];

    for (const row of raw) {
        if (!row || typeof row !== 'object') {
            continue;
        }

        const fields = row as Record<string, unknown>;
        const type =
            asOptionalString(fields.type)?.toLowerCase() ?? 'other';
        const code = asOptionalString(fields.code);
        const label =
            asOptionalString(fields.label) ??
            asOptionalString(fields.name) ??
            code ??
            type;
        const amount = asAmountString(fields.amount);

        if (amount === null) {
            continue;
        }

        result.push({ type, label, amount, code });
    }

    return result;
}

export function groupPayslipComponents(
    components: PayslipComponent[],
): PayslipBreakdown {
    const earnings: PayslipComponent[] = [];
    const deductions: PayslipComponent[] = [];

    for (const component of components) {
        if (EARNING_TYPES.has(component.type)) {
            earnings.push(component);
            continue;
        }

        if (component.type === 'deduction') {
            deductions.push(component);
            continue;
        }

        const amount = Number(component.amount) || 0;
        if (amount < 0) {
            deductions.push(component);
        } else {
            earnings.push(component);
        }
    }

    return {
        earnings,
        deductions,
        deductionTotal: deductions.reduce(
            (sum, row) => sum + Math.abs(Number(row.amount) || 0),
            0,
        ),
    };
}

function translateOrNull(key: string, t: TranslateFn): string | null {
    const translated = t(key, { ns: 'payroll' });
    return translated !== key ? translated : null;
}

export function localizePayslipType(type: string, t: TranslateFn): string {
    const normalized = type.trim().toLowerCase();
    return (
        translateOrNull(`payslips.component_types.${normalized}`, t) ??
        translateOrNull('payslips.component_types.other', t) ??
        type
    );
}

export function localizePayslipLabel(
    component: Pick<PayslipComponent, 'label' | 'code' | 'type'>,
    t: TranslateFn,
): string {
    if (component.code) {
        const byCode = SYSTEM_CODE_KEYS[component.code.toUpperCase()];
        if (byCode) {
            const translated = translateOrNull(byCode, t);
            if (translated) {
                return translated;
            }
        }
    }

    const byLabel = SYSTEM_LABEL_KEYS[component.label.trim().toLowerCase()];
    if (byLabel) {
        const translated = translateOrNull(byLabel, t);
        if (translated) {
            return translated;
        }
    }

    // Avoid showing raw type keys like "earning" / "deduction" as the title.
    const typeKey = component.label.trim().toLowerCase();
    if (typeKey === 'earning' || typeKey === 'deduction') {
        return (
            translateOrNull(`payslips.system_labels.${typeKey}_generic`, t) ??
            localizePayslipType(component.type, t)
        );
    }

    return component.label;
}

export function formatPayslipPeriod(
    start: string,
    end: string,
    locale?: string,
): string {
    const formatOne = (value: string) => {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            const [y, m, d] = value.split('-').map(Number);
            return new Date(y!, m! - 1, d!).toLocaleDateString(locale);
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString(locale);
    };

    return `${formatOne(start)} – ${formatOne(end)}`;
}

export function employeeDisplayName(slip: {
    employee_code?: string | null;
    employee_name?: string | null;
    employee_id: number;
}): string {
    if (slip.employee_code && slip.employee_name) {
        return `${slip.employee_code} — ${slip.employee_name}`;
    }

    return slip.employee_name ?? `#${slip.employee_id}`;
}
