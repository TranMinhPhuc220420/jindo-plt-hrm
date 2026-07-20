<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    | supports_credentials requires explicit origins (not "*").
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'forgot-password', 'reset-password'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:5173,http://127.0.0.1:5173,http://localhost:8000,http://127.0.0.1:8000',
        )),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
