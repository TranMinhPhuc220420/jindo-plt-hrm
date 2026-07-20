<?php

use App\Exceptions\DomainException;
use Illuminate\Support\Facades\App;

test('domain exception translates static messages for vi locale', function () {
    App::setLocale('vi');

    $exception = new DomainException(
        message: 'Invalid credentials.',
        errorCode: 'AUTH_INVALID_CREDENTIALS',
        status: 401,
    );

    expect($exception->getMessage())->toBe('Thông tin đăng nhập không hợp lệ.');
});

test('domain exception keeps english for en locale', function () {
    App::setLocale('en');

    $exception = new DomainException(
        message: 'Invalid credentials.',
        errorCode: 'AUTH_INVALID_CREDENTIALS',
        status: 401,
    );

    expect($exception->getMessage())->toBe('Invalid credentials.');
});

test('domain exception translates interpolated domain keys', function () {
    App::setLocale('vi');

    $exception = new DomainException(
        message: __('domain.settings_group_invalid', ['group' => 'foo']),
        errorCode: 'SETTINGS_GROUP_INVALID',
        status: 422,
    );

    expect($exception->getMessage())->toBe('Nhóm cài đặt không xác định [foo].');
});
