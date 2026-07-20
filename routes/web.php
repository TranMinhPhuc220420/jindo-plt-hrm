<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::inertia('organization', 'organization/index')->name('organization.index');
    Route::inertia('roles', 'roles/index')->name('roles.index');
    Route::inertia('audit-logs', 'audit-logs/index')->name('audit-logs.index');
    Route::inertia('settings/company', 'settings/company')->name('settings.company');

    Route::inertia('employees', 'employees/index')->name('employees.index');
    Route::inertia('employees/create', 'employees/create')->name('employees.create');
    Route::get('employees/{id}', function (int $id) {
        return Inertia::render('employees/show', ['id' => $id]);
    })->whereNumber('id')->name('employees.show');

    Route::inertia('shifts', 'shifts/index')->name('shifts.index');
    Route::inertia('shifts/create', 'shifts/create')->name('shifts.create');
    Route::get('shifts/{id}', function (int $id) {
        return Inertia::render('shifts/show', ['id' => $id]);
    })->whereNumber('id')->name('shifts.show');
    Route::inertia('my-schedule', 'my-schedule')->name('my-schedule');

    Route::inertia('attendance', 'attendance/index')->name('attendance.index');
    Route::inertia('attendance/corrections', 'attendance/corrections')
        ->name('attendance.corrections');

    Route::inertia('leave', 'leave/index')->name('leave.index');
    Route::inertia('leave/types', 'leave/types')->name('leave.types');
    Route::inertia('leave/holidays', 'leave/holidays')->name('leave.holidays');

    Route::inertia('payroll', 'payroll/index')->name('payroll.index');
    Route::inertia('payroll/payslips', 'payroll/payslips')->name('payroll.payslips');
    Route::inertia('payroll/compensation', 'payroll/compensation')
        ->name('payroll.compensation');
    Route::get('payroll/compensation/{id}', function (int $id) {
        return Inertia::render('payroll/compensation/show', ['id' => $id]);
    })->whereNumber('id')->name('payroll.compensation.show');
    Route::get('payroll/{id}', function (int $id) {
        return Inertia::render('payroll/show', ['id' => $id]);
    })->whereNumber('id')->name('payroll.show');

    Route::inertia('documents', 'documents/index')->name('documents.index');

    Route::inertia('assets', 'assets/index')->name('assets.index');
    Route::get('assets/{id}', function (int $id) {
        return Inertia::render('assets/show', ['id' => $id]);
    })->whereNumber('id')->name('assets.show');

    Route::inertia('recruitment', 'recruitment/index')->name('recruitment.index');
    Route::get('recruitment/candidates/{id}', function (int $id) {
        return Inertia::render('recruitment/candidates/show', ['id' => $id]);
    })->whereNumber('id')->name('recruitment.candidates.show');

    Route::inertia('onboarding', 'onboarding/index')->name('onboarding.index');
    Route::get('onboarding/{id}', function (int $id) {
        return Inertia::render('onboarding/show', ['id' => $id]);
    })->whereNumber('id')->name('onboarding.show');

    Route::inertia('notifications', 'notifications/index')->name('notifications.index');

    Route::inertia('reports', 'reports/index')->name('reports.index');

    Route::inertia('performance', 'performance/index')->name('performance.index');
    Route::get('performance/cycles/{id}', function (int $id) {
        return Inertia::render('performance/cycles/show', ['id' => $id]);
    })->whereNumber('id')->name('performance.cycles.show');
});

require __DIR__.'/settings.php';
