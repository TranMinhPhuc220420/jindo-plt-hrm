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

];
