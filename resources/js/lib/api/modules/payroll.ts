import type { PayslipComponent } from '@/lib/payroll/payslip-components';
import {
    apiGet,
    apiPost,
    apiPut,
    apiDelete,
    ensureCsrfCookie,
} from '../client';
import { normalizeError } from '../errors';
import type { PaginationMeta } from '../types';

export type { PayslipComponent };

export type EmployeeSalary = {
    id: number;
    company_id: number;
    employee_id: number;
    employee_code?: string | null;
    employee_name?: string | null;
    amount: string;
    currency: string;
    strategy: string;
    effective_from: string;
    effective_to: string | null;
};

export type CompensationComponent = {
    id?: number;
    employee_id?: number;
    code: string;
    name: string;
    amount: string;
    is_taxable: boolean;
    is_active: boolean;
};

export type PayrollRun = {
    id: number;
    company_id: number;
    name: string;
    period_start: string;
    period_end: string;
    run_type: string;
    status: 'draft' | 'calculated' | 'approved' | 'finalized';
    employee_count: number;
    total_gross: string;
    total_net: string;
    calculated_at: string | null;
    approved_at: string | null;
    finalized_at: string | null;
};

export type PayrollItem = {
    id: number;
    payroll_run_id: number;
    employee_id: number;
    employee_code?: string | null;
    employee_name?: string | null;
    gross: string;
    net: string;
    components: PayslipComponent[];
};

export type Payslip = {
    id: number;
    company_id: number;
    payroll_run_id: number;
    payroll_item_id: number;
    employee_id: number;
    employee_code?: string | null;
    employee_name?: string | null;
    period_start: string;
    period_end: string;
    gross: string;
    net: string;
    components: PayslipComponent[];
    has_pdf: boolean;
};

export async function listSalaries(
    params: {
        employee_id?: number;
        current_only?: boolean;
        page?: number;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.current_only) {
        query.set('current_only', '1');
    }

    if (params.page) {
        query.set('page', String(params.page));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<EmployeeSalary[]>(
        `/api/employee-salaries${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function upsertSalary(
    employeeId: number,
    payload: {
        amount: number | string;
        currency?: string;
        effective_from: string;
        strategy?: string;
    },
) {
    const res = await apiPut<EmployeeSalary>(
        `/api/employees/${employeeId}/salary`,
        payload,
    );

    return res.data;
}

export async function listAllowances(employeeId: number) {
    const res = await apiGet<CompensationComponent[]>(
        `/api/employees/${employeeId}/allowances`,
    );

    return res.data ?? [];
}

export async function replaceAllowances(
    employeeId: number,
    items: Array<Record<string, unknown>>,
) {
    const res = await apiPut<CompensationComponent[]>(
        `/api/employees/${employeeId}/allowances`,
        { items },
    );

    return res.data ?? [];
}

export async function listDeductions(employeeId: number) {
    const res = await apiGet<CompensationComponent[]>(
        `/api/employees/${employeeId}/deductions`,
    );

    return res.data ?? [];
}

export async function replaceDeductions(
    employeeId: number,
    items: Array<Record<string, unknown>>,
) {
    const res = await apiPut<CompensationComponent[]>(
        `/api/employees/${employeeId}/deductions`,
        { items },
    );

    return res.data ?? [];
}

export async function listBonuses(employeeId: number) {
    const res = await apiGet<CompensationComponent[]>(
        `/api/employees/${employeeId}/bonuses`,
    );

    return res.data ?? [];
}

export async function replaceBonuses(
    employeeId: number,
    items: Array<Record<string, unknown>>,
) {
    const res = await apiPut<CompensationComponent[]>(
        `/api/employees/${employeeId}/bonuses`,
        { items },
    );

    return res.data ?? [];
}

export async function listPayrollRuns(params: { per_page?: number } = {}) {
    const query = new URLSearchParams();

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<PayrollRun[]>(`/api/payroll-runs${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getPayrollRun(id: number) {
    const res = await apiGet<PayrollRun>(`/api/payroll-runs/${id}`);

    return res.data;
}

export async function createPayrollRun(payload: {
    name: string;
    period_start: string;
    period_end: string;
}) {
    const res = await apiPost<PayrollRun>('/api/payroll-runs', payload);

    return res.data;
}

export async function updatePayrollRun(
    id: number,
    payload: {
        name: string;
        period_start: string;
        period_end: string;
    },
) {
    const res = await apiPut<PayrollRun>(`/api/payroll-runs/${id}`, payload);

    return res.data;
}

export async function deletePayrollRun(id: number) {
    await apiDelete(`/api/payroll-runs/${id}`);
}

export async function calculatePayrollRun(id: number) {
    const res = await apiPost<PayrollRun>(`/api/payroll-runs/${id}/calculate`);

    return res.data;
}

export async function approvePayrollRun(id: number) {
    const res = await apiPost<PayrollRun>(`/api/payroll-runs/${id}/approve`);

    return res.data;
}

export async function finalizePayrollRun(id: number) {
    const res = await apiPost<PayrollRun>(`/api/payroll-runs/${id}/finalize`);

    return res.data;
}

export async function listPayrollItems(
    runId: number,
    params: { per_page?: number } = {},
) {
    const query = new URLSearchParams();

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<PayrollItem[]>(
        `/api/payroll-runs/${runId}/items${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function listPayslips(
    params: {
        employee_id?: number;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Payslip[]>(`/api/payslips${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getPayslip(id: number) {
    const res = await apiGet<Payslip>(`/api/payslips/${id}`);

    return res.data;
}

export async function downloadPayslip(id: number): Promise<void> {
    await ensureCsrfCookie();

    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    const headers: Record<string, string> = {
        Accept: 'application/pdf, application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf[1]);
    }

    const response = await fetch(`/api/payslips/${id}/download`, {
        method: 'GET',
        credentials: 'same-origin',
        headers,
    });

    if (!response.ok) {
        const body = await response.json().catch(() => null);

        throw normalizeError(response.status, body);
    }

    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `payslip-${id}.pdf`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}
