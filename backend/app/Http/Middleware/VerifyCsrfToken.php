<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Allow the SPA to add/remove cart items without posting a CSRF token.
        // Without leading slash
        'api/cart',
        'api/cart/*',
        'api/cart/add',
        // With leading slash (some matching logic expects the leading slash)
        '/api/cart',
        '/api/cart/*',
        '/api/cart/add',
        // Broad wildcard to catch any cart-related API paths
        'api/cart*',
        '/api/cart*',
    ];

    /**
     * Temporarily bypass CSRF verification for API routes while CSRF logic
     * is disabled for the SPA. This is a deliberate short-term measure.
     */
    // Use a non-type-hinted $request to remain compatible with the parent
    // middleware signature and avoid FatalError on declaration mismatch.
    public function handle($request, Closure $next)
    {
        if (method_exists($request, 'is') && $request->is('api/*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
