<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(function () {
                    require base_path('routes/api/auth.php');
                    require base_path('routes/api/profile.php');
                    require base_path('routes/api/discuss.php');
                    require base_path('routes/api/articles.php');
                    require base_path('routes/api/article.php');
                    require base_path('routes/api/tags.php');
                    require base_path('routes/api/cart.php');
                    require base_path('routes/api/messenger.php');
                    require base_path('routes/api/panel.php');
                    require base_path('routes/api/admin.php');
                    require base_path('routes/api/media.php');
                    require base_path('routes/api/public.php');
                    require base_path('routes/api/quiz.php');
                    require base_path('routes/api/misc.php');
                    require base_path('routes/api/broadcast.php');
                });

            Route::middleware('web')
                ->group(base_path('routes/web/home.php'));

            Route::middleware(['web', 'auth', 'administrator', 'permission.route'])
                ->prefix('administrator')
                ->group(base_path('routes/web/admin.php'));
        });

        Route::bind('courseSlug', function ($value) {
            return Course::where('slug', $value)->firstOrFail();
        });

        Route::bind('sectionSlug', function ($value) {
            return Section::where('slug', $value)->firstOrFail();
        });

        Route::bind('episodeSlug', function ($value) {
            return Episode::where('slug', $value)->firstOrFail();
        });

        Route::bind('episodeOrder', function ($value, $route) {
            $course = $route->parameter('courseSlug');
            if (! $course instanceof Course) {
                $course = Course::where('slug', $course)->firstOrFail();
            }

            return Episode::query()
                ->where('order', (int) $value)
                ->whereHas('section', fn ($q) => $q->where('course_id', $course->id))
                ->firstOrFail();
        });

        Route::bind('username', function ($value) {
            return User::where('username', $value)->firstOrFail();
        });

        Route::bind('questionSlug', function ($value) {
            return Question::where('slug', $value)->firstOrFail();
        });

        Route::bind('articleSlug', function ($value) {
            return Article::withTrashed()->where('slug', $value)->firstOrFail();
        });

        Route::bind('pathSlug', function ($value) {
            return Path::where('slug', $value)->firstOrFail();
        });

    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            if ($request->is('api/messenger/*')) {
                return Limit::perMinute(max(1, (int) config('messenger.rate_limits.baseline', 180)))
                    ->by($this->messengerRateLimitKey($request));
            }

            $credential = $request->user()?->getAuthIdentifier()
                ?: $request->bearerToken()
                ?: $request->ip();

            return Limit::perMinute(60)->by(hash('sha256', (string) $credential));
        });

        foreach (['send', 'search', 'destructive', 'typing', 'sync', 'presence', 'invite'] as $name) {
            RateLimiter::for("messenger.{$name}", function (Request $request) use ($name) {
                $limit = max(1, (int) config("messenger.rate_limits.{$name}", 60));

                return ($name === 'invite' ? Limit::perHour($limit) : Limit::perMinute($limit))
                    ->by($this->messengerRateLimitKey($request));
            });
        }
    }

    private function messengerRateLimitKey(Request $request): string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        $credential = $request->bearerToken() ?: $request->ip() ?: 'unknown';

        return 'anonymous:'.hash('sha256', $credential);
    }
}
