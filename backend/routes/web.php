<?php

use App\Http\Controllers\Web\DocsController;
use App\Http\Controllers\Web\HomeController;

Route::get('/', [HomeController::class, 'index']);
Route::get('/docs', [DocsController::class, 'index']);
Route::get('/docs/{section}', [DocsController::class, 'show']);
Route::get('/docs/{section}/{page}', [DocsController::class, 'show']);
