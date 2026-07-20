import { apiGet, apiPost } from '../client';
import type { PaginationMeta } from '../types';

export type OnboardingTaskStatus = 'pending' | 'done';

export type OnboardingTask = {
    id: number;
    company_id: number;
    onboarding_case_id: number;
    key: string;
    title: string;
    description: string | null;
    mandatory: boolean;
    assignee_type: string | null;
    status: OnboardingTaskStatus;
    sort_order: number;
    completed_at: string | null;
    completed_by: number | null;
};

export type OnboardingProgress = {
    done: number;
    total: number;
    mandatory_remaining: number;
};

export type OnboardingCaseStatus = 'in_progress' | 'completed';

export type OnboardingCase = {
    id: number;
    company_id: number;
    employee_id: number;
    offer_id: number | null;
    candidate_id: number | null;
    onboarding_template_id: number | null;
    status: OnboardingCaseStatus;
    probation_ends_on: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string | null;
    progress?: OnboardingProgress;
    tasks?: OnboardingTask[];
};

export type OnboardingTemplateItem = {
    id: number;
    onboarding_template_id: number;
    key: string;
    title: string;
    description: string | null;
    mandatory: boolean;
    assignee_type: string | null;
    sort_order: number;
};

export type OnboardingTemplate = {
    id: number;
    company_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
    items?: OnboardingTemplateItem[];
};

export async function listCases(
    params: { status?: string; employee_id?: number; per_page?: number } = {},
) {
    const query = new URLSearchParams();

    if (params.status) {
        query.set('status', params.status);
    }

    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<OnboardingCase[]>(
        `/api/onboarding-cases${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getCase(id: number) {
    const res = await apiGet<OnboardingCase>(`/api/onboarding-cases/${id}`);

    return res.data;
}

export async function startCase(payload: {
    employee_id: number;
    template_id?: number;
    offer_id?: number;
    probation_ends_on?: string;
}) {
    const res = await apiPost<OnboardingCase>('/api/onboarding-cases', payload);

    return res.data;
}

export async function completeCase(id: number) {
    const res = await apiPost<OnboardingCase>(
        `/api/onboarding-cases/${id}/complete`,
    );

    return res.data;
}

export async function listCaseTasks(id: number) {
    const res = await apiGet<OnboardingTask[]>(
        `/api/onboarding-cases/${id}/tasks`,
    );

    return res.data ?? [];
}

export async function completeTask(taskId: number) {
    const res = await apiPost<OnboardingTask>(
        `/api/onboarding-tasks/${taskId}/complete`,
    );

    return res.data;
}

export async function reopenTask(taskId: number) {
    const res = await apiPost<OnboardingTask>(
        `/api/onboarding-tasks/${taskId}/reopen`,
    );

    return res.data;
}

export async function listTemplates(params: { per_page?: number } = {}) {
    const query = new URLSearchParams();

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<OnboardingTemplate[]>(
        `/api/onboarding-templates${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}
