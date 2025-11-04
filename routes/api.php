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


Route::get('/__debug/parse', function (Request $r) {
    try {
        $raw = $r->server('HTTP_X_ARR_CLIENTCERT');
        if (!$raw) {
            return response()->json(['ok'=>false,'why'=>'no header X-ARR-ClientCert'], 400);
        }

        // Normalize header → PEM (handles URL-escaped, literal "\n", base64 DER, or already PEM)
        $s = trim($raw, " \t\n\r\0\x0B\"'");
        $s = urldecode($s);
        $s = str_replace(['\\r\\n','\\n','\\r'], "\n", $s);

        if (!str_contains($s, '-----BEGIN CERTIFICATE-----')) {
            $b64 = preg_replace('/\s+/', '', $s);
            $der = base64_decode($b64, true);
            $body = chunk_split(base64_encode($der !== false ? $der : $s), 64, "\n");
            $pem  = "-----BEGIN CERTIFICATE-----\n{$body}-----END CERTIFICATE-----\n";
        } else {
            $pem = preg_replace('/\r\n?/', "\n", $s);
        }

        if (!extension_loaded('openssl') || !function_exists('openssl_x509_read')) {
            return response()->json(['ok'=>false,'why'=>'openssl unavailable'], 500);
        }

        $cert = @openssl_x509_read($pem);
        if (!$cert) {
            return response()->json([
                'ok'  => false,
                'why' => 'openssl_x509_read failed',
                'peek'=> substr($pem, 0, 80),
            ], 500);
        }

        $fp = strtoupper(str_replace(':','', openssl_x509_fingerprint($cert, 'sha256')));

        return response()->json([
            'ok'     => true,
            'sha256' => $fp,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'    => false,
            'error' => get_class($e).': '.$e->getMessage(),
            'line'  => $e->getFile().':'.$e->getLine(),
        ], 500);
    }
})->middleware([]);

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

