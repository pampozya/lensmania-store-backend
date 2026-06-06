<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function __construct(private JwtService $jwtService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();

        if ($token === '') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $user = $this->jwtService->identifyUser($token);
        } catch (Throwable) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
