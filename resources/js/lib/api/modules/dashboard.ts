import { apiGet } from '../client';

export type DashboardSummary = {
    active_employees: number;
    pending_leave_requests: number;
    open_payroll_runs: number;
    unread_notifications: number;
};

export async function getSummary() {
    const res = await apiGet<DashboardSummary>('/api/dashboard/summary');

    return res.data;
}
