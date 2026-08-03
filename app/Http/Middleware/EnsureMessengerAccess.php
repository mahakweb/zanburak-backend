<?php

namespace App\Http\Middleware;

use App\Services\Messenger\MessengerSystemConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMessengerAccess
{
    public function __construct(
        protected MessengerSystemConfig $config
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Always allow reading public system config so the SPA can show a banner.
        $routeName = optional($request->route())->getName();
        if (in_array($routeName, ['api.messenger.config'], true)) {
            return $next($request);
        }

        try {
            $this->config->assertUserAccess($user, true);
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            $status = in_array($code, [403, 503], true) ? (int) $code : 403;

            return response()->json([
                'message' => $e->getMessage(),
                'code' => $status === 503 ? 'messenger_disabled' : 'messenger_access_denied',
            ], $status);
        }

        return $next($request);
    }
}
