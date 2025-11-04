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

        // Normalize to PEM: Azure usually sends Base64 DER in X-ARR-ClientCert
        $pem = $this->toPem($raw);

        $cert = @openssl_x509_read($pem);
        if (!$cert) {
            // Log what we got to help debugging
            logger()->warning('Invalid client certificate header', [
                'len' => strlen($raw),
                'sample' => substr($raw, 0, 40),
            ]);
            return response()->json(['message' => 'Invalid client certificate'], 403);
        }

        // SHA-256 fingerprint (normalize casing/colons)
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

        // Pass the fp to downstream if you want
        $request->attributes->set('client_cert_sha256', $fp);

        return $next($request);
    }

    private function toPem(string $raw): string
    {
        $raw = trim($raw);

        // If it already looks like PEM, return as-is
        if (str_contains($raw, '-----BEGIN CERTIFICATE-----')) {
            return $raw;
        }

        // Otherwise assume Base64 DER in one long line → wrap to PEM
        $b64 = preg_replace('/\s+/', '', $raw); // remove any spaces/newlines
        $body = chunk_split($b64, 64, "\n");

        return "-----BEGIN CERTIFICATE-----\n{$body}-----END CERTIFICATE-----\n";
    }
}
