<?php

namespace App\Providers;

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
                ->group(base_path('routes/api.php'));

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

        Route::bind('username', function ($value) {
            return User::where('username', $value)->firstOrFail();
        });

        Route::bind('questionSlug', function ($value) {
            return Question::where('slug', $value)->firstOrFail();
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
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
