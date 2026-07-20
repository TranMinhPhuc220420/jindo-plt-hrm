<?php

use App\Support\PermissionCatalog;

test('all() merges every group without key collisions', function () {
    $groups = [
        PermissionCatalog::FOUNDATION,
        PermissionCatalog::EMPLOYEE,
        PermissionCatalog::SHIFT,
        PermissionCatalog::ATTENDANCE,
        PermissionCatalog::LEAVE,
        PermissionCatalog::PAYROLL,
        PermissionCatalog::DOCUMENTS,
        PermissionCatalog::ASSETS,
        PermissionCatalog::RECRUITMENT,
        PermissionCatalog::ONBOARDING,
        PermissionCatalog::PERFORMANCE,
        PermissionCatalog::REPORTS,
        PermissionCatalog::NOTIFICATIONS,
    ];

    $expectedCount = array_sum(array_map('count', $groups));
    $all = PermissionCatalog::all();

    expect($all)->toHaveCount($expectedCount);
});

test('every permission key follows the can_ prefix convention', function () {
    foreach (array_keys(PermissionCatalog::all()) as $key) {
        expect($key)->toStartWith('can_');
    }
});

test('every permission entry declares name, group, and description', function () {
    foreach (PermissionCatalog::all() as $key => $entry) {
        expect($entry)->toHaveKeys(['name', 'group', 'description']);
        expect($entry['name'])->not->toBe('');
        expect($entry['group'])->not->toBe('');
        expect($entry['description'])->not->toBe('');
    }
});

test('group-scoped key helpers match the raw group constants', function () {
    expect(PermissionCatalog::foundationKeys())->toBe(array_keys(PermissionCatalog::FOUNDATION));
    expect(PermissionCatalog::employeeKeys())->toBe(array_keys(PermissionCatalog::EMPLOYEE));
    expect(PermissionCatalog::shiftKeys())->toBe(array_keys(PermissionCatalog::SHIFT));
    expect(PermissionCatalog::attendanceKeys())->toBe(array_keys(PermissionCatalog::ATTENDANCE));
    expect(PermissionCatalog::leaveKeys())->toBe(array_keys(PermissionCatalog::LEAVE));
    expect(PermissionCatalog::payrollKeys())->toBe(array_keys(PermissionCatalog::PAYROLL));
    expect(PermissionCatalog::documentKeys())->toBe(array_keys(PermissionCatalog::DOCUMENTS));
    expect(PermissionCatalog::assetKeys())->toBe(array_keys(PermissionCatalog::ASSETS));
    expect(PermissionCatalog::recruitmentKeys())->toBe(array_keys(PermissionCatalog::RECRUITMENT));
    expect(PermissionCatalog::onboardingKeys())->toBe(array_keys(PermissionCatalog::ONBOARDING));
    expect(PermissionCatalog::performanceKeys())->toBe(array_keys(PermissionCatalog::PERFORMANCE));
    expect(PermissionCatalog::reportKeys())->toBe(array_keys(PermissionCatalog::REPORTS));
    expect(PermissionCatalog::notificationKeys())->toBe(array_keys(PermissionCatalog::NOTIFICATIONS));
});

test('allKeys() matches the keys of all()', function () {
    expect(PermissionCatalog::allKeys())->toBe(array_keys(PermissionCatalog::all()));
});
