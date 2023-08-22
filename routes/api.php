<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    Route::middleware(['basicAuth'])->group(function () {

        // wipe user controller
        Route::prefix('wipeusercontroller')->group(function () {
            Route::controller(\App\Http\Controllers\V1\WipeUserController::class)->group(function () {
                Route::post('/loginauth', 'auth');
                Route::get('/loginauth', 'auth');
                Route::post('/add', 'add');
                Route::get('/add', 'add');
                Route::get('/findbytoken', 'findbytoken');
                Route::patch('/patch', 'patch');
            });
        });

    });


});
