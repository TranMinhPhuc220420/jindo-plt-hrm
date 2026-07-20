<?php

use App\Support\Locale\SupportedLocales;

test('all() returns vi and en', function () {
    expect(SupportedLocales::all())->toBe(['vi', 'en']);
});

test('isValid accepts only supported locales', function () {
    expect(SupportedLocales::isValid('vi'))->toBeTrue();
    expect(SupportedLocales::isValid('en'))->toBeTrue();
    expect(SupportedLocales::isValid('fr'))->toBeFalse();
    expect(SupportedLocales::isValid(null))->toBeFalse();
    expect(SupportedLocales::isValid(''))->toBeFalse();
});

test('normalize passes through a valid locale unchanged', function () {
    expect(SupportedLocales::normalize('en'))->toBe('en');
    expect(SupportedLocales::normalize('vi'))->toBe('vi');
});

test('normalize falls back to config app.locale when given an invalid locale', function () {
    config(['app.locale' => 'en']);

    expect(SupportedLocales::normalize('fr'))->toBe('en');
    expect(SupportedLocales::normalize(null))->toBe('en');
});

test('normalize falls back to the default when config locale is also unsupported', function () {
    config(['app.locale' => 'fr']);

    expect(SupportedLocales::normalize('fr'))->toBe(SupportedLocales::DEFAULT);
});
