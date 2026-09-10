<?php

use Illuminate\Support\Facades\Route;

// Routes registered via a plugin ServiceProvider's loadRoutesFrom() are not
// automatically wrapped in Laravel's 'web' middleware group (that only
// happens for routes declared directly in host/routes/web.php). This
// plugin's form needs CSRF protection, so it opts in explicitly here.
Route::middleware('web')->group(function () {
    Route::get('/doc-to-markdown', fn () => view('doc-to-markdown::home'))->name('doc-to-markdown.home');

    Route::post('/doc-to-markdown/convert', [\Techysavvy\DocToMarkdown\Http\Controllers\ConvertController::class, 'store'])
        ->name('doc-to-markdown.convert');
});

// Pre-built JS assets shipped with the plugin (see resources/dist/) are
// served straight from disk instead of going through host/'s Vite build, so
// the tool works right after `composer install` with no npm step in host/.
Route::get('/doc-to-markdown/assets/markdown-it.min.js', fn () => response()->file(
    __DIR__.'/../resources/dist/vendor/markdown-it.min.js',
    ['Content-Type' => 'application/javascript']
))->name('doc-to-markdown.assets.markdown-it');

Route::get('/doc-to-markdown/assets/doc-to-markdown.js', fn () => response()->file(
    __DIR__.'/../resources/dist/doc-to-markdown.js',
    ['Content-Type' => 'application/javascript']
))->name('doc-to-markdown.assets.script');
