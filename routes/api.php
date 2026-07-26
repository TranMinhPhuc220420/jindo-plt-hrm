<?php

use App\Http\Controllers\Api\Asset\AssetController;
use App\Http\Controllers\Api\Attendance\AttendanceCorrectionController;
use App\Http\Controllers\Api\Attendance\AttendanceRecordController;
use App\Http\Controllers\Api\Attendance\AttendanceSummaryController;
use App\Http\Controllers\Api\Audit\AuditLogController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\MeAvatarController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\MeLocaleController;
use App\Http\Controllers\Api\Authorization\PermissionController;
use App\Http\Controllers\Api\Authorization\RoleController;
use App\Http\Controllers\Api\Authorization\UserRoleController;
use App\Http\Controllers\Api\Document\DocumentController;
use App\Http\Controllers\Api\Employee\EmployeeAvatarController;
use App\Http\Controllers\Api\Employee\EmployeeContractController;
use App\Http\Controllers\Api\Employee\EmployeeController;
use App\Http\Controllers\Api\Employee\EmployeeEducationController;
use App\Http\Controllers\Api\Employee\EmployeeEmergencyContactController;
use App\Http\Controllers\Api\Employee\EmployeeFamilyMemberController;
use App\Http\Controllers\Api\Employee\EmployeeSensitiveController;
use App\Http\Controllers\Api\Employee\EmployeeWorkHistoryController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Leave\HolidayController;
use App\Http\Controllers\Api\Leave\LeaveBalanceController;
use App\Http\Controllers\Api\Leave\LeaveRequestController;
use App\Http\Controllers\Api\Leave\LeaveTypeController;
use App\Http\Controllers\Api\Leave\WeekendRuleController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Notification\NotificationPreferenceController;
use App\Http\Controllers\Api\Onboarding\OnboardingCaseController;
use App\Http\Controllers\Api\Onboarding\OnboardingTaskController;
use App\Http\Controllers\Api\Onboarding\OnboardingTemplateController;
use App\Http\Controllers\Api\Organization\BranchController;
use App\Http\Controllers\Api\Organization\CompanyController;
use App\Http\Controllers\Api\Organization\DepartmentController;
use App\Http\Controllers\Api\Organization\OrganizationTreeController;
use App\Http\Controllers\Api\Organization\PositionController;
use App\Http\Controllers\Api\Organization\TeamController;
use App\Http\Controllers\Api\Payroll\EmployeeSalaryController;
use App\Http\Controllers\Api\Payroll\PayrollRunController;
use App\Http\Controllers\Api\Payroll\PayslipController;
use App\Http\Controllers\Api\Performance\EvaluationController;
use App\Http\Controllers\Api\Performance\GoalController;
use App\Http\Controllers\Api\Performance\PromotionSuggestionController;
use App\Http\Controllers\Api\Performance\ReviewCycleController;
use App\Http\Controllers\Api\Recruitment\CandidateController;
use App\Http\Controllers\Api\Recruitment\InterviewController;
use App\Http\Controllers\Api\Recruitment\JobOpeningController;
use App\Http\Controllers\Api\Recruitment\OfferController;
use App\Http\Controllers\Api\Report\DashboardController;
use App\Http\Controllers\Api\Report\ReportController;
use App\Http\Controllers\Api\Report\ReportExportController;
use App\Http\Controllers\Api\Settings\SettingsController;
use App\Http\Controllers\Api\Shift\OvertimeRuleController;
use App\Http\Controllers\Api\Shift\ShiftAssignmentController;
use App\Http\Controllers\Api\Shift\ShiftController;
use App\Http\Controllers\Api\Shift\WorkingCalendarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| REST JSON endpoints for the first-party SPA (Sanctum cookie) and future
| token clients. All HRM domain modules must expose contracts under /api.
|
| SPA clients should call GET /sanctum/csrf-cookie before mutating auth
| requests, then send credentials (cookies) + X-XSRF-TOKEN on POSTs.
|
*/

Route::get('/health', HealthController::class)->name('api.health');

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.auth.login');

    Route::post('/two-factor/challenge', [AuthController::class, 'challengeTwoFactor'])
        ->middleware('throttle:5,1')
        ->name('api.auth.two-factor.challenge');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('api.auth.logout');
});

Route::get('/me', MeController::class)
    ->middleware('auth:sanctum')
    ->name('api.me');

Route::put('/me/locale', MeLocaleController::class)
    ->middleware('auth:sanctum')
    ->name('api.me.locale');

Route::post('/me/avatar', [MeAvatarController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('api.me.avatar.store');

Route::delete('/me/avatar', [MeAvatarController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('api.me.avatar.destroy');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/permissions', [PermissionController::class, 'index'])
        ->name('api.permissions.index');

    Route::get('/roles', [RoleController::class, 'index'])->name('api.roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('api.roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('api.roles.show');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('api.roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('api.roles.destroy');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->name('api.roles.permissions.sync');

    Route::get('/users/{user}/roles', [UserRoleController::class, 'index'])
        ->name('api.users.roles.index');
    Route::put('/users/{user}/roles', [UserRoleController::class, 'sync'])
        ->name('api.users.roles.sync');

    Route::get('/companies/current', [CompanyController::class, 'current'])
        ->name('api.companies.current');
    Route::patch('/companies/current', [CompanyController::class, 'updateCurrent'])
        ->name('api.companies.current.update');

    Route::get('/organization/tree', OrganizationTreeController::class)
        ->name('api.organization.tree');

    Route::get('/branches', [BranchController::class, 'index'])->name('api.branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('api.branches.store');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('api.branches.show');
    Route::patch('/branches/{branch}', [BranchController::class, 'update'])->name('api.branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('api.branches.destroy');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('api.departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('api.departments.store');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('api.departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('api.departments.destroy');

    Route::get('/teams', [TeamController::class, 'index'])->name('api.teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('api.teams.store');
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('api.teams.update');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('api.teams.destroy');

    Route::get('/positions', [PositionController::class, 'index'])->name('api.positions.index');
    Route::post('/positions', [PositionController::class, 'store'])->name('api.positions.store');
    Route::patch('/positions/{position}', [PositionController::class, 'update'])->name('api.positions.update');
    Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('api.positions.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('api.settings.index');
    Route::get('/settings/{group}', [SettingsController::class, 'show'])->name('api.settings.show');
    Route::put('/settings', [SettingsController::class, 'update'])->name('api.settings.update');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('api.audit-logs.show');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('api.employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('api.employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('api.employees.show');
    Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('api.employees.update');
    Route::put('/employees/{employee}/password', [EmployeeController::class, 'updatePassword'])
        ->name('api.employees.password');
    Route::post('/employees/{employee}/status', [EmployeeController::class, 'changeStatus'])
        ->name('api.employees.status');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
        ->name('api.employees.destroy');

    Route::post('/employees/{employee}/avatar', [EmployeeAvatarController::class, 'store'])
        ->name('api.employees.avatar.store');
    Route::delete('/employees/{employee}/avatar', [EmployeeAvatarController::class, 'destroy'])
        ->name('api.employees.avatar.destroy');

    Route::get('/employees/{employee}/emergency-contacts', [EmployeeEmergencyContactController::class, 'index'])
        ->name('api.employees.emergency-contacts.index');
    Route::put('/employees/{employee}/emergency-contacts', [EmployeeEmergencyContactController::class, 'replace'])
        ->name('api.employees.emergency-contacts.replace');

    Route::get('/employees/{employee}/educations', [EmployeeEducationController::class, 'index'])
        ->name('api.employees.educations.index');
    Route::post('/employees/{employee}/educations', [EmployeeEducationController::class, 'store'])
        ->name('api.employees.educations.store');
    Route::patch('/employees/{employee}/educations/{education}', [EmployeeEducationController::class, 'update'])
        ->name('api.employees.educations.update');
    Route::delete('/employees/{employee}/educations/{education}', [EmployeeEducationController::class, 'destroy'])
        ->name('api.employees.educations.destroy');

    Route::get('/employees/{employee}/work-histories', [EmployeeWorkHistoryController::class, 'index'])
        ->name('api.employees.work-histories.index');
    Route::post('/employees/{employee}/work-histories', [EmployeeWorkHistoryController::class, 'store'])
        ->name('api.employees.work-histories.store');
    Route::patch('/employees/{employee}/work-histories/{workHistory}', [EmployeeWorkHistoryController::class, 'update'])
        ->name('api.employees.work-histories.update');
    Route::delete('/employees/{employee}/work-histories/{workHistory}', [EmployeeWorkHistoryController::class, 'destroy'])
        ->name('api.employees.work-histories.destroy');

    Route::get('/employees/{employee}/family-members', [EmployeeFamilyMemberController::class, 'index'])
        ->name('api.employees.family-members.index');
    Route::post('/employees/{employee}/family-members', [EmployeeFamilyMemberController::class, 'store'])
        ->name('api.employees.family-members.store');
    Route::patch('/employees/{employee}/family-members/{familyMember}', [EmployeeFamilyMemberController::class, 'update'])
        ->name('api.employees.family-members.update');
    Route::delete('/employees/{employee}/family-members/{familyMember}', [EmployeeFamilyMemberController::class, 'destroy'])
        ->name('api.employees.family-members.destroy');

    Route::get('/employees/{employee}/contracts', [EmployeeContractController::class, 'index'])
        ->name('api.employees.contracts.index');
    Route::post('/employees/{employee}/contracts', [EmployeeContractController::class, 'store'])
        ->name('api.employees.contracts.store');
    Route::patch('/employees/{employee}/contracts/{contract}', [EmployeeContractController::class, 'update'])
        ->name('api.employees.contracts.update');

    Route::get('/employees/{employee}/bank-account', [EmployeeSensitiveController::class, 'showBankAccount'])
        ->name('api.employees.bank-account.show');
    Route::put('/employees/{employee}/bank-account', [EmployeeSensitiveController::class, 'updateBankAccount'])
        ->name('api.employees.bank-account.update');
    Route::get('/employees/{employee}/tax-profile', [EmployeeSensitiveController::class, 'showTaxProfile'])
        ->name('api.employees.tax-profile.show');
    Route::put('/employees/{employee}/tax-profile', [EmployeeSensitiveController::class, 'updateTaxProfile'])
        ->name('api.employees.tax-profile.update');
    Route::get('/employees/{employee}/insurance', [EmployeeSensitiveController::class, 'showInsurance'])
        ->name('api.employees.insurance.show');
    Route::put('/employees/{employee}/insurance', [EmployeeSensitiveController::class, 'updateInsurance'])
        ->name('api.employees.insurance.update');

    Route::get('/shifts', [ShiftController::class, 'index'])->name('api.shifts.index');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('api.shifts.store');
    Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('api.shifts.show');
    Route::patch('/shifts/{shift}', [ShiftController::class, 'update'])->name('api.shifts.update');
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('api.shifts.destroy');

    Route::get('/shift-assignments', [ShiftAssignmentController::class, 'index'])
        ->name('api.shift-assignments.index');
    Route::post('/shift-assignments', [ShiftAssignmentController::class, 'store'])
        ->name('api.shift-assignments.store');
    Route::patch('/shift-assignments/{shiftAssignment}', [ShiftAssignmentController::class, 'update'])
        ->name('api.shift-assignments.update');
    Route::delete('/shift-assignments/{shiftAssignment}', [ShiftAssignmentController::class, 'destroy'])
        ->name('api.shift-assignments.destroy');

    Route::get('/working-calendar', WorkingCalendarController::class)
        ->name('api.working-calendar');

    Route::get('/overtime-rules', [OvertimeRuleController::class, 'index'])
        ->name('api.overtime-rules.index');
    Route::put('/overtime-rules', [OvertimeRuleController::class, 'replace'])
        ->name('api.overtime-rules.replace');

    Route::post('/attendance/check-in', [AttendanceRecordController::class, 'checkIn'])
        ->name('api.attendance.check-in');
    Route::post('/attendance/check-out', [AttendanceRecordController::class, 'checkOut'])
        ->name('api.attendance.check-out');
    Route::get('/attendance/records', [AttendanceRecordController::class, 'index'])
        ->name('api.attendance.records.index');
    Route::get('/attendance/records/{record}', [AttendanceRecordController::class, 'show'])
        ->name('api.attendance.records.show');
    Route::get('/attendance/records/{record}/evidences/{punchType}/photo', [AttendanceRecordController::class, 'evidencePhoto'])
        ->where('punchType', 'check_in|check_out')
        ->name('api.attendance.records.evidence-photo');
    Route::post('/attendance/records/{record}/approve', [AttendanceRecordController::class, 'approve'])
        ->name('api.attendance.records.approve');
    Route::post('/attendance/period/lock', [AttendanceRecordController::class, 'lockPeriod'])
        ->name('api.attendance.period.lock');

    Route::get('/attendance/corrections', [AttendanceCorrectionController::class, 'index'])
        ->name('api.attendance.corrections.index');
    Route::post('/attendance/corrections', [AttendanceCorrectionController::class, 'store'])
        ->name('api.attendance.corrections.store');
    Route::post('/attendance/corrections/{correction}/approve', [AttendanceCorrectionController::class, 'approve'])
        ->name('api.attendance.corrections.approve');
    Route::post('/attendance/corrections/{correction}/reject', [AttendanceCorrectionController::class, 'reject'])
        ->name('api.attendance.corrections.reject');

    Route::get('/attendance/summary', AttendanceSummaryController::class)
        ->name('api.attendance.summary');

    Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('api.leave-types.index');
    Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('api.leave-types.store');
    Route::patch('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('api.leave-types.update');

    Route::get('/leave-balances', [LeaveBalanceController::class, 'index'])->name('api.leave-balances.index');
    Route::post('/leave-balances/adjust', [LeaveBalanceController::class, 'adjust'])->name('api.leave-balances.adjust');

    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('api.leave-requests.index');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('api.leave-requests.store');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('api.leave-requests.show');
    Route::post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('api.leave-requests.cancel');
    Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
        ->name('api.leave-requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
        ->name('api.leave-requests.reject');

    Route::get('/holidays', [HolidayController::class, 'index'])->name('api.holidays.index');
    Route::post('/holidays', [HolidayController::class, 'store'])->name('api.holidays.store');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('api.holidays.destroy');

    Route::get('/weekend-rules', [WeekendRuleController::class, 'show'])->name('api.weekend-rules.show');
    Route::put('/weekend-rules', [WeekendRuleController::class, 'update'])->name('api.weekend-rules.update');

    Route::get('/employee-salaries', [EmployeeSalaryController::class, 'index'])
        ->name('api.employee-salaries.index');
    Route::put('/employees/{employee}/salary', [EmployeeSalaryController::class, 'upsert'])
        ->name('api.employees.salary.upsert');
    Route::get('/employees/{employee}/allowances', [EmployeeSalaryController::class, 'allowances'])
        ->name('api.employees.allowances.index');
    Route::put('/employees/{employee}/allowances', [EmployeeSalaryController::class, 'replaceAllowances'])
        ->name('api.employees.allowances.replace');
    Route::get('/employees/{employee}/deductions', [EmployeeSalaryController::class, 'deductions'])
        ->name('api.employees.deductions.index');
    Route::put('/employees/{employee}/deductions', [EmployeeSalaryController::class, 'replaceDeductions'])
        ->name('api.employees.deductions.replace');
    Route::get('/employees/{employee}/bonuses', [EmployeeSalaryController::class, 'bonuses'])
        ->name('api.employees.bonuses.index');
    Route::put('/employees/{employee}/bonuses', [EmployeeSalaryController::class, 'replaceBonuses'])
        ->name('api.employees.bonuses.replace');

    Route::get('/payroll-runs', [PayrollRunController::class, 'index'])->name('api.payroll-runs.index');
    Route::post('/payroll-runs', [PayrollRunController::class, 'store'])->name('api.payroll-runs.store');
    Route::get('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'show'])->name('api.payroll-runs.show');
    Route::put('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'update'])->name('api.payroll-runs.update');
    Route::delete('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'destroy'])
        ->name('api.payroll-runs.destroy');
    Route::post('/payroll-runs/{payrollRun}/calculate', [PayrollRunController::class, 'calculate'])
        ->name('api.payroll-runs.calculate');
    Route::post('/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
        ->name('api.payroll-runs.approve');
    Route::post('/payroll-runs/{payrollRun}/finalize', [PayrollRunController::class, 'finalize'])
        ->name('api.payroll-runs.finalize');
    Route::get('/payroll-runs/{payrollRun}/items', [PayrollRunController::class, 'items'])
        ->name('api.payroll-runs.items');

    Route::get('/payslips', [PayslipController::class, 'index'])->name('api.payslips.index');
    Route::get('/payslips/{payslip}', [PayslipController::class, 'show'])->name('api.payslips.show');
    Route::get('/payslips/{payslip}/download', [PayslipController::class, 'download'])
        ->name('api.payslips.download');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('api.documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('api.documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('api.documents.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('api.documents.download');
    Route::patch('/documents/{document}', [DocumentController::class, 'update'])->name('api.documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('api.documents.destroy');

    // Assets
    Route::get('/assets', [AssetController::class, 'index'])->name('api.assets.index');
    Route::post('/assets', [AssetController::class, 'store'])->name('api.assets.store');
    Route::get('/asset-assignments', [AssetController::class, 'assignments'])
        ->name('api.asset-assignments.index');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('api.assets.show');
    Route::patch('/assets/{asset}', [AssetController::class, 'update'])->name('api.assets.update');
    Route::post('/assets/{asset}/retire', [AssetController::class, 'retire'])->name('api.assets.retire');
    Route::post('/assets/{asset}/assign', [AssetController::class, 'assign'])->name('api.assets.assign');
    Route::post('/assets/{asset}/return', [AssetController::class, 'returnAsset'])->name('api.assets.return');
    Route::post('/assets/{asset}/replace', [AssetController::class, 'replace'])->name('api.assets.replace');
    Route::get('/assets/{asset}/maintenances', [AssetController::class, 'maintenances'])
        ->name('api.assets.maintenances.index');
    Route::post('/assets/{asset}/maintenances', [AssetController::class, 'storeMaintenance'])
        ->name('api.assets.maintenances.store');
    Route::post('/assets/{asset}/damage-reports', [AssetController::class, 'storeDamageReport'])
        ->name('api.assets.damage-reports.store');

    // Recruitment: job openings
    Route::get('/job-openings', [JobOpeningController::class, 'index'])->name('api.job-openings.index');
    Route::post('/job-openings', [JobOpeningController::class, 'store'])->name('api.job-openings.store');
    Route::get('/job-openings/{jobOpening}', [JobOpeningController::class, 'show'])->name('api.job-openings.show');
    Route::patch('/job-openings/{jobOpening}', [JobOpeningController::class, 'update'])->name('api.job-openings.update');
    Route::post('/job-openings/{jobOpening}/close', [JobOpeningController::class, 'close'])
        ->name('api.job-openings.close');

    // Recruitment: candidates
    Route::get('/candidates', [CandidateController::class, 'index'])->name('api.candidates.index');
    Route::post('/candidates', [CandidateController::class, 'store'])->name('api.candidates.store');
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->name('api.candidates.show');
    Route::patch('/candidates/{candidate}', [CandidateController::class, 'update'])->name('api.candidates.update');
    Route::post('/candidates/{candidate}/stage', [CandidateController::class, 'stage'])->name('api.candidates.stage');
    Route::post('/candidates/{candidate}/hire', [CandidateController::class, 'hire'])->name('api.candidates.hire');

    // Recruitment: interviews
    Route::get('/candidates/{candidate}/interviews', [InterviewController::class, 'index'])
        ->name('api.candidates.interviews.index');
    Route::post('/candidates/{candidate}/interviews', [InterviewController::class, 'store'])
        ->name('api.candidates.interviews.store');
    Route::post('/interviews/{interview}/evaluation', [InterviewController::class, 'evaluate'])
        ->name('api.interviews.evaluation');

    // Recruitment: offers
    Route::get('/candidates/{candidate}/offers', [OfferController::class, 'index'])
        ->name('api.candidates.offers.index');
    Route::post('/candidates/{candidate}/offers', [OfferController::class, 'store'])
        ->name('api.candidates.offers.store');
    Route::post('/offers/{offer}/send', [OfferController::class, 'send'])->name('api.offers.send');
    Route::post('/offers/{offer}/accept', [OfferController::class, 'accept'])->name('api.offers.accept');
    Route::post('/offers/{offer}/reject', [OfferController::class, 'reject'])->name('api.offers.reject');

    // Onboarding: cases
    Route::get('/onboarding-cases', [OnboardingCaseController::class, 'index'])->name('api.onboarding-cases.index');
    Route::post('/onboarding-cases', [OnboardingCaseController::class, 'store'])->name('api.onboarding-cases.store');
    Route::get('/onboarding-cases/{onboardingCase}', [OnboardingCaseController::class, 'show'])
        ->name('api.onboarding-cases.show');
    Route::post('/onboarding-cases/{onboardingCase}/complete', [OnboardingCaseController::class, 'complete'])
        ->name('api.onboarding-cases.complete');
    Route::get('/onboarding-cases/{onboardingCase}/tasks', [OnboardingCaseController::class, 'tasks'])
        ->name('api.onboarding-cases.tasks');

    // Onboarding: tasks
    Route::post('/onboarding-tasks/{onboardingTask}/complete', [OnboardingTaskController::class, 'complete'])
        ->name('api.onboarding-tasks.complete');
    Route::post('/onboarding-tasks/{onboardingTask}/reopen', [OnboardingTaskController::class, 'reopen'])
        ->name('api.onboarding-tasks.reopen');

    // Onboarding: templates
    Route::get('/onboarding-templates', [OnboardingTemplateController::class, 'index'])
        ->name('api.onboarding-templates.index');
    Route::post('/onboarding-templates', [OnboardingTemplateController::class, 'store'])
        ->name('api.onboarding-templates.store');
    Route::get('/onboarding-templates/{onboardingTemplate}', [OnboardingTemplateController::class, 'show'])
        ->name('api.onboarding-templates.show');
    Route::patch('/onboarding-templates/{onboardingTemplate}', [OnboardingTemplateController::class, 'update'])
        ->name('api.onboarding-templates.update');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('api.notifications.unread-count');
    Route::post('/notifications/broadcast', [NotificationController::class, 'broadcast'])
        ->name('api.notifications.broadcast');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('api.notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('api.notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
        ->name('api.notifications.destroy');

    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'show'])
        ->name('api.notification-preferences.show');
    Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])
        ->name('api.notification-preferences.update');

    // Reports
    Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('api.reports.attendance');
    Route::get('/reports/payroll', [ReportController::class, 'payroll'])->name('api.reports.payroll');
    Route::get('/reports/leave', [ReportController::class, 'leave'])->name('api.reports.leave');
    Route::get('/reports/employees', [ReportController::class, 'employees'])->name('api.reports.employees');
    Route::get('/reports/departments', [ReportController::class, 'departments'])->name('api.reports.departments');
    Route::get('/reports/performance', [ReportController::class, 'performance'])->name('api.reports.performance');
    Route::get('/reports/custom', [ReportController::class, 'customIndex'])->name('api.reports.custom.index');
    Route::post('/reports/custom', [ReportController::class, 'customStore'])->name('api.reports.custom.store');
    Route::get('/reports/custom/{custom}/run', [ReportController::class, 'customRun'])->name('api.reports.custom.run');

    Route::post('/reports/exports', [ReportExportController::class, 'store'])->name('api.reports.exports.store');
    Route::get('/reports/exports/{export}', [ReportExportController::class, 'show'])->name('api.reports.exports.show');

    Route::get('/dashboard/summary', DashboardController::class)->name('api.dashboard.summary');

    // Performance: review cycles
    Route::get('/performance/review-cycles', [ReviewCycleController::class, 'index'])
        ->name('api.performance.review-cycles.index');
    Route::post('/performance/review-cycles', [ReviewCycleController::class, 'store'])
        ->name('api.performance.review-cycles.store');
    Route::get('/performance/review-cycles/{reviewCycle}', [ReviewCycleController::class, 'show'])
        ->name('api.performance.review-cycles.show');
    Route::put('/performance/review-cycles/{reviewCycle}/participants', [ReviewCycleController::class, 'syncParticipants'])
        ->name('api.performance.review-cycles.participants');
    Route::post('/performance/review-cycles/{reviewCycle}/start', [ReviewCycleController::class, 'start'])
        ->name('api.performance.review-cycles.start');
    Route::post('/performance/review-cycles/{reviewCycle}/finalize', [ReviewCycleController::class, 'finalize'])
        ->name('api.performance.review-cycles.finalize');
    Route::delete('/performance/review-cycles/{reviewCycle}', [ReviewCycleController::class, 'destroy'])
        ->name('api.performance.review-cycles.destroy');

    // Performance: goals
    Route::get('/performance/goals', [GoalController::class, 'index'])->name('api.performance.goals.index');
    Route::post('/performance/goals', [GoalController::class, 'store'])->name('api.performance.goals.store');
    Route::patch('/performance/goals/{goal}', [GoalController::class, 'update'])->name('api.performance.goals.update');

    // Performance: evaluations
    Route::get('/performance/evaluations', [EvaluationController::class, 'index'])
        ->name('api.performance.evaluations.index');
    Route::post('/performance/evaluations', [EvaluationController::class, 'store'])
        ->name('api.performance.evaluations.store');
    Route::get('/performance/evaluations/{evaluation}', [EvaluationController::class, 'show'])
        ->name('api.performance.evaluations.show');

    // Performance: promotion suggestions
    Route::get('/performance/promotion-suggestions', [PromotionSuggestionController::class, 'index'])
        ->name('api.performance.promotion-suggestions.index');
    Route::post('/performance/promotion-suggestions/{promotionSuggestion}/acknowledge', [PromotionSuggestionController::class, 'acknowledge'])
        ->name('api.performance.promotion-suggestions.acknowledge');
});
