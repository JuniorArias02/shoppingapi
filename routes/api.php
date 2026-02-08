<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'sendResetCode']);
Route::post('/reset-password', [AuthController::class, 'verifyAndResetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('perfil');
    });

    // Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'stats']);
    Route::get('/client/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'clientStats']);
});

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;

// Public Routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::post('/products/{id}/rate', [ProductController::class, 'rate']);
Route::post('/products/{id}/like', [ProductController::class, 'toggleLike']);
Route::post('/payments/wompi/webhook', [App\Http\Controllers\PaymentController::class, 'handleWompiWebhook']);

// Protected Routes (Admin)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Cart Routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'addToCart']);
    Route::put('/cart/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/{item}', [CartController::class, 'removeItem']);

    // Order Routes
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store']);
    Route::post('/orders/{pedido}/cancel', [App\Http\Controllers\OrderController::class, 'cancel']);
    Route::put('/orders/{pedido}/status', [App\Http\Controllers\OrderController::class, 'updateStatus']);

    // Payment Routes
    Route::post('/payments/wompi/init', [App\Http\Controllers\PaymentController::class, 'initWompiTransaction']);
    Route::post('/payments/{pago}/confirm', [App\Http\Controllers\PaymentController::class, 'confirm']);

    // Favorites
    Route::get('/favorites', [ProductController::class, 'favorites']);

    // User Settings
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);
});
