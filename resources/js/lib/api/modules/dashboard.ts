import { apiGet } from '../client';

export type DashboardScope = 'company' | 'self';

export type AttendanceDayPoint = {
    date: string;
    label: string;
    present: number;
    expected: number;
    rate: number;
};

export type MyAttendanceDayPoint = {
    date: string;
    label: string;
    present: number;
    worked_minutes: number | null;
};

export type StatusCount = {
    status: string;
    count: number;
};

export type DepartmentCount = {
    department_id: number | null;
    name: string;
    count: number;
};

export type RecentHire = {
    id: number;
    code: string;
    full_name: string;
    department_name: string | null;
    hired_at: string | null;
    status: string;
};

export type PendingAction = {
    key: string;
    count: number;
    href: string;
};

export type UpcomingItem = {
    kind: 'holiday' | 'leave' | string;
    date: string;
    title: string;
    employee_name?: string | null;
};

export type ActivityItem = {
    id: number;
    type: string;
    title: string;
    body: string | null;
    created_at: string;
    read_at: string | null;
};

export type SelfEmployee = {
    id: number;
    code: string;
    full_name: string;
    department_name: string | null;
    status: string;
};

export type TodayAttendance = {
    id: number;
    work_date: string | null;
    check_in_at: string | null;
    check_out_at: string | null;
    worked_minutes: number | null;
    status: string;
};

export type LeaveBalanceRow = {
    leave_type_id: number;
    leave_type_code: string;
    leave_type_name: string;
    remaining: number;
    entitled: number;
    used: number;
    pending: number;
};

export type CompanyDashboardSummary = {
    scope: 'company';
    active_employees: number;
    attendance_today_rate: number;
    pending_leave_requests: number;
    new_hires_month: number;
    open_payroll_runs: number;
    unread_notifications: number;
    attendance_last_7_days: AttendanceDayPoint[];
    employees_by_status: StatusCount[];
    employees_by_department: DepartmentCount[];
    recent_hires: RecentHire[];
    pending_actions: PendingAction[];
    upcoming: UpcomingItem[];
    recent_activity: ActivityItem[];
};

export type SelfDashboardSummary = {
    scope: 'self';
    employee: SelfEmployee | null;
    unread_notifications: number;
    today_attendance: TodayAttendance | null;
    checked_in_today: boolean;
    pending_leave_requests: number;
    leave_balances: LeaveBalanceRow[];
    my_attendance_last_7_days: MyAttendanceDayPoint[];
    upcoming: UpcomingItem[];
    pending_actions: PendingAction[];
    recent_activity: ActivityItem[];
};

export type DashboardSummary = CompanyDashboardSummary | SelfDashboardSummary;

export async function getSummary() {
    const res = await apiGet<DashboardSummary>('/api/dashboard/summary');

    return res.data;
}
