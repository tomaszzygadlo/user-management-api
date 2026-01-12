<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is an API-only application, so web routes are minimal.
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'User Management API',
        'version' => '1.0.0',
        'documentation' => '/api/health',
    ]);
});
