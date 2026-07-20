<?php

namespace App\Support;

/**
 * Canonical permission keys for seeded / shipped modules.
 *
 * @see docs/01-architecture/PERMISSIONS_CATALOG.md
 */
final class PermissionCatalog
{
    public const FOUNDATION = [
        'can_view_organization' => [
            'name' => 'View organization',
            'group' => 'organization',
            'description' => 'View the organization tree (company, branches, departments, teams, and positions).',
        ],
        'can_manage_organization' => [
            'name' => 'Manage organization',
            'group' => 'organization',
            'description' => 'Create, update, and remove branches, departments, teams, and positions.',
        ],
        'can_manage_company' => [
            'name' => 'Manage company',
            'group' => 'organization',
            'description' => 'Update the company profile and company-level organization settings.',
        ],
        'can_view_roles' => [
            'name' => 'View roles',
            'group' => 'authorization',
            'description' => 'List roles and browse the permission catalog.',
        ],
        'can_manage_roles' => [
            'name' => 'Manage roles',
            'group' => 'authorization',
            'description' => 'Create and delete roles and change which permissions each role has.',
        ],
        'can_assign_roles' => [
            'name' => 'Assign roles',
            'group' => 'authorization',
            'description' => 'Attach or detach roles for user accounts.',
        ],
        'can_view_settings' => [
            'name' => 'View settings',
            'group' => 'settings',
            'description' => 'View company and system settings values.',
        ],
        'can_manage_settings' => [
            'name' => 'Manage settings',
            'group' => 'settings',
            'description' => 'Change company and system settings values.',
        ],
        'can_view_audit_logs' => [
            'name' => 'View audit logs',
            'group' => 'audit',
            'description' => 'Browse the audit trail of sensitive actions in the system.',
        ],
    ];

    public const EMPLOYEE = [
        'can_view_employee' => [
            'name' => 'View employees',
            'group' => 'employee',
            'description' => 'List and view employee profiles within the allowed scope.',
        ],
        'can_create_employee' => [
            'name' => 'Create employees',
            'group' => 'employee',
            'description' => 'Create new employee records.',
        ],
        'can_update_employee' => [
            'name' => 'Update employees',
            'group' => 'employee',
            'description' => 'Update non-sensitive employee profile fields.',
        ],
        'can_manage_employee_sensitive' => [
            'name' => 'Manage employee sensitive data',
            'group' => 'employee',
            'description' => 'View and edit sensitive fields such as bank, tax, and insurance data.',
        ],
        'can_change_employee_status' => [
            'name' => 'Change employee status',
            'group' => 'employee',
            'description' => 'Change employment status and archive or reactivate employees.',
        ],
        'can_view_own_profile' => [
            'name' => 'View own employee profile',
            'group' => 'employee',
            'description' => "View the signed-in user's own limited employee profile.",
        ],
    ];

    public const SHIFT = [
        'can_view_shifts' => [
            'name' => 'View shifts',
            'group' => 'shift',
            'description' => 'View shift definitions and assignments.',
        ],
        'can_manage_shift_definitions' => [
            'name' => 'Manage shift definitions',
            'group' => 'shift',
            'description' => 'Create, update, and remove shift definitions.',
        ],
        'can_assign_shifts' => [
            'name' => 'Assign shifts',
            'group' => 'shift',
            'description' => 'Assign shifts to employees and manage assignment changes.',
        ],
        'can_manage_overtime_rules' => [
            'name' => 'Manage overtime rules',
            'group' => 'shift',
            'description' => 'Configure overtime calculation rules.',
        ],
        'can_view_own_schedule' => [
            'name' => 'View own schedule',
            'group' => 'shift',
            'description' => "View the signed-in user's own work schedule.",
        ],
    ];

    public const ATTENDANCE = [
        'can_check_in_out' => [
            'name' => 'Check in and out',
            'group' => 'attendance',
            'description' => "Record the signed-in user's own check-in and check-out punches.",
        ],
        'can_view_attendance' => [
            'name' => 'View attendance',
            'group' => 'attendance',
            'description' => 'View attendance lists and summaries within the allowed scope.',
        ],
        'can_request_attendance_correction' => [
            'name' => 'Request attendance corrections',
            'group' => 'attendance',
            'description' => 'Submit requests to correct attendance records.',
        ],
        'can_approve_attendance' => [
            'name' => 'Approve attendance',
            'group' => 'attendance',
            'description' => 'Approve or reject attendance records and correction requests.',
        ],
        'can_manage_attendance' => [
            'name' => 'Manage attendance',
            'group' => 'attendance',
            'description' => 'Perform HR overrides such as locking periods or adjusting attendance data.',
        ],
    ];

    public const LEAVE = [
        'can_request_leave' => [
            'name' => 'Request leave',
            'group' => 'leave',
            'description' => "Create and cancel the signed-in user's own leave requests.",
        ],
        'can_view_leave' => [
            'name' => 'View leave',
            'group' => 'leave',
            'description' => 'View leave requests and balances within the allowed scope.',
        ],
        'can_approve_leave' => [
            'name' => 'Approve leave',
            'group' => 'leave',
            'description' => 'Approve or reject leave requests.',
        ],
        'can_manage_leave_types' => [
            'name' => 'Manage leave types',
            'group' => 'leave',
            'description' => 'Create, update, and remove leave types.',
        ],
        'can_manage_leave_balances' => [
            'name' => 'Manage leave balances',
            'group' => 'leave',
            'description' => 'Manually adjust employee leave balances.',
        ],
        'can_manage_holidays' => [
            'name' => 'Manage holidays and weekend rules',
            'group' => 'leave',
            'description' => 'Configure public holidays and weekend rules used by leave calculations.',
        ],
    ];

    public const PAYROLL = [
        'can_view_salary' => [
            'name' => 'View salary and payslips',
            'group' => 'payroll',
            'description' => 'View salary details and payslips within the allowed scope.',
        ],
        'can_manage_salary' => [
            'name' => 'Manage salary components',
            'group' => 'payroll',
            'description' => 'Edit compensation components such as base salary, allowances, and deductions.',
        ],
        'can_run_payroll' => [
            'name' => 'Run payroll calculations',
            'group' => 'payroll',
            'description' => 'Create payroll runs and calculate payroll results.',
        ],
        'can_approve_payroll' => [
            'name' => 'Approve payroll runs',
            'group' => 'payroll',
            'description' => 'Approve payroll runs before finalization.',
        ],
        'can_view_payroll_history' => [
            'name' => 'View payroll history',
            'group' => 'payroll',
            'description' => 'Browse historical payroll runs and outcomes.',
        ],
        'can_manage_payslips' => [
            'name' => 'Manage payslips',
            'group' => 'payroll',
            'description' => 'Perform administrative payslip operations such as regeneration or publishing.',
        ],
    ];

    public const DOCUMENTS = [
        'can_view_company_documents' => [
            'name' => 'View company documents',
            'group' => 'documents',
            'description' => 'View company-level documents and files.',
        ],
        'can_manage_company_documents' => [
            'name' => 'Manage company documents',
            'group' => 'documents',
            'description' => 'Upload, update, and remove company-level documents.',
        ],
        'can_view_employee_documents' => [
            'name' => 'View employee documents',
            'group' => 'documents',
            'description' => 'View employee documents within the allowed scope.',
        ],
        'can_manage_employee_documents' => [
            'name' => 'Manage employee documents',
            'group' => 'documents',
            'description' => 'Upload, update, and remove employee documents within allowed scope.',
        ],
        'can_upload_own_documents' => [
            'name' => 'Upload own documents',
            'group' => 'documents',
            'description' => "Upload documents for the signed-in user's own employee record.",
        ],
        'can_manage_document_templates' => [
            'name' => 'Manage document templates',
            'group' => 'documents',
            'description' => 'Configure document templates and related document policies.',
        ],
    ];

    public const ASSETS = [
        'can_view_assets' => [
            'name' => 'View assets',
            'group' => 'assets',
            'description' => 'View the company asset inventory.',
        ],
        'can_manage_assets' => [
            'name' => 'Manage assets',
            'group' => 'assets',
            'description' => 'Create, update, and remove asset records.',
        ],
        'can_assign_asset' => [
            'name' => 'Assign assets',
            'group' => 'assets',
            'description' => 'Assign assets to employees.',
        ],
        'can_return_asset' => [
            'name' => 'Return assets',
            'group' => 'assets',
            'description' => 'Process asset returns from employees.',
        ],
        'can_report_asset_damage' => [
            'name' => 'Report asset damage',
            'group' => 'assets',
            'description' => 'Report damage or issues for assigned assets.',
        ],
        'can_manage_asset_maintenance' => [
            'name' => 'Manage asset maintenance',
            'group' => 'assets',
            'description' => 'Track and manage asset maintenance activities.',
        ],
    ];

    public const RECRUITMENT = [
        'can_manage_job_positions' => [
            'name' => 'Manage job openings',
            'group' => 'recruitment',
            'description' => 'Create and manage job openings used in recruitment.',
        ],
        'can_view_candidates' => [
            'name' => 'View candidates',
            'group' => 'recruitment',
            'description' => 'View candidates in the recruitment pipeline.',
        ],
        'can_manage_candidates' => [
            'name' => 'Manage candidates',
            'group' => 'recruitment',
            'description' => 'Create, update, and move candidates through the hiring pipeline.',
        ],
        'can_manage_interviews' => [
            'name' => 'Manage interviews',
            'group' => 'recruitment',
            'description' => 'Schedule and manage candidate interviews.',
        ],
        'can_create_offer' => [
            'name' => 'Create offers',
            'group' => 'recruitment',
            'description' => 'Create job offers for candidates.',
        ],
        'can_approve_offer' => [
            'name' => 'Approve and send offers',
            'group' => 'recruitment',
            'description' => 'Approve offers and send them to candidates when dual control is required.',
        ],
        'can_hire_candidate' => [
            'name' => 'Hire candidate / accept offer',
            'group' => 'recruitment',
            'description' => 'Mark a candidate as hired and hand off to onboarding.',
        ],
    ];

    public const ONBOARDING = [
        'can_view_onboarding' => [
            'name' => 'View onboarding',
            'group' => 'onboarding',
            'description' => 'View onboarding cases and progress.',
        ],
        'can_manage_onboarding' => [
            'name' => 'Manage onboarding',
            'group' => 'onboarding',
            'description' => 'Create and configure onboarding cases for new hires.',
        ],
        'can_complete_onboarding_task' => [
            'name' => 'Complete onboarding tasks',
            'group' => 'onboarding',
            'description' => 'Complete onboarding tasks assigned to the signed-in user.',
        ],
        'can_complete_onboarding' => [
            'name' => 'Complete onboarding cases',
            'group' => 'onboarding',
            'description' => 'Mark an onboarding case as fully completed.',
        ],
        'can_manage_onboarding_templates' => [
            'name' => 'Manage onboarding templates',
            'group' => 'onboarding',
            'description' => 'Create and maintain onboarding templates used for new hires.',
        ],
    ];

    public const PERFORMANCE = [
        'can_view_performance' => [
            'name' => 'View performance',
            'group' => 'performance',
            'description' => 'View performance cycles, goals, and evaluations within allowed scope.',
        ],
        'can_manage_goals' => [
            'name' => 'Manage goals',
            'group' => 'performance',
            'description' => 'Create and update employee goals / KPI / OKR items.',
        ],
        'can_evaluate_employee' => [
            'name' => 'Evaluate employees',
            'group' => 'performance',
            'description' => 'Submit performance evaluations for eligible employees.',
        ],
        'can_manage_review_cycles' => [
            'name' => 'Manage review cycles',
            'group' => 'performance',
            'description' => 'Create, start, and finalize performance review cycles.',
        ],
        'can_view_promotion_suggestions' => [
            'name' => 'View promotion suggestions',
            'group' => 'performance',
            'description' => 'View advisory promotion suggestions from evaluations.',
        ],
        'can_manage_performance_settings' => [
            'name' => 'Manage performance settings',
            'group' => 'performance',
            'description' => 'Configure performance framework settings for the company.',
        ],
    ];

    public const REPORTS = [
        'can_view_attendance_reports' => [
            'name' => 'View attendance reports',
            'group' => 'reports',
            'description' => 'Run and view attendance reports.',
        ],
        'can_view_payroll_reports' => [
            'name' => 'View payroll reports',
            'group' => 'reports',
            'description' => 'Run and view sensitive payroll reports.',
        ],
        'can_view_leave_reports' => [
            'name' => 'View leave reports',
            'group' => 'reports',
            'description' => 'Run and view leave reports.',
        ],
        'can_view_employee_reports' => [
            'name' => 'View employee reports',
            'group' => 'reports',
            'description' => 'Run and view employee and headcount reports.',
        ],
        'can_view_performance_reports' => [
            'name' => 'View performance reports',
            'group' => 'reports',
            'description' => 'Run and view performance reports.',
        ],
        'can_manage_custom_reports' => [
            'name' => 'Manage custom reports',
            'group' => 'reports',
            'description' => 'Create and manage custom report definitions (reserved).',
        ],
        'can_export_reports' => [
            'name' => 'Export reports',
            'group' => 'reports',
            'description' => 'Queue and download report exports.',
        ],
    ];

    public const NOTIFICATIONS = [
        'can_view_own_notifications' => [
            'name' => 'View own notifications',
            'group' => 'notifications',
            'description' => 'View and manage the signed-in user notification inbox.',
        ],
        'can_manage_notification_templates' => [
            'name' => 'Manage notification templates',
            'group' => 'notifications',
            'description' => 'Manage notification templates (admin; reserved for MVP).',
        ],
        'can_send_broadcast_notification' => [
            'name' => 'Send broadcast notifications',
            'group' => 'notifications',
            'description' => 'Send broadcast notifications to users (reserved for MVP).',
        ],
        'can_manage_notification_settings' => [
            'name' => 'Manage notification settings',
            'group' => 'notifications',
            'description' => 'Manage company notification defaults and settings.',
        ],
    ];

    /**
     * @return array<string, array{name: string, group: string, description: string}>
     */
    public static function all(): array
    {
        return self::FOUNDATION
            + self::EMPLOYEE
            + self::SHIFT
            + self::ATTENDANCE
            + self::LEAVE
            + self::PAYROLL
            + self::DOCUMENTS
            + self::ASSETS
            + self::RECRUITMENT
            + self::ONBOARDING
            + self::PERFORMANCE
            + self::REPORTS
            + self::NOTIFICATIONS;
    }

    /**
     * @return list<string>
     */
    public static function foundationKeys(): array
    {
        return array_keys(self::FOUNDATION);
    }

    /**
     * @return list<string>
     */
    public static function employeeKeys(): array
    {
        return array_keys(self::EMPLOYEE);
    }

    /**
     * @return list<string>
     */
    public static function shiftKeys(): array
    {
        return array_keys(self::SHIFT);
    }

    /**
     * @return list<string>
     */
    public static function attendanceKeys(): array
    {
        return array_keys(self::ATTENDANCE);
    }

    /**
     * @return list<string>
     */
    public static function leaveKeys(): array
    {
        return array_keys(self::LEAVE);
    }

    /**
     * @return list<string>
     */
    public static function payrollKeys(): array
    {
        return array_keys(self::PAYROLL);
    }

    /**
     * @return list<string>
     */
    public static function documentKeys(): array
    {
        return array_keys(self::DOCUMENTS);
    }

    /**
     * @return list<string>
     */
    public static function assetKeys(): array
    {
        return array_keys(self::ASSETS);
    }

    /**
     * @return list<string>
     */
    public static function recruitmentKeys(): array
    {
        return array_keys(self::RECRUITMENT);
    }

    /**
     * @return list<string>
     */
    public static function onboardingKeys(): array
    {
        return array_keys(self::ONBOARDING);
    }

    /**
     * @return list<string>
     */
    public static function performanceKeys(): array
    {
        return array_keys(self::PERFORMANCE);
    }

    /**
     * @return list<string>
     */
    public static function reportKeys(): array
    {
        return array_keys(self::REPORTS);
    }

    /**
     * @return list<string>
     */
    public static function notificationKeys(): array
    {
        return array_keys(self::NOTIFICATIONS);
    }

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return array_keys(self::all());
    }
}
