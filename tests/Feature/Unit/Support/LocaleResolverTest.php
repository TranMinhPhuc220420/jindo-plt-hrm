<?php

use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Support\Locale\LocaleResolver;

function resolver(): LocaleResolver
{
    return app(LocaleResolver::class);
}

function setCompanyLocale(Company $company, string $locale): void
{
    Setting::query()->updateOrCreate(
        ['company_id' => $company->id, 'group' => 'company', 'key' => 'locale'],
        ['value' => $locale],
    );
}

test('resolve prefers a valid user locale over the company locale', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'vi');
    $user = User::factory()->create(['locale' => 'en']);

    expect(resolver()->resolve($user))->toBe('en');
});

test('resolve falls back to the company locale when the user has none', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'en');
    $user = User::factory()->create(['locale' => null]);

    expect(resolver()->resolve($user))->toBe('en');
});

test('resolve falls back to the company locale for an unsupported user locale', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'en');
    $user = User::factory()->create(['locale' => 'fr']);

    expect(resolver()->resolve($user))->toBe('en');
});

test('resolve works without a user by using the company locale', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'en');

    expect(resolver()->resolve())->toBe('en');
});

test('companyLocale falls back to config app.locale when no setting is stored', function () {
    Company::factory()->create();

    expect(resolver()->companyLocale())->toBe(config('app.locale'));
});

test('companyLocale normalizes an invalid stored value to config app.locale', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'fr');

    expect(resolver()->companyLocale())->toBe(config('app.locale'));
});

test('companyLocale falls back to config app.locale when there is no active company', function () {
    expect(resolver()->companyLocale())->toBe(config('app.locale'));
});

test('payload reports locale, user_locale, and company_locale together', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'vi');
    $user = User::factory()->create(['locale' => 'en']);

    expect(resolver()->payload($user))->toBe([
        'locale' => 'en',
        'user_locale' => 'en',
        'company_locale' => 'vi',
    ]);
});

test('payload reports a null user_locale when the user has no valid locale', function () {
    $company = Company::factory()->create();
    setCompanyLocale($company, 'vi');
    $user = User::factory()->create(['locale' => null]);

    expect(resolver()->payload($user))->toBe([
        'locale' => 'vi',
        'user_locale' => null,
        'company_locale' => 'vi',
    ]);
});
