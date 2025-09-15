<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); // Sanctum: Current user API

        if ($user && !$user->active) {
            return response()->json([
                'message' => $user->deactivationMessage(),
                'reason'  => $user->deactivation_reason,
            ], 403);
        }

        return $next($request);
    }
}
