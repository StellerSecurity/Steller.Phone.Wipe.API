<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequireClientCert
{
    public function handle(Request $request, Closure $next)
    {
        // App Service forwards the presented client cert here when Client certificate mode = Required
        $pem = $request->server('HTTP_X_ARR_CLIENTCERT');

        // If you're enforcing mTLS at Front Door/AppGW, you can forward a header
        // like X-Client-Cert-Thumbprint and use that instead. Example:
        // $forwardedThumb = $request->header('X-Client-Cert-Thumbprint');

        if (!$pem /* && !$forwardedThumb */) {
            return response()->json(['message' => 'Client certificate required'], 403);
        }

        // If using forwarded thumb, skip openssl and trust the edge header (ensure WAF rule)
        // if ($forwardedThumb) {
        //     $fp = strtoupper(str_replace(':', '', $forwardedThumb));
        // } else {
        //     // parse cert below
        // }

        $cert = @openssl_x509_read($pem);
        if (!$cert) {
            return response()->json(['message' => 'Invalid client certificate'], 403);
        }

        // SHA-256 fingerprint (remove colons, uppercase for consistent matching)
        $fp = strtoupper(str_replace(':', '', openssl_x509_fingerprint($cert, 'sha256')));

        // Optional: you can also enforce Subject/Issuer if you want
        // $parsed = openssl_x509_parse($cert);
        // $subjectCN = $parsed['subject']['CN'] ?? null;
        // $issuerCN  = $parsed['issuer']['CN']  ?? null;

        // Load allowlist from env/config (comma/space separated)
        $allowed = collect(config('security.allowed_client_thumbprints'))
            ->map(fn($t) => strtoupper(str_replace(':', '', trim($t))))
            ->filter()
            ->values();

        if ($allowed->isEmpty()) {
            // Fail closed by default
            return response()->json(['message' => 'No allowed client certs configured'], 403);
        }

        if (!$allowed->contains($fp)) {
            // Log what came in so you can copy the value for allowlisting during first setup/rotation
            logger()->warning('Client certificate rejected', ['sha256' => $fp, 'path' => $request->path()]);
            return response()->json(['message' => 'Client certificate not allowed'], 403);
        }

        // (Optional) attach fingerprint to the request for downstream logging
        $request->attributes->set('client_cert_sha256', $fp);

        return $next($request);
    }
}
