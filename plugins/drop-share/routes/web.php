<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\DropShare\Http\Controllers\DownloadController;
use Techysavvy\DropShare\Http\Controllers\UploadController;

// Routes registered via a plugin ServiceProvider's loadRoutesFrom() are not
// automatically wrapped in Laravel's 'web' middleware group (that only
// happens for routes declared directly in host/routes/web.php via
// bootstrap/app.php's withRouting()). This plugin's forms need session
// (flashed phrase/error) and CSRF protection, so it opts in explicitly here
// — a self-contained fix, no host files touched.
Route::middleware('web')->group(function () {
    Route::get('/drop-share', fn () => view('drop-share::home'))->name('drop-share.home');

    Route::post('/drop-share/upload', [UploadController::class, 'store'])
        ->name('drop-share.upload')
        ->middleware('throttle:drop-share-uploads');

    Route::post('/drop-share/download', [DownloadController::class, 'store'])
        ->name('drop-share.download')
        ->middleware('throttle:drop-share-downloads');
});
