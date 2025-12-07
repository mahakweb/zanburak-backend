<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Score;
use App\Observers\ScoreObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('path.public', function () {
            return base_path() . '/public_html';
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        
        // Load notification helper
        require_once app_path('Helpers/NotificationHelper.php');
        
        // Register observers
        Score::observe(ScoreObserver::class);

        // Use https links instead http links
        if (Request::server('HTTP_X_FORWARDED_PROTO') == 'https') {
            URL::forceScheme('https');
        }

        // Map polymorphic types for polymorphic relations
        // This allows short names (Question) to be resolved to full class names (App\Models\Question)
        // This map is used for all polymorphic relations: reportable, commentable, videoable, attachable, likeable, viewable, rateable, payable, cartable
        Relation::morphMap([
            // Core models
            // 'User' => \App\Models\User::class,
            // 'Course' => \App\Models\Course::class,
            // 'Episode' => \App\Models\Episode::class,
            // 'Section' => \App\Models\Section::class,
            // 'Path' => \App\Models\Path::class,
            // 'Plan' => \App\Models\Plan::class,
            // 'Project' => \App\Models\Project::class,
            
            // // Discussion models
            // 'Question' => \App\Models\Question::class,
            // 'Answer' => \App\Models\Answer::class,
            // 'Comment' => \App\Models\Comment::class,
            // 'QuestionCategory' => \App\Models\QuestionCategory::class,
            
            // // Content models
            // 'Video' => \App\Models\Video::class,
            // 'Attach' => \App\Models\Attach::class,
            // 'Like' => \App\Models\Like::class,
            // 'View' => \App\Models\View::class,
            // 'Rating' => \App\Models\Rating::class,
            
            // // Financial models
            // 'Payment' => \App\Models\Payment::class,
            // 'PaymentItem' => \App\Models\PaymentItem::class,
            // 'Cart' => \App\Models\Cart::class,
            // 'Discount' => \App\Models\Discount::class,
            // 'Wallet' => \App\Models\Wallet::class,
            
            // // Other important models
            // 'Category' => \App\Models\Category::class,
            // 'Level' => \App\Models\Level::class,
            // 'Status' => \App\Models\Status::class,
            // 'Mission' => \App\Models\Mission::class,
            // 'Certificate' => \App\Models\Certificate::class,
            // 'Report' => \App\Models\Report::class,
        ]);
    }
}
