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

Route::get('/__debug/cert', function (\Illuminate\Http\Request $r) {
    $raw = $r->server('HTTP_X_ARR_CLIENTCERT');
    return response()->json([
        'present' => (bool)$raw,
        'len'     => $raw ? strlen($raw) : 0,
        'sample'  => $raw ? substr($raw, 0, 120) : null,
    ]);
})->middleware([]); // no clientcert/basicauth here

// v1
Route::prefix('v1')->middleware(['clientcert','basicAuth'])->group(function () {
    Route::prefix('wipeusercontroller')->controller(WipeUserController::class)->group(function () {
        Route::match(['get','post'], '/loginauth', 'auth');
        Route::match(['get','post'], '/add', 'add');
        Route::get('/findbytoken', 'findbytoken');
        Route::get('/findbysubscriptionid', 'findbysubscriptionid');
        Route::patch('/patch', 'patch');
    });
});

// v2 (uses v1 controller for now; swap when ready)
Route::prefix('v2')->middleware(['clientcert','basicAuth'])->group(function () {
    Route::prefix('wipeusercontroller')->controller(WipeUserController::class)->group(function () {
        Route::match(['get','post'], '/loginauth', 'auth');
        Route::match(['get','post'], '/add', 'add');
        Route::get('/findbytoken', 'findbytoken');
        Route::get('/findbysubscriptionid', 'findbysubscriptionid');
        Route::patch('/patch', 'patch');
    });
});

