<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect root to index.html
Route::get('/', function () {
    return redirect('/index.html');
});

// Blok semua request lainnya ke web routes
Route::fallback(function () {
    return response()->json([
        'message' => 'Forbidden'
    ], 403);
});
