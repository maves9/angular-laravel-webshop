<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;

Route::middleware('api')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    
    // Products API
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/products/{product}/combinations', [ProductController::class, 'combinations']);
    Route::get('/products/{product}/combinations/find', [ProductController::class, 'findCombination']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Cart endpoints that require session state. Wrap with 'web' middleware so StartSession runs.
    Route::middleware('web')->group(function () {
        Route::get('/cart', [\App\Http\Controllers\Api\CartController::class, 'index'])
            ->withoutMiddleware([
                \App\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            ]);

        Route::post('/cart/add', [\App\Http\Controllers\Api\CartController::class, 'add'])
            ->withoutMiddleware([
                \App\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            ]);

        Route::delete('/cart/clear', [\App\Http\Controllers\Api\CartController::class, 'clear'])
            ->withoutMiddleware([
                \App\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            ]);

        Route::delete('/cart/{index}', [\App\Http\Controllers\Api\CartController::class, 'remove'])
            ->withoutMiddleware([
                \App\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            ]);
    });
});
