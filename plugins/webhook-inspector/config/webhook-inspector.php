<?php

return [
    // How long a bin (capture URL + its requests) lives after creation.
    'lifespan_hours' => (int) env('WEBHOOK_INSPECTOR_LIFESPAN_HOURS', 24),

    // Bodies larger than this are stored truncated, in kilobytes.
    'max_body_kb' => (int) env('WEBHOOK_INSPECTOR_MAX_BODY_KB', 256),

    // Requests kept per bin; the oldest are dropped beyond this.
    'max_requests_per_bin' => (int) env('WEBHOOK_INSPECTOR_MAX_REQUESTS_PER_BIN', 200),

    // Captures accepted per bin per minute before answering 429.
    'capture_rate_per_minute' => (int) env('WEBHOOK_INSPECTOR_CAPTURE_RATE_PER_MINUTE', 60),

    // Bins one IP can create per minute.
    'create_rate_per_minute' => (int) env('WEBHOOK_INSPECTOR_CREATE_RATE_PER_MINUTE', 10),

    // How often the viewer asks for new requests, in milliseconds.
    'poll_interval_ms' => (int) env('WEBHOOK_INSPECTOR_POLL_INTERVAL_MS', 2000),

    // How often the prune command runs, when scheduled by this plugin.
    'prune_interval_minutes' => (int) env('WEBHOOK_INSPECTOR_PRUNE_INTERVAL_MINUTES', 5),
];
