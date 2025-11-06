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
use App\Http\Controllers\V1\WipeUserController;
use Mondago\ApplicationInsights\Middleware\TrackRequest;

// v1
Route::prefix('v1')->middleware(['basicAuth'])->group(function () {
    Route::prefix('wipeusercontroller')->controller(WipeUserController::class)->group(function () {
        Route::match(['get','post'], '/loginauth', 'auth');
        Route::match(['get','post'], '/add', 'add');
        Route::get('/findbytoken', 'findbytoken');
        Route::get('/findbysubscriptionid', 'findbysubscriptionid');
        Route::patch('/patch', 'patch');
    });
})->middleware(TrackRequest::class);

