<?php

use App\Http\Controllers\Api\UserController;
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

// User Management API Routes
Route::prefix('users')->group(function () {
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
});
