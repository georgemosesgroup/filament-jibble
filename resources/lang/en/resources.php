<?php

return [
    'navigation' => [
        'groups' => [
            'integrations' => 'Integrations',
        ],
    ],

    'common' => [
        'all' => 'All',
        'labels' => [
            'connection' => 'Connection',
            'status' => 'Status',
            'tenant' => 'Tenant',
            'created' => 'Created',
            'updated' => 'Updated',
            'person' => 'Person',
            'location' => 'Location',
        ],
        'actions' => [
            'copy' => 'Copy',
        ],
        'placeholders' => [
            'not_available' => '—',
        ],
        'filters' => [
            'date_range' => [
                'label' => 'Date range',
                'from' => 'From',
                'until' => 'Until',
                'indicator' => [
                    'range' => 'From :from to :until',
                    'from' => 'From :from',
                    'until' => 'Until :until',
                ],
            ],
        ],
        'booleans' => [
            'with' => 'With',
            'without' => 'Without',
            'only_inside' => 'Only inside',
            'only_outside' => 'Only outside',
        ],
    ],

    'connections' => [
        'label' => 'Jibble Connection',
        'plural' => 'Jibble Connections',
        'navigation_label' => 'Jibble Connections',
        'form' => [
            'sections' => [
                'details' => [
                    'title' => 'Connection details',
                ],
                'settings' => [
                    'title' => 'Settings',
                ],
            ],
            'fields' => [
                'name' => 'Name',
                'organization_uuid' => 'Organization UUID',
                'client_id' => 'Client ID',
                'client_secret' => 'Client Secret',
                'api_token' => 'Personal access token',
                'settings' => [
                    'label' => 'Settings',
                    'key' => 'Key',
                    'value' => 'Value',
                    'add' => 'Add setting',
                ],
            ],
        ],
        'table' => [
            'columns' => [
                'name' => 'Name',
                'organization_uuid' => 'Org UUID',
                'created_at' => 'Created',
                'updated_at' => 'Updated',
            ],
            'filters' => [
                'tenant' => [
                    'label' => 'Tenant',
                ],
                'organization' => [
                    'label' => 'Organization',
                ],
                'credentials' => [
                    'label' => 'Credentials',
                    'with' => 'With credentials',
                    'without' => 'Without credentials',
                ],
            ],
            'actions' => [
                'sync' => 'Sync now',
                'sync_all' => 'Sync All',
            ],
        ],
        'notifications' => [
            'sync_started' => [
                'title' => 'Sync started',
                'all' => 'Sync jobs dispatched for all connections.',
                'single' => 'Sync jobs dispatched for connection :name.',
            ],
        ],
    ],

    'people' => [
        'label' => 'Jibble Person',
        'plural' => 'Jibble People',
        'navigation_label' => 'Jibble People',
        'table' => [
            'columns' => [
                'full_name' => 'Name',
                'email' => 'Email',
                'connection' => 'Connection',
                'status' => 'Status',
                'created_at' => 'Created',
            ],
            'filters' => [
                'status' => [
                    'label' => 'Status',
                ],
                'connection' => [
                    'label' => 'Connection',
                ],
                'email' => [
                    'label' => 'Email',
                    'with' => 'With email',
                    'without' => 'Without email',
                ],
            ],
        ],
    ],

    'locations' => [
        'label' => 'Jibble Location',
        'plural' => 'Jibble Locations',
        'navigation_label' => 'Jibble Locations',
        'tabs' => [
            'main' => 'Jibble Location',
            'details' => 'Details',
            'geo' => 'Geo',
            'payload' => 'Payload',
        ],
        'sections' => [
            'general' => [
                'title' => 'General',
                'description' => 'Basic location information',
            ],
            'meta' => 'Meta',
        ],
        'fieldsets' => [
            'coordinates' => 'Coordinates',
            'geofence' => 'Geofence',
        ],
        'fields' => [
            'name' => 'Name',
            'code' => 'Code',
            'status' => 'Status',
            'status_hint' => 'Status synced from Jibble.',
            'address' => 'Address',
            'connection' => 'Connection',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'radius' => 'Radius',
            'units' => 'Units',
            'payload' => 'Raw JSON (read-only)',
            'payload_hint' => 'Original Jibble response for debugging.',
            'created_at' => 'Created',
            'updated_at' => 'Last Imported',
            'tenant' => 'Tenant',
        ],
        'table' => [
            'columns' => [
                'code' => 'Code',
                'connection' => 'Connection',
                'address' => 'Address',
                'latitude' => 'Lat',
                'longitude' => 'Lng',
                'radius' => 'Radius (m)',
                'imported' => 'Imported',
            ],
            'filters' => [
                'status' => [
                    'label' => 'Status',
                ],
                'connection' => [
                    'label' => 'Connection',
                ],
                'coordinates' => [
                    'label' => 'Coordinates',
                    'with' => 'With coordinates',
                    'without' => 'Without coordinates',
                ],
            ],
        ],
    ],

    'sync_logs' => [
        'label' => 'Sync Log',
        'plural' => 'Sync Logs',
        'navigation_label' => 'Sync Logs',
        'table' => [
            'columns' => [
                'queued' => 'Queued',
                'resource' => 'Resource',
                'status' => 'Status',
                'connection' => 'Connection',
                'message' => 'Message',
                'started_at' => 'Started',
                'finished_at' => 'Finished',
            ],
            'filters' => [
                'status' => [
                    'label' => 'Status',
                    'options' => [
                        'running' => 'Running',
                        'failed' => 'Failed',
                        'completed' => 'Completed',
                    ],
                ],
                'resource' => [
                    'label' => 'Resource',
                    'options' => [
                        'people' => 'People',
                        'timesheets' => 'Timesheets',
                        'timesheets_summary' => 'Timesheet Summary',
                        'time_entries' => 'Time Entries',
                    ],
                ],
                'connection' => [
                    'label' => 'Connection',
                ],
                'queued_between' => [
                    'label' => 'Queued between',
                ],
            ],
        ],
    ],

    'time_entries' => [
        'label' => 'Jibble Time Entry',
        'plural' => 'Jibble Time Entries',
        'navigation_label' => 'Jibble Time Entries',
        'table' => [
            'columns' => [
                'picture' => 'Photo',
                'date' => 'Date',
                'type' => 'Type',
                'status' => 'Status',
                'person' => 'Person',
                'connection' => 'Connection',
                'time' => 'Time (UTC)',
                'local_time' => 'Local Time',
                'location' => 'Location',
                'client' => 'Client',
                'project' => 'Project',
                'activity' => 'Activity',
                'location_id' => 'Location ID',
                'note' => 'Note',
                'outside_geofence' => 'Outside geofence',
            ],
            'filters' => [
                'date_range' => [
                    'label' => 'Date range',
                ],
                'connection' => [
                    'label' => 'Connection',
                ],
                'person' => [
                    'label' => 'Person',
                ],
                'location' => [
                    'label' => 'Location',
                ],
                'status' => [
                    'label' => 'Status',
                ],
                'type' => [
                    'label' => 'Type',
                ],
                'outside_geofence' => [
                    'label' => 'Outside geofence',
                    'true' => 'Only outside',
                    'false' => 'Only inside',
                ],
                'picture' => [
                    'label' => 'Picture',
                    'with' => 'With picture',
                    'without' => 'Without picture',
                ],
            ],
        ],
    ],

    'timesheets' => [
        'label' => 'Jibble Timesheet',
        'plural' => 'Jibble Timesheets',
        'navigation_label' => 'Jibble Timesheets',
        'table' => [
            'columns' => [
                'date' => 'Date',
                'status' => 'Status',
                'person' => 'Person',
                'connection' => 'Connection',
                'tracked_seconds' => 'Tracked (s)',
                'billable_seconds' => 'Billable (s)',
                'break_seconds' => 'Break (s)',
            ],
            'filters' => [
                'date_range' => [
                    'label' => 'Date range',
                ],
                'status' => [
                    'label' => 'Status',
                ],
                'connection' => [
                    'label' => 'Connection',
                ],
                'person' => [
                    'label' => 'Person',
                ],
            ],
        ],
    ],

    'timesheet_summaries' => [
        'label' => 'Timesheet Summary',
        'plural' => 'Timesheet Summaries',
        'navigation_label' => 'Timesheet Summaries',
        'table' => [
            'columns' => [
                'person' => 'Person',
                'connection' => 'Connection',
                'period' => 'Period',
                'tracked' => 'Tracked',
                'billable' => 'Billable',
                'breaks' => 'Breaks',
                'updated' => 'Updated',
            ],
            'filters' => [
                'date_range' => [
                    'label' => 'Date range',
                ],
                'connection' => [
                    'label' => 'Connection',
                ],
                'person' => [
                    'label' => 'Person',
                ],
            ],
        ],
    ],

    'plugin' => [
        'menu' => [
            'tenant_settings' => 'Jibble Integration',
            'profile_settings' => 'Jibble Settings',
        ],
    ],

    'pages' => [
        'profile' => [
            'navigation_label' => 'Jibble Settings',
            'title' => 'Jibble Integration',
            'form' => [
                'fields' => [
                    'name' => 'Connection name',
                    'organization' => 'Organization',
                    'client_id' => 'Client ID',
                    'client_secret' => 'Client Secret',
                    'api_token' => 'Personal access token',
                ],
                'placeholders' => [
                    'organization' => 'Fetch organizations from Jibble',
                ],
                'helpers' => [
                    'organization' => 'Save your credentials, then load organizations from the API.',
                ],
                'actions' => [
                    'fetch' => 'Fetch from Jibble',
                ],
            ],
            'notifications' => [
                'no_connection' => [
                    'title' => 'No connection saved',
                    'body' => 'Save your Jibble credentials before starting a sync.',
                ],
                'sync_started' => [
                    'title' => 'Sync started',
                    'body' => 'Sync jobs dispatched for your Jibble connection.',
                ],
                'sync_failed' => [
                    'title' => 'Unable to start sync',
                ],
                'saved' => [
                    'title' => 'Jibble connection saved',
                ],
                'save_failed' => [
                    'title' => 'Unable to save connection',
                ],
                'verified' => [
                    'title' => 'Connection verified',
                    'body' => 'Successfully communicated with Jibble API.',
                ],
                'verify_failed' => [
                    'title' => 'Unable to verify connection',
                ],
                'save_first' => [
                    'title' => 'Save credentials first',
                    'body' => 'Save your Jibble credentials before loading organizations.',
                ],
                'no_organizations' => [
                    'title' => 'No organizations found',
                    'body' => 'The Jibble API returned an empty list of organizations.',
                ],
                'organizations_loaded' => [
                    'title' => 'Organizations loaded',
                    'body' => 'Select your organization from the dropdown before saving.',
                ],
                'organizations_failed' => [
                    'title' => 'Failed to load organizations',
                ],
            ],
        ],
        'tenant' => [
            'navigation_label' => 'Jibble Integration',
            'title' => 'Tenant Jibble Settings',
            'form' => [
                'fields' => [
                    'name' => 'Connection name',
                    'organization' => 'Organization',
                    'client_id' => 'Client ID',
                    'client_secret' => 'Client secret',
                    'api_token' => 'Personal access token',
                ],
                'placeholders' => [
                    'organization' => 'Select organization',
                ],
                'helpers' => [
                    'name' => 'Short identifier for this connection (e.g. "primary").',
                    'organization' => 'Fetch organizations using your credentials, then choose one.',
                    'client_id' => 'Required when using OAuth client credentials instead of an API token.',
                    'client_secret' => 'Required when using OAuth client credentials instead of an API token.',
                    'api_token' => 'If you provide an API token, the client credentials can be left blank.',
                ],
                'actions' => [
                    'fetch_organizations' => 'Load organizations',
                ],
                'sections' => [
                    'connection' => 'Connection',
                    'connection_desc' => 'Configure which organization this tenant will use.',
                    'credentials' => 'Credentials',
                    'credentials_desc' => 'Choose either OAuth Client (Client ID/Secret) or a personal API token.',
                    'oauth' => 'OAuth Client',
                ],
            ],
            'notifications' => [
                'saved' => [
                    'title' => 'Jibble tenant settings updated',
                ],
                'save_failed' => [
                    'title' => 'Unable to update settings',
                ],
                'verified' => [
                    'title' => 'Connection verified',
                    'body' => 'Successfully communicated with Jibble API.',
                ],
                'verify_failed' => [
                    'title' => 'Unable to verify connection',
                ],
                'save_first' => [
                    'title' => 'Save credentials first',
                    'body' => 'Save your Jibble credentials before loading organizations.',
                ],
                'no_organizations' => [
                    'title' => 'No organizations found',
                    'body' => 'The Jibble API returned an empty list of organizations.',
                ],
                'organizations_loaded' => [
                    'title' => 'Organizations loaded',
                    'body' => 'Select your organization from the dropdown before saving.',
                ],
                'organizations_failed' => [
                    'title' => 'Failed to load organizations',
                ],
            ],
            'validation' => [
                'credentials' => 'Provide an API token or both Client ID and Client secret.',
            ],
            'profile' => [
                'page_title' => 'Tenant settings',
                'section_title' => 'Jibble preferences',
                'fields' => [
                    'default_project' => 'Default project',
                    'default_group' => 'Default group',
                ],
                'placeholders' => [
                    'default_project' => 'Select project',
                    'default_group' => 'Select group',
                ],
                'helpers' => [
                    'default_project' => 'Choose which Jibble project to associate with this tenant.',
                    'default_group' => 'Choose which Jibble group to filter data by default.',
                ],
                'actions' => [
                    'fetch_projects' => 'Load projects',
                    'fetch_groups' => 'Load groups',
                ],
                'notifications' => [
                    'requires_connection' => [
                        'title' => 'Add credentials first',
                        'body' => 'Save Jibble credentials for this tenant before loading preferences.',
                    ],
                    'requires_organization' => [
                        'title' => 'Select an organization',
                        'body' => 'Choose an organization on the Jibble settings page before loading projects or groups.',
                    ],
                    'no_projects' => [
                        'title' => 'No projects found',
                        'body' => 'The Jibble API returned an empty list of projects.',
                    ],
                    'no_groups' => [
                        'title' => 'No groups found',
                        'body' => 'The Jibble API returned an empty list of groups.',
                    ],
                    'projects_loaded' => [
                        'title' => 'Projects loaded',
                        'body' => 'Select a project to store as the default for this tenant.',
                    ],
                    'groups_loaded' => [
                        'title' => 'Groups loaded',
                        'body' => 'Select a group to store as the default for this tenant.',
                    ],
                    'projects_failed' => [
                        'title' => 'Failed to load projects',
                    ],
                    'groups_failed' => [
                        'title' => 'Failed to load groups',
                    ],
                ],
            ],
        ],
        'api_explorer' => [
            'navigation_label' => 'Jibble API',
            'fieldsets' => [
                'request' => 'Request',
                'timesheet_filters' => 'Timesheet filters',
                'timesheet_summary_filters' => 'Timesheets summary filters',
            ],
            'fields' => [
                'connection' => [
                    'label' => 'Connection',
                    'helper' => 'Choose which Jibble credentials to use.',
                ],
                'resource' => 'Resource',
                'custom_endpoint' => [
                    'label' => 'Custom endpoint',
                    'placeholder' => 'organizations/{organization}/custom-path',
                ],
                'http_method' => 'Method',
                'paginate' => 'Use pagination',
                'identifier' => [
                    'label' => 'Identifier',
                    'helper' => 'Required for show, update, and delete operations.',
                ],
                'organization_uuid' => [
                    'label' => 'Organization UUID',
                    'helper' => 'Overrides the default organization when provided.',
                ],
                'replacements' => [
                    'label' => 'Path placeholders',
                    'add' => 'Add placeholder',
                ],
                'query' => [
                    'label' => 'Query parameters',
                    'add' => 'Add parameter',
                ],
                'payload' => 'Payload (JSON)',
                'date' => 'Date',
                'period' => 'Period',
                'start_date' => 'Start date',
                'end_date' => 'End date',
                'person_ids' => 'Person IDs',
            ],
            'resources' => [
                'custom' => 'Custom endpoint',
            ],
            'errors' => [
                'missing_endpoint' => 'Please provide a custom endpoint path.',
                'unsupported_method' => 'Unsupported HTTP method: :method',
                'missing_identifier_update' => 'Identifier is required for update operations.',
                'missing_identifier_delete' => 'Identifier is required for delete operations.',
                'invalid_json' => 'Payload must be valid JSON: :error',
                'payload_not_object' => 'Decoded payload must be a JSON object.',
            ],
        ],
    ],

    'widgets' => [
        'sync_status' => [
            'title' => 'Jibble Sync',
            'empty' => [
                'value' => 'No syncs yet',
                'description' => 'Run jibble:sync to import data',
            ],
            'description' => [
                'finished' => 'Finished :time',
                'started' => 'Started :time',
                'queued' => 'Queued',
            ],
            'status' => [
                'completed' => 'Completed',
                'failed' => 'Failed',
                'running' => 'Running',
                'default' => 'Queued',
            ],
        ],
        'timesheet_heatmap' => [
            'employee' => 'Employee',
            'total' => 'Total',
            'month' => 'Month',
            'year' => 'Year',
            'search_placeholder' => 'Search name or email…',
            'search_empty' => 'No team members match your filters.',
            'legend_title' => 'Legend',
            'statuses' => [
                'missing' => 'No data',
                'off' => 'Off / Holiday',
                'target' => '≤ 6h',
                'extended' => '6–8h',
                'overtime' => '8–10h',
                'excessive' => '10h+',
            ],
            'tooltip' => [
                'no_data' => 'No tracked time',
            ],
            'empty' => [
                'heading' => 'No tracked time yet',
                'body' => 'We did not find any synced time for your team during this month.',
            ],
            'no_branch' => [
                'heading' => 'No tenant selected',
                'body' => 'Select a tenant to view the heatmap for its team.',
            ],
        ],
    ],
];
