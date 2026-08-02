<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Score;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Question;
use App\Observers\ScoreObserver;
use App\Observers\SearchSynonymRefreshObserver;
use App\Services\Search\SearchSynonymRegistry;

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
            $publicHtml = base_path('public_html');
            if (is_dir($publicHtml)) {
                return $publicHtml;
            }

            return base_path('public');
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
        Course::observe(SearchSynonymRefreshObserver::class);
        Episode::observe(SearchSynonymRefreshObserver::class);
        Question::observe(SearchSynonymRefreshObserver::class);

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

        $this->configureMeilisearchSynonyms();
    }

    private function configureMeilisearchSynonyms(): void
    {
        // Skip during console bootstrap when tables may not exist yet (e.g. migrate)
        if (! Schema::hasTable('courses')) {
            return;
        }

        try {
            $synonyms = app(SearchSynonymRegistry::class)->all();
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        $indexSettings = config('scout.meilisearch.index-settings', []);

        foreach (array_keys($indexSettings) as $index) {
            $indexSettings[$index]['synonyms'] = $synonyms;
        }

        config(['scout.meilisearch.index-settings' => $indexSettings]);
    }
}
