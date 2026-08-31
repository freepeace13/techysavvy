<?php

return [
    // Any disk name defined in filesystems.php ('local', 'public', 's3', ...).
    'disk' => env('DROP_SHARE_DISK', 'local'),

    // Max upload size, in kilobytes.
    'max_upload_kb' => env('DROP_SHARE_MAX_UPLOAD_KB', 25600), // 25MB

    // How long an uploaded file lives before it's eligible for deletion.
    'lifespan_hours' => env('DROP_SHARE_LIFESPAN_HOURS', 24),

    // How often the prune command runs, when scheduled by this plugin.
    'prune_interval_minutes' => env('DROP_SHARE_PRUNE_INTERVAL_MINUTES', 5),
];
