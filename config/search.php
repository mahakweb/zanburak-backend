<?php

return [

    'stop_words' => [
        'آموزش',
        'اموزش',
        'دوره',
        'course',
        'courses',
        'ویدیو',
        'video',
        'videos',
        'آموزشی',
        'از',
        'تا',
        'در',
        'با',
        'برای',
        'روی',
        'که',
        'این',
        'آن',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug / title noise words
    |--------------------------------------------------------------------------
    |
    | Filtered out when auto-building synonyms from english_title slugs.
    |
    */
    'slug_noise_words' => [
        'complete', 'full', 'course', 'courses', 'tutorial', 'tutorials',
        'guide', 'learn', 'learning', 'beginner', 'advanced', 'intermediate',
        'from', 'zero', 'scratch', 'to', 'the', 'a', 'an', 'and', 'or',
        'for', 'with', 'how', 'what', 'why', 'introduction', 'intro',
        'basic', 'basics', 'master', 'masterclass', 'bootcamp', 'training',
        'آموزش', 'دوره', 'کامل', 'صفر', 'صد', 'مقدماتی', 'پیشرفته', 'حرفه',
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual Synonym Overrides (optional)
    |--------------------------------------------------------------------------
    |
    | Usually NOT needed — synonyms are built automatically from course titles,
    | english_title slugs, tags, and categories whenever content is saved.
    |
    | Only add entries here for special cases that cannot be inferred from content.
    |
    */
    'synonym_overrides' => [
        // 'laravel' => ['laravel', 'لاراول'],
    ],

    'synonym_cache_ttl' => 3600,

    'types' => [
        'course' => [
            'model' => App\Models\Course::class,
            'db_columns' => ['title', 'english_title', 'short_description', 'description', 'meta_keywords'],
            'scout_columns' => ['title', 'english_title', 'short_description', 'description', 'meta_keywords', 'search_terms', 'tags_text', 'categories_text', 'level_title', 'teacher_name'],
        ],
        'episode' => [
            'model' => App\Models\Episode::class,
            'db_columns' => ['title', 'english_title', 'description', 'meta_keywords'],
            'scout_columns' => ['title', 'english_title', 'description', 'meta_keywords', 'search_terms', 'course_title', 'course_english_title'],
        ],
        'question' => [
            'model' => App\Models\Question::class,
            'db_columns' => ['subject', 'question', 'meta_keywords'],
            'scout_columns' => ['subject', 'question', 'meta_keywords', 'search_terms', 'tags_text', 'category_title'],
        ],
        'article' => [
            'model' => App\Models\Article::class,
            'db_columns' => ['title', 'excerpt', 'content', 'meta_keywords'],
            'scout_columns' => ['title', 'excerpt', 'content', 'meta_keywords', 'search_terms', 'tags_text', 'category_title', 'author_name'],
        ],
    ],

    'default_limit' => 20,
    'max_limit' => 50,
    'max_scout_take' => 500,
    'min_query_length' => 2,

];
