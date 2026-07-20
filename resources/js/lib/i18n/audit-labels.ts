import type { TFunction } from 'i18next';

/** Sentinel for “no filter” in Radix Select (empty string is not a valid item value). */
export const AUDIT_FILTER_ALL = '__all__';

/**
 * All known audit action codes written by services.
 * Keep in sync with `AuditLogger::write(action: …)` call sites and `audit:actions.*`.
 */
export const AUDIT_ACTION_CODES: readonly string[] = [
    'settings.updated',
    'company.updated',
    'branch.created',
    'branch.updated',
    'branch.deleted',
    'department.created',
    'department.updated',
    'department.deleted',
    'team.created',
    'team.updated',
    'team.deleted',
    'position.created',
    'position.updated',
    'position.deleted',
    'role.created',
    'role.updated',
    'role.deleted',
    'role.permissions_synced',
    'user.roles_synced',
    'employee.created',
    'employee.updated',
    'employee.archived',
    'employee.status_changed',
    'employee.emergency_contacts_updated',
    'employee.education_created',
    'employee.education_updated',
    'employee.education_deleted',
    'employee.work_history_created',
    'employee.work_history_updated',
    'employee.work_history_deleted',
    'employee.family_member_created',
    'employee.family_member_updated',
    'employee.family_member_deleted',
    'employee.contract_created',
    'employee.contract_updated',
    'employee.bank_account_updated',
    'employee.tax_profile_updated',
    'employee.insurance_updated',
    'shift.created',
    'shift.updated',
    'shift.deleted',
    'shift.assignment_created',
    'shift.assignment_updated',
    'shift.assignment_deleted',
    'overtime_rules.updated',
    'attendance.checked_in',
    'attendance.checked_out',
    'attendance.record_approved',
    'attendance.period_locked',
    'attendance.correction_requested',
    'attendance.correction_approved',
    'attendance.correction_rejected',
    'leave.requested',
    'leave.cancelled',
    'leave.approved',
    'leave.rejected',
    'leave.balance_adjusted',
    'leave.type_created',
    'leave.type_updated',
    'leave.holiday_created',
    'leave.holiday_deleted',
    'leave.weekend_rules_updated',
    'payroll.run_created',
    'payroll.run_calculated',
    'payroll.run_approved',
    'payroll.run_finalized',
    'payroll.salary_changed',
    'payroll.allowances_replaced',
    'payroll.deductions_replaced',
    'payroll.bonuses_replaced',
    'document.uploaded',
    'document.updated',
    'document.deleted',
    'asset.created',
    'asset.updated',
    'asset.retired',
    'asset.assigned',
    'asset.returned',
    'asset.maintenance_added',
    'asset.damage_reported',
    'asset.replaced',
    'job_opening.created',
    'job_opening.updated',
    'job_opening.closed',
    'candidate.created',
    'candidate.updated',
    'candidate.stage_changed',
    'interview.scheduled',
    'interview.evaluated',
    'offer.created',
    'offer.sent',
    'offer.accepted',
    'offer.rejected',
    'onboarding.started',
    'onboarding.task_completed',
    'onboarding.task_reopened',
    'onboarding.completed',
    'onboarding.template_created',
    'onboarding.template_updated',
    'onboarding.account_provisioned',
    'performance.goal_created',
    'performance.goal_updated',
    'performance.cycle_created',
    'performance.cycle_started',
    'performance.cycle_finalized',
    'performance.evaluation_submitted',
    'performance.promotion_suggested',
    'performance.promotion_acknowledged',
];

/**
 * Subject filter options: display basename → morph class stored in `audit_logs.subject_type`.
 */
export const AUDIT_SUBJECT_OPTIONS: readonly {
    basename: string;
    morph: string;
}[] = [
    { basename: 'Company', morph: 'App\\Models\\Company' },
    { basename: 'Branch', morph: 'App\\Models\\Branch' },
    { basename: 'Department', morph: 'App\\Models\\Department' },
    { basename: 'Team', morph: 'App\\Models\\Team' },
    { basename: 'Position', morph: 'App\\Models\\Position' },
    { basename: 'Employee', morph: 'App\\Models\\Employee' },
    { basename: 'Role', morph: 'App\\Models\\Role' },
    { basename: 'User', morph: 'App\\Models\\User' },
    { basename: 'LeaveRequest', morph: 'App\\Models\\LeaveRequest' },
    { basename: 'LeaveType', morph: 'App\\Models\\LeaveType' },
    { basename: 'LeaveBalance', morph: 'App\\Models\\LeaveBalance' },
    { basename: 'Holiday', morph: 'App\\Models\\Holiday' },
    { basename: 'WeekendRule', morph: 'App\\Models\\WeekendRule' },
    { basename: 'Shift', morph: 'App\\Models\\Shift' },
    { basename: 'ShiftAssignment', morph: 'App\\Models\\ShiftAssignment' },
    { basename: 'AttendanceRecord', morph: 'App\\Models\\AttendanceRecord' },
    { basename: 'AttendanceCorrection', morph: 'App\\Models\\AttendanceCorrection' },
    { basename: 'PayrollRun', morph: 'App\\Models\\PayrollRun' },
    { basename: 'EmployeeSalary', morph: 'App\\Models\\EmployeeSalary' },
    { basename: 'Document', morph: 'App\\Models\\Document' },
    { basename: 'Asset', morph: 'App\\Models\\Asset' },
    { basename: 'JobOpening', morph: 'App\\Models\\JobOpening' },
    { basename: 'Candidate', morph: 'App\\Models\\Candidate' },
    { basename: 'Interview', morph: 'App\\Models\\Interview' },
    { basename: 'Offer', morph: 'App\\Models\\Offer' },
    { basename: 'OnboardingCase', morph: 'App\\Models\\OnboardingCase' },
    { basename: 'OnboardingTask', morph: 'App\\Models\\OnboardingTask' },
    { basename: 'OnboardingTemplate', morph: 'App\\Models\\OnboardingTemplate' },
    { basename: 'PerformanceGoal', morph: 'App\\Models\\PerformanceGoal' },
    { basename: 'PerformanceReviewCycle', morph: 'App\\Models\\PerformanceReviewCycle' },
    { basename: 'PerformanceEvaluation', morph: 'App\\Models\\PerformanceEvaluation' },
    {
        basename: 'PerformancePromotionSuggestion',
        morph: 'App\\Models\\PerformancePromotionSuggestion',
    },
];

/**
 * Resolve a human-readable audit action label.
 * Action codes use dotted form (e.g. `settings.updated`) which maps to
 * nested keys under `audit:actions.*`.
 */
export function auditActionLabel(
    t: TFunction,
    action: string | null | undefined,
): string {
    if (!action) {
        return t('empty_value', { ns: 'common' });
    }

    return t(`actions.${action}`, {
        ns: 'audit',
        defaultValue: action,
    });
}

/**
 * Strip morph / FQCN to the model basename (e.g. `App\\Models\\Company` → `Company`).
 */
export function auditSubjectBasename(
    subjectType: string | null | undefined,
): string | null {
    if (!subjectType) {
        return null;
    }

    const normalized = subjectType.replace(/\\\\/g, '\\');
    const parts = normalized.split(/[\\/]/);
    const last = parts[parts.length - 1];

    return last || null;
}

export function auditSubjectTypeLabel(
    t: TFunction,
    subjectType: string | null | undefined,
): string {
    const basename = auditSubjectBasename(subjectType);

    if (!basename) {
        return t('empty_value', { ns: 'common' });
    }

    return t(`subjects.${basename}`, {
        ns: 'audit',
        defaultValue: basename,
    });
}

export function auditSubjectLabel(
    t: TFunction,
    subjectType: string | null | undefined,
    subjectId: number | string | null | undefined,
): string {
    const typeLabel = auditSubjectTypeLabel(t, subjectType);

    if (
        !subjectType ||
        subjectId === null ||
        subjectId === undefined ||
        subjectId === ''
    ) {
        return subjectType ? typeLabel : t('empty_value', { ns: 'common' });
    }

    return t('subject_with_id', {
        ns: 'audit',
        type: typeLabel,
        id: subjectId,
    });
}

export function auditActorLabel(
    t: TFunction,
    actorId: number | string | null | undefined,
    actorType?: string | null,
): string {
    if (actorId === null || actorId === undefined || actorId === '') {
        return t('empty_value', { ns: 'common' });
    }

    const basename = auditSubjectBasename(actorType);

    if (basename && basename !== 'User') {
        return t('subject_with_id', {
            ns: 'audit',
            type: t(`subjects.${basename}`, {
                ns: 'audit',
                defaultValue: basename,
            }),
            id: actorId,
        });
    }

    return t('actor_user', { ns: 'audit', id: actorId });
}

/** Group action codes by domain prefix for filter select optgroups. */
export function auditActionsByDomain(): {
    domain: string;
    actions: string[];
}[] {
    const map = new Map<string, string[]>();

    for (const code of AUDIT_ACTION_CODES) {
        const domain = code.split('.')[0] ?? code;
        const list = map.get(domain) ?? [];
        list.push(code);
        map.set(domain, list);
    }

    return [...map.entries()].map(([domain, actions]) => ({
        domain,
        actions,
    }));
}
