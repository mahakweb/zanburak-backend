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

    /*
    |--------------------------------------------------------------------------
    | Search term aliases (manual, strict)
    |--------------------------------------------------------------------------
    |
    | Only cross-language / spelling variants for the same concept.
    | Keys must be lowercase. Used for DB search — NOT auto-generated groups.
    |
    */
    'term_aliases' => [
        'laravel' => ['لاراول'],
        'لاراول' => ['laravel', 'Laravel'],
        'vue' => ['Vue', 'vue.js', 'Vue.js', 'Vuejs', 'ویو'],
        'ویو' => ['vue', 'Vue', 'vue.js', 'Vue.js'],
        'react' => ['React', 'ری‌اکت', 'reactjs'],
        'ری‌اکت' => ['react', 'React'],
        'javascript' => ['JavaScript', 'JS', 'جاوااسکریپت'],
        'جاوااسکریپت' => ['javascript', 'JavaScript', 'JS'],
        'typescript' => ['TypeScript', 'TS', 'تایپ‌اسکریپت'],
        'تایپ‌اسکریپت' => ['typescript', 'TypeScript'],
        'php' => ['PHP', 'پی‌اچ‌پی'],
        'پی‌اچ‌پی' => ['php', 'PHP'],
        'python' => ['Python', 'پایتون'],
        'پایتون' => ['python', 'Python'],
        'docker' => ['Docker', 'داکر'],
        'داکر' => ['docker', 'Docker'],
        'git' => ['Git', 'گیت'],
        'گیت' => ['git', 'Git'],
        'nodejs' => ['Node.js', 'Nodejs', 'نود'],
        'node.js' => ['nodejs', 'Nodejs'],
        'نود' => ['nodejs', 'Node.js'],
    ],

    'synonym_cache_ttl' => 3600,

    'types' => [
        'course' => [
            'model' => App\Models\Course::class,
            'db_columns' => ['title', 'english_title', 'short_description', 'meta_keywords'],
            'scout_columns' => ['title', 'english_title', 'short_description', 'description', 'meta_keywords', 'search_terms', 'tags_text', 'categories_text', 'level_title', 'teacher_name'],
        ],
        'episode' => [
            'model' => App\Models\Episode::class,
            'db_columns' => ['title', 'english_title', 'meta_keywords'],
            'scout_columns' => ['title', 'english_title', 'description', 'meta_keywords', 'search_terms', 'course_title', 'course_english_title'],
        ],
        'question' => [
            'model' => App\Models\Question::class,
            'db_columns' => ['subject', 'meta_keywords'],
            'scout_columns' => ['subject', 'question', 'meta_keywords', 'search_terms', 'tags_text', 'category_title'],
        ],
        'article' => [
            'model' => App\Models\Article::class,
            'db_columns' => ['title', 'excerpt', 'meta_keywords'],
            'scout_columns' => ['title', 'excerpt', 'content', 'meta_keywords', 'search_terms', 'tags_text', 'category_title', 'author_name'],
        ],
    ],

    'default_limit' => 20,
    'max_limit' => 50,
    'max_scout_take' => 500,
    'min_query_length' => 2,

];
