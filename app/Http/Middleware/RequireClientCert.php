<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireClientCert
{
    public function handle(Request $request, Closure $next)
    {
        $raw = $request->server('HTTP_X_ARR_CLIENTCERT');

        if (!$raw) {
            return response()->json(['message' => 'Client certificate required'], 403);
        }

        // Normalize the header to PEM (handles URL-encoding, escaped \n, bare base64 DER, or PEM)
        $pem = $this->normalizeToPem($raw);

        $cert = @openssl_x509_read($pem);
        if (!$cert) {
            logger()->warning('Invalid client certificate', [
                'len'    => strlen((string)$raw),
                'sample' => substr((string)$raw, 0, 80),
            ]);
            return response()->json(['message' => 'Invalid client certificate'], 403);
        }

        $fp = strtoupper(str_replace(':', '', openssl_x509_fingerprint($cert, 'sha256')));

        $allowed = collect(config('security.allowed_client_thumbprints'))
            ->map(fn($t) => strtoupper(str_replace(':', '', trim($t))))
            ->filter()
            ->values();

        if ($allowed->isEmpty()) {
            return response()->json(['message' => 'No allowed client certs configured'], 403);
        }

        if (!$allowed->contains($fp)) {
            logger()->warning('Client certificate rejected', ['sha256' => $fp, 'path' => $request->path()]);
            return response()->json(['message' => 'Client certificate not allowed'], 403);
        }

        $request->attributes->set('client_cert_sha256', $fp);
        return $next($request);
    }

    private function normalizeToPem(string $raw): string
    {
        // Trim quotes, URL-decode, and fix escaped newlines
        $s = trim($raw, " \t\n\r\0\x0B\"'");
        $s = urldecode($s);
        $s = str_replace(['\r\n', '\n', '\r'], "\n", $s);

        // Already PEM?
        if (str_contains($s, '-----BEGIN CERTIFICATE-----')) {
            // Ensure proper line breaks
            $s = preg_replace('/\r\n?/', "\n", $s);
            return $s;
        }

        // If it looks like JSON-base64 or b64 with spaces, strip all whitespace
        $b64 = preg_replace('/\s+/', '', $s);

        // If it wasn't valid base64, bail to raw PEM attempt
        $der = base64_decode($b64, true);
        if ($der === false) {
            // Last attempt: maybe it actually was PEM without headers (very rare)
            return "-----BEGIN CERTIFICATE-----\n" .
                chunk_split(base64_encode($s), 64, "\n") .
                "-----END CERTIFICATE-----\n";
        }

        // Wrap DER as PEM
        return "-----BEGIN CERTIFICATE-----\n" .
            chunk_split(base64_encode($der), 64, "\n") .
            "-----END CERTIFICATE-----\n";
    }
}
