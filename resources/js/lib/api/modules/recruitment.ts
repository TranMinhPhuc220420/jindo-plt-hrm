import { apiGet, apiPatch, apiPost } from '../client';
import type { PaginationMeta } from '../types';

export type JobOpeningStatus = 'open' | 'closed';

export type JobOpening = {
    id: number;
    company_id: number;
    code: string | null;
    title: string;
    department_id: number | null;
    position_id: number | null;
    description: string | null;
    headcount: number | null;
    status: JobOpeningStatus;
    opened_at: string | null;
    closed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type CandidateStage =
    | 'applied'
    | 'screening'
    | 'interview'
    | 'offer'
    | 'hired'
    | 'rejected'
    | 'withdrawn';

export type Candidate = {
    id: number;
    company_id: number;
    job_opening_id: number;
    full_name: string;
    email: string | null;
    phone: string | null;
    stage: CandidateStage;
    source: string | null;
    resume_document_id: number | null;
    employee_id: number | null;
    created_at: string | null;
    updated_at: string | null;
};

export type Interview = {
    id: number;
    company_id: number;
    candidate_id: number;
    scheduled_at: string | null;
    mode: string | null;
    location: string | null;
    interviewer_id: number | null;
    status: string;
    notes: string | null;
    created_at: string | null;
};

export type CandidateEvaluation = {
    id: number;
    company_id: number;
    interview_id: number;
    candidate_id: number;
    evaluator_id: number | null;
    rating: number | null;
    recommendation: string | null;
    comments: string | null;
    created_at: string | null;
};

export type OfferStatus = 'draft' | 'sent' | 'accepted' | 'rejected';

export type Offer = {
    id: number;
    company_id: number;
    candidate_id: number;
    title: string | null;
    salary_amount: string | null;
    currency: string | null;
    start_date: string | null;
    probation_ends_on: string | null;
    status: string;
    sent_at: string | null;
    accepted_at: string | null;
    rejected_at: string | null;
    notes: string | null;
    onboarding_case_id?: number | null;
    created_at: string | null;
};

// Job openings

export async function listJobOpenings(
    params: { status?: string; search?: string; per_page?: number } = {},
) {
    const query = new URLSearchParams();

    if (params.status) {
        query.set('status', params.status);
    }

    if (params.search) {
        query.set('search', params.search);
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<JobOpening[]>(`/api/job-openings${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getJobOpening(id: number) {
    const res = await apiGet<JobOpening>(`/api/job-openings/${id}`);

    return res.data;
}

export async function createJobOpening(payload: {
    title: string;
    code?: string;
    department_id?: number;
    position_id?: number;
    description?: string;
    headcount?: number;
    opened_at?: string;
}) {
    const res = await apiPost<JobOpening>('/api/job-openings', payload);

    return res.data;
}

export async function closeJobOpening(id: number) {
    const res = await apiPost<JobOpening>(`/api/job-openings/${id}/close`);

    return res.data;
}

// Candidates

export async function listCandidates(
    params: {
        job_opening_id?: number;
        stage?: string;
        search?: string;
        per_page?: number;
    } = {},
) {
    const query = new URLSearchParams();

    if (params.job_opening_id) {
        query.set('job_opening_id', String(params.job_opening_id));
    }

    if (params.stage) {
        query.set('stage', params.stage);
    }

    if (params.search) {
        query.set('search', params.search);
    }

    if (params.per_page) {
        query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString() ? `?${query.toString()}` : '';
    const res = await apiGet<Candidate[]>(`/api/candidates${suffix}`);

    return {
        data: res.data ?? [],
        meta: res.meta as PaginationMeta | undefined,
    };
}

export async function getCandidate(id: number) {
    const res = await apiGet<Candidate>(`/api/candidates/${id}`);

    return res.data;
}

export async function createCandidate(payload: {
    job_opening_id: number;
    full_name: string;
    email?: string;
    phone?: string;
    stage?: CandidateStage;
    source?: string;
}) {
    const res = await apiPost<Candidate>('/api/candidates', payload);

    return res.data;
}

export async function changeCandidateStage(id: number, stage: CandidateStage) {
    const res = await apiPost<Candidate>(`/api/candidates/${id}/stage`, {
        stage,
    });

    return res.data;
}

export async function hireCandidate(id: number) {
    const res = await apiPost<Candidate>(`/api/candidates/${id}/hire`);

    return res.data;
}

// Interviews

export async function listInterviews(candidateId: number) {
    const res = await apiGet<Interview[]>(
        `/api/candidates/${candidateId}/interviews`,
    );

    return res.data ?? [];
}

export async function scheduleInterview(
    candidateId: number,
    payload: {
        scheduled_at?: string;
        mode?: string;
        location?: string;
        interviewer_id?: number;
        status?: string;
        notes?: string;
    },
) {
    const res = await apiPost<Interview>(
        `/api/candidates/${candidateId}/interviews`,
        payload,
    );

    return res.data;
}

export async function submitEvaluation(
    interviewId: number,
    payload: { rating?: number; recommendation?: string; comments?: string },
) {
    const res = await apiPost<CandidateEvaluation>(
        `/api/interviews/${interviewId}/evaluation`,
        payload,
    );

    return res.data;
}

// Offers

export async function listOffers(candidateId: number) {
    const res = await apiGet<Offer[]>(`/api/candidates/${candidateId}/offers`);

    return res.data ?? [];
}

export async function createOffer(
    candidateId: number,
    payload: {
        title?: string;
        salary_amount?: number | string;
        currency?: string;
        start_date?: string;
        probation_ends_on?: string;
        notes?: string;
    },
) {
    const res = await apiPost<Offer>(
        `/api/candidates/${candidateId}/offers`,
        payload,
    );

    return res.data;
}

export async function sendOffer(offerId: number) {
    const res = await apiPost<Offer>(`/api/offers/${offerId}/send`);

    return res.data;
}

export async function acceptOffer(
    offerId: number,
    payload: { accepted_at?: string } = {},
) {
    const res = await apiPost<Offer>(`/api/offers/${offerId}/accept`, payload);

    return res.data;
}

export async function rejectOffer(offerId: number) {
    const res = await apiPost<Offer>(`/api/offers/${offerId}/reject`);

    return res.data;
}

export async function updateJobOpening(
    id: number,
    payload: Partial<{
        title: string;
        code: string;
        department_id: number;
        position_id: number;
        description: string;
        headcount: number;
    }>,
) {
    const res = await apiPatch<JobOpening>(`/api/job-openings/${id}`, payload);

    return res.data;
}
