<?php

use Illuminate\Support\Facades\Route;

// Plugin routes aren't wrapped in the 'web' group automatically (see
// drop-share's routes file). The viewer pages opt in; the capture endpoint
// deliberately stays outside it — external senders have no session or CSRF token.
Route::middleware('web')->group(function () {
    Route::view('/webhook-inspector', 'webhook-inspector::home')->name('webhook-inspector.home');
});
