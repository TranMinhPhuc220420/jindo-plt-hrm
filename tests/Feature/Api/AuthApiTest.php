<?php

use App\Exceptions\DomainException;
use App\Models\Company;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Settings\SettingsService;
use Illuminate\Contracts\Debug\ExceptionHandler;

test('users can login via api and fetch me', function () {
    $user = User::factory()->create();

    $login = $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $login->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.two_factor_required', false)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'name', 'email', 'avatar'],
                'permissions',
                'employee_id',
                'two_factor_required',
            ],
        ]);

    $this->assertAuthenticated();

    $me = $this->withHeaders(spaJsonHeaders())
        ->getJson('/api/me');

    $me->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.locale', 'vi')
        ->assertJsonPath('data.user_locale', null)
        ->assertJsonPath('data.company_locale', 'vi');
});

test('spa login works for production Origin when APP_URL host is in stateful domains', function () {
    // Regression: production Origin not in SANCTUM_STATEFUL_DOMAINS → StartSession skipped
    // → AuthService session()->regenerate() throws "Session store not set on request".
    // config/sanctum.php always merges APP_URL host so same-origin prod SPA stays stateful.
    config([
        'sanctum.stateful' => ['localhost', 'hrm.plt.pro.vn'],
    ]);

    $user = User::factory()->create();

    $login = $this->withHeaders([
        'Origin' => 'https://hrm.plt.pro.vn',
        'Referer' => 'https://hrm.plt.pro.vn/login',
    ])->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $login->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id);

    $this->assertAuthenticated();
});

test('sanctum stateful domains include APP_URL host', function () {
    $host = parse_url((string) config('app.url'), PHP_URL_HOST);

    expect($host)->not->toBeNull()
        ->and(config('sanctum.stateful'))->toContain($host);
});

test('users can update personal locale preference', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/me/locale', ['locale' => 'en'])
        ->assertOk()
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.user_locale', 'en')
        ->assertJsonPath('data.company_locale', 'vi');

    expect($user->fresh()->locale)->toBe('en');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/me/locale', ['locale' => null])
        ->assertOk()
        ->assertJsonPath('data.locale', 'vi')
        ->assertJsonPath('data.user_locale', null);

    expect($user->fresh()->locale)->toBeNull();
});

test('personal locale rejects invalid values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/me/locale', ['locale' => 'fr'])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');
});

test('AuthService::updateLocale defensively rejects an unsupported locale', function () {
    $user = User::factory()->create();

    try {
        app(AuthService::class)->updateLocale($user, 'fr');
        $this->fail('Expected a DomainException to be thrown.');
    } catch (DomainException $e) {
        expect($e->errorCode())->toBe('LOCALE_INVALID');
    }
});

test('forgot password and reset password api endpoints are disabled', function () {
    $user = User::factory()->create();

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])
        ->assertNotFound();

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/reset-password', [
            'token' => 'not-a-valid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertNotFound();
});

test('repeated failed logins are rate limited with TOO_MANY_REQUESTS', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->withHeaders(spaJsonHeaders())
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertUnauthorized();
    }

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertStatus(429)
        ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
});

test('login fails with invalid credentials envelope', function () {
    $user = User::factory()->create();

    $response = $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

    $response->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error_code', 'AUTH_INVALID_CREDENTIALS');

    $this->assertGuest();
});

test('DomainException is not reported to the log', function () {
    $handler = app(ExceptionHandler::class);

    $domain = new DomainException(
        message: 'Invalid credentials.',
        errorCode: 'AUTH_INVALID_CREDENTIALS',
        status: 401,
    );

    expect($handler->shouldReport($domain))->toBeFalse();

    // Still returned as a stable API envelope (not a 500), without reporting.
    $user = User::factory()->create();

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password-should-not-appear-in-logs',
        ])
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'AUTH_INVALID_CREDENTIALS');
});

test('unauthenticated me returns 401 envelope', function () {
    $response = $this->withHeaders(spaJsonHeaders())
        ->getJson('/api/me');

    $response->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error_code', 'UNAUTHENTICATED');
});

test('users can logout via api', function () {
    $user = User::factory()->create();

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertOk();

    $logout = $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/logout');

    $logout->assertOk()
        ->assertJsonPath('success', true);

    $this->withHeaders(spaJsonHeaders())
        ->getJson('/api/me')
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'UNAUTHENTICATED');

    $this->assertGuest();
});

test('two factor challenge without pending login returns required error', function () {
    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/two-factor/challenge', [
            'code' => '123456',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'AUTH_TWO_FACTOR_REQUIRED');
});

test('login with two factor enabled requires challenge then accepts recovery code', function () {
    $user = User::factory()->withTwoFactor()->create();

    $login = $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $login->assertOk()
        ->assertJsonPath('data.two_factor_required', true)
        ->assertJsonStructure(['data' => ['challenge_token']]);

    $this->assertGuest();

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/two-factor/challenge', [
            'recovery_code' => 'not-a-valid-recovery-code',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'AUTH_TWO_FACTOR_INVALID');

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/two-factor/challenge', [
            'recovery_code' => 'recovery-code-1',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.two_factor_required', false);

    $this->assertAuthenticated();
});
