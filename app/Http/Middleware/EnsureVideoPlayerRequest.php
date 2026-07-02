<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVideoPlayerRequest
{
    /**
     * Block direct media download tools (e.g. IDM) that do not send the player header.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-Zanburak-Player') !== '1') {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
