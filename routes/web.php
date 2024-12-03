<?php

use App\Http\Controllers\PWAGeneratorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [PWAGeneratorController::class, 'index']);
Route::post('/generate', [PWAGeneratorController::class, 'generate']);

