<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow credentialed requests and the XSRF headers used by Laravel + SPA
        // When the request sends an Origin header, echo it back exactly.
        // Browsers will reject credentialed responses if Access-Control-Allow-Origin is '*'.
        $origin = $request->headers->get('Origin');
        // When credentials are used, browsers require a specific origin value
        // (not '*'). Prefer the request's Origin header; fall back to app URL.
        $allowOrigin = $origin ?: config('app.url') ?: '*';

        $headers = [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, X-CSRF-TOKEN',
            'Access-Control-Allow-Credentials' => 'true',
            // Inform caches/proxies that the response varies by Origin so they don't
            // reuse a response for a different origin.
            'Vary' => 'Origin',
        ];

        if ($request->isMethod('OPTIONS')) {
            // Return preflight response; explicitly set/overwrite headers.
            $resp = response()->noContent(204);
            foreach ($headers as $key => $value) {
                $resp->headers->set($key, $value);
            }
            return $resp;
        }

        $response = $next($request);

        // Overwrite any existing CORS headers so our echoing Origin is used
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
