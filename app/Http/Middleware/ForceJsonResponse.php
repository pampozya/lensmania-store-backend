<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force every API request to be treated as expecting JSON.
 *
 * Without this, an unauthenticated API call that does NOT send
 * "Accept: application/json" makes the auth middleware try to redirect to a
 * (non-existent) "login" route, which 500s. With the Accept header set, auth
 * failures cleanly return a JSON 401.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
