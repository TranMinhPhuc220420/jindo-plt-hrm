<?php

use App\Exceptions\DomainException;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Services\Employee\EmployeeAccountService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function seedEmployeeAuth(): void
{
    seedAuthCatalog();
}

function employeeUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'emp');
}

test('cannot list employees without can_view_employee', function () {
    Company::factory()->create();
    $user = employeeUser([]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/employees')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'FORBIDDEN');
});

test('listing employees with no active company returns COMPANY_NOT_CONFIGURED', function () {
    $user = employeeUser(['can_view_employee']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/employees')
        ->assertNotFound()
        ->assertJsonPath('error_code', 'COMPANY_NOT_CONFIGURED');
});

test('EmployeeAccountService::defaultPassword fails fast when unconfigured', function () {
    Company::factory()->create();
    config(['hrm.employee_default_password' => null]);

    try {
        app(EmployeeAccountService::class)->defaultPassword();
        $this->fail('Expected a DomainException to be thrown.');
    } catch (DomainException $e) {
        expect($e->errorCode())->toBe('EMPLOYEE_DEFAULT_PASSWORD_MISSING');
    }
});

test('hr can create list filter and update employees', function () {
    $company = Company::factory()->create(['code' => 'EMPCO']);
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $department = Department::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
    ]);
    $position = Position::factory()->create(['company_id' => $company->id]);
    $otherDepartment = Department::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
    ]);

    $hr = employeeUser([
        'can_view_employee',
        'can_create_employee',
        'can_update_employee',
        'can_change_employee_status',
        'can_view_audit_logs',
    ]);

    $created = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'code' => 'E-100',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.test',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'status' => 'probation',
        ]);

    $created->assertCreated()
        ->assertJsonPath('data.code', 'E-100')
        ->assertJsonPath('data.full_name', 'Jane Doe')
        ->assertJsonPath('data.status', 'probation')
        ->assertJsonPath('data.department_id', $department->id)
        ->assertJsonPath('data.position_id', $position->id);

    $id = $created->json('data.id');

    expect(AuditLog::query()->where('action', 'employee.created')->count())->toBe(1);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/employees?search=Jane&status=probation&department_id='.$department->id)
        ->assertOk()
        ->assertJsonPath('data.0.id', $id);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->patchJson("/api/employees/{$id}", [
            'phone' => '0900000000',
            'department_id' => $otherDepartment->id,
            'position_id' => $position->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.phone', '0900000000')
        ->assertJsonPath('data.department_id', $otherDepartment->id)
        ->assertJsonPath('data.position_id', $position->id);
});

test('duplicate employee code returns EMPLOYEE_CODE_DUPLICATE', function () {
    Company::factory()->create(['code' => 'DUP']);
    $hr = employeeUser(['can_view_employee', 'can_create_employee']);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'code' => 'E-DUP',
            'first_name' => 'A',
            'last_name' => 'B',
        ])
        ->assertCreated();

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'code' => 'E-DUP',
            'first_name' => 'C',
            'last_name' => 'D',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'EMPLOYEE_CODE_DUPLICATE');
});

test('invalid status transition returns 409', function () {
    Company::factory()->create();
    $hr = employeeUser([
        'can_view_employee',
        'can_create_employee',
        'can_change_employee_status',
    ]);

    $id = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'code' => 'E-ST',
            'first_name' => 'Sam',
            'last_name' => 'Status',
            'status' => 'probation',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$id}/status", [
            'status' => 'archived',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'EMPLOYEE_INVALID_STATUS_TRANSITION');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$id}/status", [
            'status' => 'active',
            'reason' => 'Passed probation',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    expect(AuditLog::query()->where('action', 'employee.status_changed')->count())->toBe(1);
});

test('sensitive bank account requires can_manage_employee_sensitive', function () {
    Company::factory()->create();
    $viewer = employeeUser(['can_view_employee', 'can_create_employee', 'can_update_employee']);

    $id = $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'code' => 'E-SENS',
            'first_name' => 'Sen',
            'last_name' => 'Sitive',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->getJson("/api/employees/{$id}/bank-account")
        ->assertForbidden();

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/employees/{$id}/bank-account", [
            'bank_name' => 'ACB',
            'account_number' => '123',
        ])
        ->assertForbidden();

    $sensitive = employeeUser(['can_view_employee', 'can_manage_employee_sensitive']);

    $this->actingAs($sensitive)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/employees/{$id}/bank-account", [
            'bank_name' => 'ACB',
            'account_number' => '123456',
            'account_name' => 'Sen Sitive',
        ])
        ->assertOk()
        ->assertJsonPath('data.account_number', '123456');
});

test('cannot create employee without can_create_employee', function () {
    Company::factory()->create();
    $user = employeeUser(['can_view_employee']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'code' => 'E-NO',
            'first_name' => 'No',
            'last_name' => 'Create',
        ])
        ->assertForbidden();
});

test('hr can set and reset employee password', function () {
    $company = Company::factory()->create();
    $hr = employeeUser(['can_view_employee', 'can_update_employee']);
    $account = User::factory()->create([
        'email' => 'linked@example.test',
        'password' => 'old-password',
    ]);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $account->id,
        'email' => 'linked@example.test',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/employees/{$employee->id}/password", [
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])
        ->assertOk()
        ->assertJsonPath('data.id', $employee->id);

    expect(Hash::check('new-secret-pass', $account->fresh()->password))->toBeTrue();
    expect(AuditLog::query()->where('action', 'employee.password_changed')->count())->toBe(1);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/employees/{$employee->id}/password", [
            'use_default' => true,
        ])
        ->assertOk();

    expect(Hash::check(
        config('hrm.employee_default_password'),
        $account->fresh()->password,
    ))->toBeTrue();
    expect(AuditLog::query()->where('action', 'employee.password_reset_to_default')->count())->toBe(1);
});

test('password update requires linked user and can_update_employee', function () {
    $company = Company::factory()->create();
    $viewer = employeeUser(['can_view_employee']);
    $hr = employeeUser(['can_view_employee', 'can_update_employee']);

    $noAccount = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => null,
    ]);

    $account = User::factory()->create();
    $withAccount = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $account->id,
    ]);

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/employees/{$withAccount->id}/password", [
            'use_default' => true,
        ])
        ->assertForbidden();

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/employees/{$noAccount->id}/password", [
            'use_default' => true,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'EMPLOYEE_NO_USER_ACCOUNT');
});

test('hr can upload replace and delete employee avatar', function () {
    Storage::fake('public');
    $company = Company::factory()->create();
    $hr = employeeUser(['can_view_employee', 'can_update_employee']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $upload = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->post("/api/employees/{$employee->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $path = $employee->fresh()->avatar_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
    expect($upload->json('data.avatar_url'))->not->toBeNull();
    expect(AuditLog::query()->where('action', 'employee.avatar_updated')->count())->toBe(1);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->post("/api/employees/{$employee->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('photo2.png', 180, 180),
        ])
        ->assertOk();

    Storage::disk('public')->assertMissing($path);
    $replacement = $employee->fresh()->avatar_path;
    expect($replacement)->not->toBeNull()
        ->and($replacement)->not->toBe($path);
    Storage::disk('public')->assertExists($replacement);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson("/api/employees/{$employee->id}/avatar")
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);

    expect($employee->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($replacement);
    expect(AuditLog::query()->where('action', 'employee.avatar_deleted')->count())->toBe(1);
});

test('employee can upload own avatar without can_update_employee', function () {
    Storage::fake('public');
    $company = Company::factory()->create();
    $user = employeeUser([]);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $this->actingAs($user->fresh('roles.permissions'))
        ->withHeaders(spaJsonHeaders())
        ->post("/api/employees/{$employee->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('me.webp', 120, 120),
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->actingAs($user->fresh(['roles.permissions', 'employee']))
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.employee_id', $employee->id);

    expect($this->actingAs($user->fresh(['roles.permissions', 'employee']))
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/me')
        ->json('data.user.avatar'))->not->toBeNull();
});

test('cannot upload another employees avatar without permission', function () {
    Storage::fake('public');
    $company = Company::factory()->create();
    $user = employeeUser([]);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);
    $other = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $this->actingAs($user->fresh('roles.permissions'))
        ->withHeaders(spaJsonHeaders())
        ->post("/api/employees/{$other->id}/avatar", [
            'avatar' => UploadedFile::fake()->image('other.jpg', 100, 100),
        ])
        ->assertForbidden();
});

test('me avatar requires linked employee', function () {
    Storage::fake('public');
    Company::factory()->create();
    $user = employeeUser([]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->post('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('me.jpg', 100, 100),
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'EMPLOYEE_NOT_LINKED');
});

test('me avatar upload and delete sync auth payload', function () {
    Storage::fake('public');
    $company = Company::factory()->create();
    $user = employeeUser([]);
    Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $upload = $this->actingAs($user->fresh(['roles.permissions', 'employee']))
        ->withHeaders(spaJsonHeaders())
        ->post('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('self.jpg', 140, 140),
        ])
        ->assertOk();

    expect($upload->json('data.user.avatar'))->not->toBeNull();

    $this->actingAs($user->fresh(['roles.permissions', 'employee']))
        ->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/me/avatar')
        ->assertOk()
        ->assertJsonPath('data.user.avatar', null);
});

test('resigning an employee closes future shifts and sets terminated_at', function () {
    $company = Company::factory()->create();
    $hr = employeeUser(['can_view_employee', 'can_change_employee_status']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $shift = Shift::factory()->create(['company_id' => $company->id]);
    $past = ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    $running = ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
    ]);
    $future = ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$employee->id}/status", [
            'status' => 'resigned',
            'reason' => 'Left the company',
            'effective_on' => '2026-08-24',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'resigned')
        ->assertJsonPath('data.terminated_at', '2026-08-24');

    expect($past->fresh()->end_date?->toDateString())->toBe('2026-07-31')
        ->and($running->fresh()->end_date?->toDateString())->toBe('2026-08-24')
        ->and(ShiftAssignment::query()->find($future->id))->toBeNull();
});

test('resigning with outstanding assets requires confirmation', function () {
    $company = Company::factory()->create();
    $hr = employeeUser(['can_view_employee', 'can_change_employee_status']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'code' => 'LAP-OFF',
        'name' => 'Laptop',
        'status' => 'assigned',
        'assigned_to' => $employee->id,
    ]);
    AssetAssignment::factory()->create([
        'company_id' => $company->id,
        'asset_id' => $asset->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'returned_at' => null,
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$employee->id}/status", [
            'status' => 'resigned',
            'reason' => 'Left the company',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'EMPLOYEE_HAS_OUTSTANDING_ASSETS')
        ->assertJsonPath('meta.assets.0.code', 'LAP-OFF');

    expect($employee->fresh()->status)->toBe('active');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$employee->id}/status", [
            'status' => 'resigned',
            'reason' => 'Left the company',
            'confirm_asset_return' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'resigned')
        ->assertJsonPath('data.outstanding_assets_count', 0);

    expect($asset->fresh()->status)->toBe('available')
        ->and($asset->fresh()->assigned_to)->toBeNull()
        ->and(AuditLog::query()->where('action', 'asset.returned')->count())->toBe(1);
});

test('suspending an employee keeps shifts and assets', function () {
    $company = Company::factory()->create();
    $hr = employeeUser(['can_view_employee', 'can_change_employee_status']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $shift = Shift::factory()->create(['company_id' => $company->id]);
    $assignment = ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-08-01',
        'end_date' => null,
    ]);
    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'status' => 'assigned',
        'assigned_to' => $employee->id,
    ]);
    AssetAssignment::factory()->create([
        'company_id' => $company->id,
        'asset_id' => $asset->id,
        'employee_id' => $employee->id,
        'status' => 'active',
        'returned_at' => null,
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$employee->id}/status", [
            'status' => 'suspended',
            'reason' => 'On leave',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended')
        ->assertJsonPath('data.terminated_at', null);

    expect($assignment->fresh()->end_date)->toBeNull()
        ->and($asset->fresh()->status)->toBe('assigned');
});

test('changing status to resigned invalidates the employee session', function () {
    $company = Company::factory()->create();
    $hr = employeeUser(['can_view_employee', 'can_change_employee_status']);
    $employeeUser = User::factory()->create(['remember_token' => 'old-remember-token']);
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $employeeUser->id,
        'status' => 'active',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/employees/{$employee->id}/status", [
            'status' => 'resigned',
            'reason' => 'Left the company',
        ])
        ->assertOk();

    expect($employeeUser->fresh()->remember_token)->not->toBe('old-remember-token');

    $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/me')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'AUTH_ACCOUNT_INACTIVE');
});
