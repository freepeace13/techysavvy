<?php

use Illuminate\Support\Facades\Route;

Route::get('/photo-tweaker', fn () => view('photo-tweaker::home'))->name('photo-tweaker.home');
