import { apiGet, apiPatch, apiPost } from '../client';
import type { PaginationMeta } from '../types';

export type ReviewCycleStatus = 'draft' | 'active' | 'finalized';
export type ReviewCycleFramework = 'goal' | 'kpi' | 'okr' | 'mixed';

export type ReviewCycle = {
    id: number;
    company_id: number;
    name: string;
    framework: ReviewCycleFramework;
    status: ReviewCycleStatus;
    starts_on: string | null;
    ends_on: string | null;
    participant_employee_ids: number[];
    participants_count?: number;
    started_at: string | null;
    finalized_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type GoalType = 'goal' | 'kpi' | 'okr';
export type GoalStatus = 'active' | 'completed' | 'cancelled';

export type PerformanceGoal = {
    id: number;
    company_id: number;
    review_cycle_id: number | null;
    employee_id: number;
    employee_name?: string | null;
    title: string;
    description: string | null;
    type: GoalType;
    metric: string | null;
    target: string | null;
    weight: number | null;
    progress: number;
    status: GoalStatus;
    created_at: string | null;
    updated_at: string | null;
};

export type PerformanceEvaluation = {
    id: number;
    company_id: number;
    review_cycle_id: number;
    review_cycle_name?: string | null;
    employee_id: number;
    employee_name?: string | null;
    evaluator_id: number | null;
    overall_score: number;
    summary: string | null;
    ratings: Array<Record<string, unknown>>;
    submitted_at: string | null;
    created_at: string | null;
};

export type PromotionSuggestion = {
    id: number;
    company_id: number;
    employee_id: number;
    employee_name?: string | null;
    review_cycle_id: number | null;
    evaluation_id: number | null;
    overall_score: number;
    status: string;
    note: string | null;
    suggested_at: string | null;
    acknowledged_by: number | null;
    acknowledged_at: string | null;
};

// Review cycles

export async function listCycles(params: { status?: string; per_page?: number } = {}) {
    const query = new URLSearchParams();
    if (params.status) {
        query.set('status', params.status);
    }
    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }
    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<ReviewCycle[]>(
        `/api/performance/review-cycles${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getCycle(id: number) {
    const res = await apiGet<ReviewCycle>(`/api/performance/review-cycles/${id}`);

    return res.data;
}

export async function createCycle(payload: {
    name: string;
    framework?: ReviewCycleFramework;
    starts_on?: string;
    ends_on?: string;
    participant_employee_ids?: number[];
}) {
    const res = await apiPost<ReviewCycle>(
        '/api/performance/review-cycles',
        payload,
    );

    return res.data;
}

export async function startCycle(id: number) {
    const res = await apiPost<ReviewCycle>(
        `/api/performance/review-cycles/${id}/start`,
    );

    return res.data;
}

export async function finalizeCycle(id: number) {
    const res = await apiPost<ReviewCycle>(
        `/api/performance/review-cycles/${id}/finalize`,
    );

    return res.data;
}

// Goals

export async function listGoals(
    params: {
        employee_id?: number;
        review_cycle_id?: number;
        status?: string;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();
    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }
    if (params.review_cycle_id) {
        query.set('review_cycle_id', String(params.review_cycle_id));
    }
    if (params.status) {
        query.set('status', params.status);
    }
    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }
    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<PerformanceGoal[]>(
        `/api/performance/goals${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function createGoal(payload: {
    employee_id: number;
    review_cycle_id?: number;
    title: string;
    description?: string;
    type?: GoalType;
    metric?: string;
    target?: string;
    weight?: number;
    progress?: number;
}) {
    const res = await apiPost<PerformanceGoal>(
        '/api/performance/goals',
        payload,
    );

    return res.data;
}

export async function updateGoal(
    id: number,
    payload: Partial<{
        title: string;
        description: string;
        type: GoalType;
        metric: string;
        target: string;
        weight: number;
        progress: number;
        status: GoalStatus;
    }>,
) {
    const res = await apiPatch<PerformanceGoal>(
        `/api/performance/goals/${id}`,
        payload,
    );

    return res.data;
}

// Evaluations

export async function listEvaluations(
    params: { review_cycle_id?: number; employee_id?: number; per_page?: number } = {},
) {
    const query = new URLSearchParams();
    if (params.review_cycle_id) {
        query.set('review_cycle_id', String(params.review_cycle_id));
    }
    if (params.employee_id) {
        query.set('employee_id', String(params.employee_id));
    }
    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }
    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<PerformanceEvaluation[]>(
        `/api/performance/evaluations${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function submitEvaluation(payload: {
    review_cycle_id: number;
    employee_id: number;
    overall_score: number;
    summary?: string;
    ratings?: Array<{ criterion: string; score: number }>;
}) {
    const res = await apiPost<PerformanceEvaluation>(
        '/api/performance/evaluations',
        payload,
    );

    return res.data;
}

// Promotion suggestions

export async function listPromotionSuggestions(
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
    const res = await apiGet<PromotionSuggestion[]>(
        `/api/performance/promotion-suggestions${suffix}`,
    );

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function acknowledgePromotionSuggestion(id: number) {
    const res = await apiPost<PromotionSuggestion>(
        `/api/performance/promotion-suggestions/${id}/acknowledge`,
    );

    return res.data;
}
