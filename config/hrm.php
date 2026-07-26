<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employee default password
    |--------------------------------------------------------------------------
    |
    | Plain-text password assigned when provisioning an employee login account
    | (onboarding create_account) and when HR resets an account to default.
    | Never expose this value in API responses.
    |
    */

    'employee_default_password' => env('EMPLOYEE_DEFAULT_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Production / bootstrap seed
    |--------------------------------------------------------------------------
    |
    | Used by ProductionBootstrapSeeder. Company code/name have defaults;
    | admin email and password are required when that seeder runs.
    |
    */

    'seed' => [
        'company_code' => env('SEED_COMPANY_CODE', 'JINDO'),
        'company_name' => env('SEED_COMPANY_NAME', 'Jindo'),
        'admin_email' => env('SEED_ADMIN_EMAIL'),
        'admin_password' => env('SEED_ADMIN_PASSWORD'),
    ],

];
