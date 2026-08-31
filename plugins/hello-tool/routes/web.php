<?php

use Illuminate\Support\Facades\Route;

Route::get('/hello-tool', function () {
    return view('hello-tool::home');
})->name('hello-tool.home');
