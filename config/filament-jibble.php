<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jibble API Endpoints
    |--------------------------------------------------------------------------
    |
    | Base URLs for the different Jibble services. You can override these per
    | environment if you need to target staging or sandbox instances.
    |
    */

    'base_urls' => [
        'workspace' => env('JIBBLE_BASE_URL', 'https://workspace.prod.jibble.io/v1'),
        'time_tracking' => env('JIBBLE_TIME_TRACKING_BASE_URL', 'https://time-tracking.prod.jibble.io/v1'),
        'time_attendance' => env('JIBBLE_TIME_ATTENDANCE_BASE_URL', 'https://time-attendance.prod.jibble.io/v1'),
        'authorization' => env('JIBBLE_AUTHORIZATION_BASE_URL', 'https://authorization.prod.jibble.io/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'queue' => env('JIBBLE_SYNC_QUEUE', 'default'),
        'schedule' => [
            'people' => '0 * * * *', // hourly
            'timesheets_summary' => '*/30 * * * *', // every 30 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy Resolver
    |--------------------------------------------------------------------------
    |
    | Override this callable (via config or runtime) to customise how the
    | package resolves the current tenant when running in a multi-tenant setup.
    |
    */
    'tenant_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Configure the Eloquent models used to resolve tenants and users when
    | storing Jibble connections. The user model is required for the
    | single-tenant profile page to locate personal credentials.
    |
    */
    'user_model' => env('FILAMENT_JIBBLE_USER_MODEL', config('auth.providers.users.model', 'App\\Models\\User')),
    'tenant_model' => env('FILAMENT_JIBBLE_TENANT_MODEL', 'App\\Models\\Tenant'),
];
