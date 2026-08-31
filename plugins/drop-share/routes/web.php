<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\DropShare\Http\Controllers\UploadController;

Route::get('/drop-share', fn () => view('drop-share::home'))->name('drop-share.home');

Route::post('/drop-share/upload', [UploadController::class, 'store'])
    ->name('drop-share.upload')
    ->middleware('throttle:drop-share-uploads');
