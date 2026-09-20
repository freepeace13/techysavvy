<?php

return [
    // Max upload size, in kilobytes. PHP's upload_max_filesize/post_max_size
    // still cap it if they are lower.
    'max_upload_kb' => env('DOC_TO_MARKDOWN_MAX_UPLOAD_KB', 10240), // 10MB

    // Wall-clock budget for converting one upload, in seconds.
    'max_execution_seconds' => env('DOC_TO_MARKDOWN_MAX_EXECUTION_SECONDS', 60),

    // Conversions allowed per minute, per client IP.
    'rate_limit_per_minute' => env('DOC_TO_MARKDOWN_RATE_LIMIT', 20),
];
