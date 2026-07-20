<?php

use App\Support\ApiResponse;

test('success envelope includes required fields', function () {
    $response = ApiResponse::success(['status' => 'ok'], 'Healthy');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toMatchArray([
            'success' => true,
            'message' => 'Healthy',
            'data' => ['status' => 'ok'],
        ]);
});

test('error envelope includes error code and field errors', function () {
    $response = ApiResponse::error(
        message: 'Validation failed.',
        status: 422,
        errorCode: 'VALIDATION_FAILED',
        errors: ['email' => ['The email field is required.']],
    );

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toMatchArray([
            'success' => false,
            'message' => 'Validation failed.',
            'error_code' => 'VALIDATION_FAILED',
            'errors' => ['email' => ['The email field is required.']],
        ]);
});
