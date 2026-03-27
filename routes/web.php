<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RedirectController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [HealthController::class, 'check']);

Route::get('/{slug}', RedirectController::class)
    ->where('slug', '[A-Za-z0-9_-]{3,64}')
    ->middleware('throttle:redirect');
