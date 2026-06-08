<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Authenticate via Bearer header OR ?token= query param.
 * Required for SSE (EventSource) which cannot reliably send Authorization headers cross-origin.
 */
class AuthenticateSanctumWithToken
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            return $next($request);
        }

        $token = $request->bearerToken() ?? $request->query('token');

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->tokenable) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $accessToken->tokenable;

        if (method_exists($user, 'isDeactivated') && $user->isDeactivated()) {
            return response()->json(['message' => 'Account deactivated.'], 403);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
