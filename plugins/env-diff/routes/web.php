<?php

use Illuminate\Support\Facades\Route;

Route::get('/env-diff', function () {
    return view('env-diff::home');
})->name('env-diff.home');
