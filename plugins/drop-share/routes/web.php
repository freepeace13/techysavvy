<?php

use Illuminate\Support\Facades\Route;

Route::get('/drop-share', fn () => view('drop-share::home'))->name('drop-share.home');
