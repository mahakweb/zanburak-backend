<?php

namespace App\Http\Middleware;

use App\Events\Score\User\DailyLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckDailyLogin
{
    /**
     * Handle an incoming request.
     * بررسی می‌کند آیا کاربر امروز ورود روزانه داشته است یا نه
     * اگر نداشته باشد، Event DailyLogin را fire می‌کند
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // فقط برای کاربران authenticated
        if ($request->user()) {
            $user = $request->user();
            $cacheKey = "daily_login_check_{$user->id}_" . now()->format('Y-m-d');
            
            // بررسی Cache برای جلوگیری از بررسی مکرر در یک روز
            if (!Cache::has($cacheKey)) {
                // بررسی اینکه آیا امروز قبلاً امتیاز ورود روزانه دریافت کرده است
                $todayScore = $user->scores()
                    ->where('description', 'like', '%ورود روزانه%')
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->first();
                
                // اگر امروز امتیاز نگرفته، Event را fire کن
                if (!$todayScore) {
                    event(new DailyLogin($user));
                }
                
                // ذخیره در Cache برای 24 ساعت تا دوباره بررسی نشود
                Cache::put($cacheKey, true, now()->addDay());
            }
        }

        return $next($request);
    }
}

