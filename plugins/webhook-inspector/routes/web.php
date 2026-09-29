<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\WebhookInspector\Http\Controllers\BinController;
use Techysavvy\WebhookInspector\Http\Controllers\CaptureController;

// Plugin routes aren't wrapped in the 'web' group automatically (see
// drop-share's routes file). The viewer pages opt in; the capture endpoint
// deliberately stays outside it — external senders have no session or CSRF token.
Route::middleware('web')->group(function () {
    Route::view('/webhook-inspector', 'webhook-inspector::home')->name('webhook-inspector.home');

    Route::post('/webhook-inspector/bins', [BinController::class, 'store'])
        ->name('webhook-inspector.bins.store')
        ->middleware('throttle:webhook-inspector-create');

    Route::get('/webhook-inspector/b/{viewToken}', [BinController::class, 'show'])
        ->name('webhook-inspector.bins.show');
});

Route::any('/webhook-inspector/in/{binId}/{path?}', CaptureController::class)
    ->where('path', '.*')
    ->name('webhook-inspector.capture')
    ->middleware('throttle:webhook-inspector-capture');
