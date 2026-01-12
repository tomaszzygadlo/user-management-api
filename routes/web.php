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
    return view('welcome');
});
