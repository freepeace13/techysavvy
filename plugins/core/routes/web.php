<?php

use Illuminate\Support\Facades\Route;
use Techysavvy\Core\Http\AssetController;

Route::get('/_plugin-assets/{bundle}/{file}', AssetController::class)
    ->where(['bundle' => '[a-z0-9][a-z0-9-]*', 'file' => '[A-Za-z0-9][A-Za-z0-9._-]*'])
    ->name('core.assets');
