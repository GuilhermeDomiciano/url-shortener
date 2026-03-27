<?php

use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

Route::post('/links', [LinkController::class, 'store'])
    ->middleware('throttle:create-link');

Route::get('/links', [LinkController::class, 'index']);

Route::get('/links/{slug}/analytics', [LinkController::class, 'analytics'])
    ->where('slug', '[A-Za-z0-9_-]{3,64}');

Route::delete('/links/{slug}', [LinkController::class, 'destroy'])
    ->where('slug', '[A-Za-z0-9_-]{3,64}');
