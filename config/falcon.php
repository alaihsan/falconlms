<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Files are stored on the private "local" disk and are only served after
    | an authorization check. The allow-list leaves out HTML, SVG and other
    | types that a browser could execute.
    |
    */

    'uploads' => [
        'max_kilobytes' => (int) env('FALCON_UPLOAD_MAX_MB', 100) * 1024,

        'extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'txt', 'csv',
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'mp4', 'webm', 'mov', 'm4v', 'mp3', 'wav', 'm4a', 'ogg',
            'zip',
        ],

        // Files per request when uploading a whole folder.
        'max_files' => (int) env('FALCON_UPLOAD_MAX_FILES', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nginx X-Accel-Redirect
    |--------------------------------------------------------------------------
    |
    | When set (e.g. "/_protected"), Falcon checks access and then lets Nginx
    | send the file itself, which keeps PHP workers free during big video
    | downloads. Leave empty when Nginx is not configured for it.
    |
    */

    'x_accel_redirect' => env('FALCON_X_ACCEL_REDIRECT'),

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'path' => env('FALCON_BACKUP_PATH'),
        'connection' => env('FALCON_BACKUP_CONNECTION', env('DB_CONNECTION', 'pgsql')),
        'keep_days' => (int) env('FALCON_BACKUP_KEEP_DAYS', 14),
        'pg_dump' => env('FALCON_PG_DUMP', 'pg_dump'),
        'rsync' => env('FALCON_RSYNC', 'rsync'),
    ],

];
