<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\DocToMarkdown\Http\Controllers\ConvertController;

// Routes registered via a plugin ServiceProvider's loadRoutesFrom() are not
// automatically wrapped in Laravel's 'web' middleware group (that only
// happens for routes declared directly in host/routes/web.php). This
// plugin's form needs CSRF protection, so it opts in explicitly here.
// No closures here: they would break `route:cache`.
Route::middleware('web')->group(function () {
    Route::view('/doc-to-markdown', 'doc-to-markdown::home')->name('doc-to-markdown.home');

    Route::post('/doc-to-markdown/convert', [ConvertController::class, 'store'])
        ->middleware('throttle:'.config('doc-to-markdown.rate_limit_per_minute').',1')
        ->name('doc-to-markdown.convert');
});
