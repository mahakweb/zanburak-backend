<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMobileIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasVerifiedMobile()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'شماره موبایل شما تأیید نشده است.',
                    'mobile_verification_required' => true,
                ], 403);
            }

            return redirect(config('app.frontend_url') . '/panel/profile/manage-phone');
        }

        return $next($request);
    }
}
