<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    // If your app is *only* reachable via Azure Front Door/App Gateway/App Service,
    // this is simplest. Otherwise list your proxy IPs/CIDRs instead of '*'.
    protected $proxies = '*';

    // Trust the common X-Forwarded-* headers (no AWS flag)
    protected $headers = Request::HEADER_X_FORWARDED_FOR
    | Request::HEADER_X_FORWARDED_HOST
    | Request::HEADER_X_FORWARDED_PORT
    | Request::HEADER_X_FORWARDED_PROTO;
}
