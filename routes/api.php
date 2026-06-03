<?php

use App\Http\Controllers\LinkController;
use App\Http\Controllers\PingController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', PingController::class);

Route::post('/links', [LinkController::class, 'store'])
    ->middleware('throttle:create-link');
