<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RedirectController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/dashboard', function () {
    return view('welcome');
});

Route::get('/analytics/{slug}', function (string $slug) {
    return view('analytics', ['slug' => $slug]);
})->where('slug', '[A-Za-z0-9_-]{3,64}');

Route::get('/{slug}', RedirectController::class)
    ->where('slug', '[A-Za-z0-9_-]{3,64}')
    ->middleware('throttle:redirect');
