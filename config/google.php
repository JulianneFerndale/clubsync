<?php

return [

    'calendar' => [
        /*
        |----------------------------------------------------------------------
        | Google Calendar sync (institutional Google Workspace)
        |----------------------------------------------------------------------
        | Background sync of confirmed club activities to a shared Google
        | Calendar via a service account with domain-wide delegation. See the
        | setup notes in .env.example / workflows/POLICY.md.
        */

        // Master switch. Sync is a no-op unless this is true AND the key file exists.
        'enabled' => (bool) env('GOOGLE_CALENDAR_ENABLED', false),

        // Absolute path (or storage-relative) to the service-account JSON key file.
        'credentials' => env('GOOGLE_SERVICE_ACCOUNT_JSON', storage_path('app/google/service-account.json')),

        // The Workspace user the service account impersonates (domain-wide delegation
        // "subject"). Must be a real @sccpag.edu.ph account that owns/can edit the calendar.
        'impersonate' => env('GOOGLE_CALENDAR_IMPERSONATE'),

        // Target calendar id ("primary" of the impersonated user, or a shared calendar id).
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),

        // IANA timezone used for event start/end.
        'timezone' => env('GOOGLE_CALENDAR_TIMEZONE', 'Asia/Manila'),
    ],

];
