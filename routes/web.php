<?php

use App\Http\Controllers\MarkdownController;
use App\Http\Controllers\PWAGeneratorController;
use Illuminate\Support\Facades\Route;

Route::get('/markdown/{fileName}', [MarkdownController::class, 'showMarkdown']);
Route::get('/', [PWAGeneratorController::class, 'index']);
Route::post('/generate', [PWAGeneratorController::class, 'generate']);
