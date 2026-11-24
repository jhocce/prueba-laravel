<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::apiResource('books', BookController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

Route::apiResource('categories', CategoryController::class)
    ->only(['index', 'store', 'destroy']);
