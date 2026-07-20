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

test('archived is a terminal state with no outgoing transitions', function () {
    foreach (Employee::STATUSES as $to) {
        if ($to === 'archived') {
            continue;
        }
        expect(EmployeeStatusTransitions::canTransition('archived', $to))->toBeFalse();
    }
});

test('resigned can only move to archived', function () {
    expect(EmployeeStatusTransitions::canTransition('resigned', 'archived'))->toBeTrue();
    expect(EmployeeStatusTransitions::canTransition('resigned', 'active'))->toBeFalse();
    expect(EmployeeStatusTransitions::canTransition('resigned', 'probation'))->toBeFalse();
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
