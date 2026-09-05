<?php

use App\Exceptions\DomainException;
use App\Models\Company;
use App\Models\User;
use App\Services\Settings\SettingsService;

function settingsUser(array $permissions): User
{
    return actingUser($permissions, prefix: 'settings');
}

test('settings require view permission and return grouped defaults', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $denied = settingsUser([]);
    $this->actingAs($denied)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/settings')
        ->assertForbidden();

    $viewer = settingsUser(['can_view_settings']);
    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/settings')
        ->assertOk()
        ->assertJsonPath('data.company.timezone', 'Asia/Ho_Chi_Minh')
        ->assertJsonPath('data.company.locale', 'vi')
        ->assertJsonPath('data.auth.two_factor_required', false)
        ->assertJsonPath('data.attendance.punch_reminder_enabled', true);
});

test('settings manager can update keys', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $manager = settingsUser(['can_view_settings', 'can_manage_settings']);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/settings', [
            'company' => [
                'currency' => 'USD',
                'locale' => 'en',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.company.currency', 'USD')
        ->assertJsonPath('data.company.locale', 'en')
        ->assertJsonPath('data.company.timezone', 'Asia/Ho_Chi_Minh');

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/settings/company')
        ->assertOk()
        ->assertJsonPath('data.company.currency', 'USD');
});

test('settings reject invalid company locale', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $manager = settingsUser(['can_view_settings', 'can_manage_settings']);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/settings', [
            'company' => [
                'locale' => 'fr',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});

test('settings update rejects unknown keys', function () {
    Company::factory()->create();
    $manager = settingsUser(['can_manage_settings', 'can_view_settings']);

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/settings', [
            'company' => [
                'smtp_password' => 'secret',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'SETTINGS_KEY_INVALID');
});

test('fetching an unknown settings group returns SETTINGS_GROUP_INVALID', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);
    $viewer = settingsUser(['can_view_settings']);

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/settings/bogus')
        ->assertNotFound()
        ->assertJsonPath('error_code', 'SETTINGS_GROUP_INVALID');
});

test('the HTTP layer rejects a non-object group payload before it reaches the service', function () {
    Company::factory()->create();
    $manager = settingsUser(['can_manage_settings', 'can_view_settings']);

    // UpdateSettingsRequest validates each group as an array, so a scalar
    // value never reaches SettingsService::update()'s own guard below.
    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/settings', ['company' => 'not-an-object'])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});

test('SettingsService::update defensively rejects a non-array group payload', function () {
    Company::factory()->create();

    try {
        app(SettingsService::class)->update(['company' => 'not-an-object']);
        $this->fail('Expected a DomainException to be thrown.');
    } catch (DomainException $e) {
        expect($e->errorCode())->toBe('SETTINGS_PAYLOAD_INVALID');
    }
});

test('a specific settings group can be fetched on its own', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);
    $viewer = settingsUser(['can_view_settings']);

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/settings/auth')
        ->assertOk()
        ->assertJsonPath('data.auth.two_factor_required', false)
        ->assertJsonMissingPath('data.company');
});
