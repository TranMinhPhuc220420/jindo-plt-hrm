<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Settings\SettingsService;

function auditUser(array $permissions): User
{
    return actingUser($permissions, prefix: 'audit');
}

test('settings update writes an audit log', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $manager = auditUser(['can_view_settings', 'can_manage_settings', 'can_view_audit_logs']);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/settings', [
            'auth' => ['two_factor_required' => true],
        ])
        ->assertOk();

    expect(AuditLog::query()->where('action', 'settings.updated')->count())->toBe(1);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs')
        ->assertOk()
        ->assertJsonPath('data.0.action', 'settings.updated');
});

test('audit log list requires can_view_audit_logs', function () {
    Company::factory()->create();
    $denied = auditUser([]);

    $this->actingAs($denied)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'FORBIDDEN');
});

test('branch create and role sync write audit logs', function () {
    Company::factory()->create(['code' => 'AUD2']);

    $manager = auditUser([
        'can_view_organization',
        'can_manage_organization',
        'can_view_roles',
        'can_manage_roles',
        'can_assign_roles',
        'can_view_audit_logs',
    ]);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'Audit Branch',
            'code' => 'AUD-BR',
        ])
        ->assertCreated();

    expect(AuditLog::query()->where('action', 'branch.created')->count())->toBe(1);

    $subject = User::factory()->create();

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/users/{$subject->id}/roles", [
            'roles' => ['employee'],
        ])
        ->assertOk();

    expect(AuditLog::query()->where('action', 'user.roles_synced')->count())->toBe(1);
});

test('audit log list filters by action', function () {
    $company = Company::factory()->create();
    $manager = auditUser(['can_view_audit_logs']);

    AuditLog::query()->create([
        'company_id' => $company->id,
        'actor_type' => User::class,
        'actor_id' => $manager->id,
        'action' => 'employee.created',
        'created_at' => now(),
    ]);
    AuditLog::query()->create([
        'company_id' => $company->id,
        'actor_type' => User::class,
        'actor_id' => $manager->id,
        'action' => 'employee.updated',
        'created_at' => now(),
    ]);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?action=employee.created')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.action', 'employee.created');
});

test('audit log list filters by actor_id', function () {
    $company = Company::factory()->create();
    $manager = auditUser(['can_view_audit_logs']);
    $otherActor = User::factory()->create();

    AuditLog::query()->create([
        'company_id' => $company->id,
        'actor_type' => User::class,
        'actor_id' => $manager->id,
        'action' => 'employee.created',
        'created_at' => now(),
    ]);
    AuditLog::query()->create([
        'company_id' => $company->id,
        'actor_type' => User::class,
        'actor_id' => $otherActor->id,
        'action' => 'employee.created',
        'created_at' => now(),
    ]);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?actor_id='.$otherActor->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.actor_id', $otherActor->id);
});

test('audit log list filters by date_from and date_to', function () {
    $company = Company::factory()->create();
    $manager = auditUser(['can_view_audit_logs']);

    AuditLog::query()->create([
        'company_id' => $company->id,
        'actor_type' => User::class,
        'actor_id' => $manager->id,
        'action' => 'employee.created',
        'created_at' => '2026-01-10 10:00:00',
    ]);
    AuditLog::query()->create([
        'company_id' => $company->id,
        'actor_type' => User::class,
        'actor_id' => $manager->id,
        'action' => 'employee.created',
        'created_at' => '2026-06-10 10:00:00',
    ]);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?date_from=2026-06-01&date_to=2026-06-30')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.created_at', '2026-06-10T10:00:00+00:00');
});

test('audit log list paginates results', function () {
    $company = Company::factory()->create();
    $manager = auditUser(['can_view_audit_logs']);

    for ($i = 0; $i < 5; $i++) {
        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_type' => User::class,
            'actor_id' => $manager->id,
            'action' => 'employee.created',
            'created_at' => now(),
        ]);
    }

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/audit-logs?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.last_page', 3);
});
