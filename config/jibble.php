<?php

return [
    'base_url' => env('JIBBLE_BASE_URL', 'https://workspace.prod.jibble.io/v1'),
    'path_prefix' => env('JIBBLE_PATH_PREFIX'),

    'services' => [
        'workspace' => env('JIBBLE_BASE_URL', 'https://workspace.prod.jibble.io/v1'),
        'time_tracking' => env('JIBBLE_TIME_TRACKING_BASE_URL', 'https://time-tracking.prod.jibble.io/v1'),
        'time_attendance' => env('JIBBLE_TIME_ATTENDANCE_BASE_URL', 'https://time-attendance.prod.jibble.io/v1'),
        'authorization' => env('JIBBLE_AUTHORIZATION_BASE_URL', 'https://authorization.prod.jibble.io/v1'),
    ],

    'storage' => env('JIBBLE_STORAGE_BASE_URL', 'https://storage.prod.jibble.io/v1'),
    'storage_public_base' => env('JIBBLE_STORAGE_PUBLIC_BASE_URL', 'https://storage.prod.jibble.io'),

    'api_token' => env('JIBBLE_API_TOKEN'),

    'organization_uuid' => env('JIBBLE_ORGANIZATION_UUID'),

    'client_id' => env('JIBBLE_CLIENT_ID'),
    'client_secret' => env('JIBBLE_CLIENT_SECRET'),
    'webhook_secret' => env('JIBBLE_WEBHOOK_SECRET'),

    'oauth' => [
        'token_endpoint' => env('JIBBLE_TOKEN_ENDPOINT', 'https://identity.prod.jibble.io/connect/token'),
        'grant_type' => env('JIBBLE_OAUTH_GRANT_TYPE', 'client_credentials'),
        'scope' => env('JIBBLE_OAUTH_SCOPE', 'api1'),
        'cache_key' => env('JIBBLE_TOKEN_CACHE_KEY', 'jibble.api.access_token'),
        'cache_ttl_buffer' => (int) env('JIBBLE_TOKEN_CACHE_TTL_BUFFER', 60),
        'default_cache_ttl' => (int) env('JIBBLE_TOKEN_DEFAULT_CACHE_TTL', 3600),
    ],

    'http' => [
        'timeout' => (float) env('JIBBLE_HTTP_TIMEOUT', 10),
        'retry' => [
            'times' => (int) env('JIBBLE_RETRY_TIMES', 2),
            'sleep' => (int) env('JIBBLE_RETRY_SLEEP', 100),
        ],
    ],

    'pagination' => [
        'default_per_page' => 50,
    ],

    'endpoints' => [
        'organizations' => [
            'path' => 'Organizations',
            'detail_path' => 'Organizations/{id}',
            'organization_scoped' => false,
        ],

        'people' => [
            'path' => 'People',
            'detail_path' => 'People/{id}',
            'organization_scoped' => true,
            'defaults' => [
                'query' => [
                    '$filter' => "organizationId eq {organization_uuid}",
                ],
            ],
        ],

        'activities' => [
            'path' => 'Activities',
            'detail_path' => 'Activities/{id}',
            'organization_scoped' => true,
        ],

        'clients' => [
            'path' => 'Clients',
            'detail_path' => 'Clients/{id}',
            'organization_scoped' => true,
        ],

        'projects' => [
            'path' => 'Projects',
            'detail_path' => 'Projects/{id}',
            'organization_scoped' => true,
        ],

        'time_entries' => [
            'service' => 'time_tracking',
            'path' => 'TimeEntries',
            'detail_path' => 'TimeEntries/{id}',
            'organization_scoped' => true,
            'defaults' => [
                'query' => [
                    '$filter' => "organizationId eq {organization_uuid}",
                ],
            ],
        ],

        'timesheets' => [
            'service' => 'time_attendance',
            'path' => 'Timesheets',
            'detail_path' => 'Timesheets/{id}',
            'organization_scoped' => true,
            'supports_pagination' => false,

        ],

        'timesheets_summary' => [
            'service' => 'time_attendance',
            'path' => 'TimesheetsSummary',
            'organization_scoped' => false,
            'supports_pagination' => false,
        ],

        'hour_entries' => [
            'service' => 'time_tracking',
            'path' => 'HourEntries',
            'detail_path' => 'HourEntries/{id}',
            'organization_scoped' => false,
        ],

        'attendance_export' => [
            'service' => 'time_attendance',
            'path' => 'TimeAttendanceReportStartExport',
            'detail_path' => 'TimeAttendanceReportStartExport/{id}',
            'organization_scoped' => true,
            'defaults' => [
                'query' => [
                    '$filter' => "organizationId eq {organization_uuid}",
                ],
            ],
        ],

        'work_schedules' => [
            'service' => 'workspace',
            'path' => 'Schedules',
            'detail_path' => 'Schedules/{id}',
            'organization_scoped' => true,
            'defaults' => [
                'query' => [
                    '$filter' => "organizationId eq {organization_uuid}",
                ],
            ],
        ],

        'groups' => [
            'path' => 'Groups',
            'detail_path' => 'Groups/{id}',
            'organization_scoped' => true,
        ],

        'roles' => [
            'service' => 'authorization',
            'path' => 'Roles',
            'detail_path' => 'Roles/{id}',
            'organization_scoped' => true,
        ],

        'locations' => [
            'service' => 'workspace',
            'path' => 'Locations',
            'detail_path' => 'Locations/{id}',
            'organization_scoped' => true,
        ],

        'time_off_policies' => [
            'service' => 'workspace',
            'path' => 'TimeOffPolicies',
            'detail_path' => 'TimeOffPolicies/{id}',
            'organization_scoped' => true,
        ],

        'time_off_overview' => [
            'service' => 'time_tracking',
            'path' => 'TimeOffOverview',
            'detail_path' => 'TimeOffOverview/{id}',
            'organization_scoped' => true,
        ],
    ],
];
