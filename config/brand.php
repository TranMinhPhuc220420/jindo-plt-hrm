<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product & company brand
    |--------------------------------------------------------------------------
    |
    | Keep in sync with resources/js/lib/brand.ts for the SPA shell.
    |
    */

    'company' => env('BRAND_COMPANY', 'PLT Solutions'),

    'description' => env(
        'BRAND_DESCRIPTION',
        'HRM by PLT Solutions — human resource management for people operations, attendance, leave, and payroll.',
    ),

    /*
    | Absolute-path image used for Open Graph / social previews (og:image).
    | Served from the public disk; Facebook/Zalo require a full absolute URL.
    */
    'og_image' => env('BRAND_OG_IMAGE', '/images/welcome-hero.jpg'),

];
