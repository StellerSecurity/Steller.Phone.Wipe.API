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



Route::get('/_ip', function (\Illuminate\Http\Request $r) {
    return [
        'ip' => $r->ip(),
        'xff' => $r->header('X-Forwarded-For'),
        'proto' => $r->header('X-Forwarded-Proto'),
    ];
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
                Route::get('/findbysubscriptionid', 'findbysubscriptionid');
                Route::patch('/patch', 'patch')->middleware('throttle:wipe.critical');
            });
        });

    });


});


Route::prefix('v2')->group(function () {

    Route::middleware(['basicAuth'])->group(function () {

        // wipe user controller
        Route::prefix('wipeusercontroller')->group(function () {
            Route::controller(\App\Http\Controllers\V1\WipeUserController::class)->group(function () {
                Route::post('/loginauth', 'auth');
                Route::get('/loginauth', 'auth');
                Route::post('/add', 'add');
                Route::get('/add', 'add');
                Route::get('/findbytoken', 'findbytoken');
                Route::get('/findbysubscriptionid', 'findbysubscriptionid');
                Route::patch('/patch', 'patch')->middleware('throttle:wipe.critical');
            });
        });

    });


});


