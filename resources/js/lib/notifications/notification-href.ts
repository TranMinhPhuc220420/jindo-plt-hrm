import type { Notification } from '@/lib/api/modules/notifications';

/**
 * Map notification `data` payload + `type` to an in-app href when known.
 */
export function notificationHref(item: Notification): string | null {
    const data = item.data ?? {};
    const type = item.type;

    if (type.startsWith('leave.')) {
        return '/leave';
    }

    if (type.startsWith('attendance.')) {
        return '/attendance';
    }

    if (type.startsWith('shift.')) {
        return '/shifts';
    }

    if (type.startsWith('asset.') && typeof data.asset_id === 'number') {
        return `/assets/${data.asset_id}`;
    }

    if (type.startsWith('asset.')) {
        return '/assets';
    }

    if (type === 'payroll.finalized' || type === 'payroll.salary_changed') {
        return '/payroll/payslips';
    }

    if (type.startsWith('payroll.')) {
        return '/payroll';
    }

    if (type.startsWith('performance.')) {
        return '/performance';
    }

    if (type.startsWith('onboarding.')) {
        return '/onboarding';
    }

    if (type.startsWith('employee.') && typeof data.employee_id === 'number') {
        return `/employees/${data.employee_id}`;
    }

    if (type.startsWith('employee.')) {
        return '/employees';
    }

    if (type.startsWith('report.')) {
        return '/reports';
    }

    if (
        type.startsWith('recruitment.') &&
        typeof data.candidate_id === 'number'
    ) {
        return `/recruitment/candidates/${data.candidate_id}`;
    }

    if (type.startsWith('recruitment.')) {
        return '/recruitment';
    }

    if (type.startsWith('document.')) {
        return '/documents';
    }

    if (type.startsWith('broadcast.') || type.startsWith('push.')) {
        return '/notifications';
    }

    return null;
}
