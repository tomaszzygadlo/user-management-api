<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'User Management API',
        'version' => '1.0.0',
    ]);
});

// Public Authentication Routes
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Protected Authentication Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
});

// User Management API Routes (Protected)
Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    // CRUD operations
    Route::get('/', [UserController::class, 'index'])
        ->name('users.index');

    Route::post('/', [UserController::class, 'store'])
        ->name('users.store');

    Route::get('/{user}', [UserController::class, 'show'])
        ->name('users.show');

    Route::put('/{user}', [UserController::class, 'update'])
        ->name('users.update');

    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy');

    // Special actions
    Route::post('/{user}/welcome', [UserController::class, 'sendWelcome'])
        ->name('users.welcome');

    // User Emails (nested resource)
    Route::prefix('{user}/emails')->group(function () {
        Route::get('/', [UserEmailController::class, 'index'])->name('users.emails.index');
        Route::post('/', [UserEmailController::class, 'store'])->name('users.emails.store');
        Route::get('/{email}', [UserEmailController::class, 'show'])->name('users.emails.show');
        Route::put('/{email}', [UserEmailController::class, 'update'])->name('users.emails.update');
        Route::delete('/{email}', [UserEmailController::class, 'destroy'])->name('users.emails.destroy');
    });
});
