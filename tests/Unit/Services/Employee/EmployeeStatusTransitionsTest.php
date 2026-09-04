<?php

use App\Models\Employee;
use App\Services\Employee\EmployeeStatusTransitions;

test('full transition matrix matches the allowed map', function () {
    foreach (Employee::STATUSES as $from) {
        foreach (Employee::STATUSES as $to) {
            $expected = $from === $to || in_array($to, EmployeeStatusTransitions::ALLOWED[$from], true);

            expect(EmployeeStatusTransitions::canTransition($from, $to))
                ->toBe($expected, "transition {$from} -> {$to} did not match the expected allowed map");
        }
    }
});

test('probation can move to active, suspended, or resigned', function () {
    expect(EmployeeStatusTransitions::canTransition('probation', 'active'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('probation', 'suspended'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('probation', 'resigned'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('probation', 'archived'))->toBeFalse();
});

test('archived can be rehired to active or probation', function () {
    expect(EmployeeStatusTransitions::canTransition('archived', 'active'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('archived', 'probation'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('archived', 'suspended'))->toBeFalse();
    expect(EmployeeStatusTransitions::canTransition('archived', 'resigned'))->toBeFalse();
});

test('resigned can move to archived or be rehired', function () {
    expect(EmployeeStatusTransitions::canTransition('resigned', 'archived'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('resigned', 'active'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('resigned', 'probation'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('resigned', 'suspended'))->toBeFalse();
});

test('active cannot skip to archived or go back to probation', function () {
    expect(EmployeeStatusTransitions::canTransition('active', 'archived'))->toBeFalse();
    expect(EmployeeStatusTransitions::canTransition('active', 'probation'))->toBeFalse();
});

test('unknown status values are always rejected', function () {
    expect(EmployeeStatusTransitions::canTransition('bogus', 'active'))->toBeFalse();
    expect(EmployeeStatusTransitions::canTransition('active', 'bogus'))->toBeFalse();
    expect(EmployeeStatusTransitions::canTransition('bogus', 'bogus'))->toBeFalse();
});

test('every status is idempotent to itself', function () {
    foreach (Employee::STATUSES as $status) {
        expect(EmployeeStatusTransitions::canTransition($status, $status))->toBeTrue();
    }
});

test('allowed next statuses include current plus legal targets', function () {
    expect(EmployeeStatusTransitions::allowedNextStatuses('archived'))
        ->toBe(['archived', 'active', 'probation'])
        ->and(EmployeeStatusTransitions::allowedNextStatuses('resigned'))
        ->toBe(['resigned', 'archived', 'active', 'probation'])
        ->and(EmployeeStatusTransitions::allowedNextStatuses('active'))
        ->toBe(['active', 'suspended', 'resigned'])
        ->and(EmployeeStatusTransitions::allowedNextStatuses('bogus'))
        ->toBe([]);
});
