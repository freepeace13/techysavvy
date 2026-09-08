<?php

use Illuminate\Support\Facades\Route;

// Routes registered via a plugin ServiceProvider's loadRoutesFrom() are not
// automatically wrapped in Laravel's 'web' middleware group (that only
// happens for routes declared directly in host/routes/web.php). This
// plugin's form needs CSRF protection, so it opts in explicitly here.
Route::middleware('web')->group(function () {
    Route::get('/doc-to-markdown', fn () => view('doc-to-markdown::home'))->name('doc-to-markdown.home');
});
