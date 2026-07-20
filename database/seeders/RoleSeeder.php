<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', PermissionCatalog::allKeys())
            ->pluck('id', 'key');

        $hrEmployee = [
            'can_view_employee',
            'can_create_employee',
            'can_update_employee',
            'can_manage_employee_sensitive',
            'can_change_employee_status',
        ];

        $hrShift = [
            'can_view_shifts',
            'can_manage_shift_definitions',
            'can_assign_shifts',
            'can_manage_overtime_rules',
        ];

        $hrAttendance = [
            'can_view_attendance',
            'can_manage_attendance',
            'can_approve_attendance',
            'can_request_attendance_correction',
        ];

        $hrLeave = [
            'can_view_leave',
            'can_request_leave',
            'can_approve_leave',
            'can_manage_leave_types',
            'can_manage_leave_balances',
            'can_manage_holidays',
        ];

        $hrPayroll = [
            'can_view_salary',
            'can_manage_salary',
            'can_run_payroll',
            'can_approve_payroll',
            'can_view_payroll_history',
            'can_manage_payslips',
        ];

        $hrDocuments = PermissionCatalog::documentKeys();
        $hrAssets = PermissionCatalog::assetKeys();
        $hrRecruitment = PermissionCatalog::recruitmentKeys();
        $hrOnboarding = PermissionCatalog::onboardingKeys();
        $hrPerformance = PermissionCatalog::performanceKeys();
        $hrReports = PermissionCatalog::reportKeys();
        $hrNotifications = PermissionCatalog::notificationKeys();

        $roles = [
            'admin' => [
                'name' => 'Admin',
                'description' => 'Full platform administration access',
                'permissions' => PermissionCatalog::allKeys(),
            ],
            'hr' => [
                'name' => 'HR',
                'description' => 'Organization, employee, time domain, payroll, hire/ops, and insight management',
                'permissions' => array_merge([
                    'can_view_organization',
                    'can_manage_organization',
                    'can_manage_company',
                    'can_view_roles',
                    'can_view_settings',
                    'can_manage_settings',
                    'can_view_audit_logs',
                ], $hrEmployee, $hrShift, $hrAttendance, $hrLeave, $hrPayroll, $hrDocuments, $hrAssets, $hrRecruitment, $hrOnboarding, $hrPerformance, $hrReports, $hrNotifications),
            ],
            'manager' => [
                'name' => 'Manager',
                'description' => 'Approve time domain; view candidates/onboarding/assets; evaluate performance; non-payroll reports',
                'permissions' => [
                    'can_view_organization',
                    'can_view_settings',
                    'can_view_employee',
                    'can_view_shifts',
                    'can_view_attendance',
                    'can_approve_attendance',
                    'can_view_leave',
                    'can_approve_leave',
                    'can_view_payroll_history',
                    'can_view_candidates',
                    'can_view_onboarding',
                    'can_view_assets',
                    'can_view_employee_documents',
                    'can_view_performance',
                    'can_manage_goals',
                    'can_evaluate_employee',
                    'can_view_attendance_reports',
                    'can_view_leave_reports',
                    'can_view_employee_reports',
                    'can_view_performance_reports',
                    'can_export_reports',
                    'can_view_own_notifications',
                ],
            ],
            'employee' => [
                'name' => 'Employee',
                'description' => 'Self-service time domain, payslips, documents, onboarding tasks, notifications, own performance',
                'permissions' => [
                    'can_view_own_profile',
                    'can_view_own_schedule',
                    'can_check_in_out',
                    'can_request_attendance_correction',
                    'can_view_attendance',
                    'can_request_leave',
                    'can_view_leave',
                    'can_view_salary',
                    'can_upload_own_documents',
                    'can_view_employee_documents',
                    'can_complete_onboarding_task',
                    'can_view_assets',
                    'can_view_own_notifications',
                    'can_view_performance',
                ],
            ],
        ];

        foreach ($roles as $key => $definition) {
            $role = Role::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                ],
            );

            $ids = collect($definition['permissions'])
                ->map(fn (string $permissionKey) => $permissionIds[$permissionKey] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }
    }
}
