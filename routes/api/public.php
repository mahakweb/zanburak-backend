<?php

use Illuminate\Support\Facades\Route;

// Index and paths
Route::get('/index/home', [\App\Http\Controllers\Api\IndexController::class, 'home'])->name('api.index.home');
Route::get('/index/stats', [\App\Http\Controllers\Api\IndexController::class, 'platformStats'])->name('api.index.stats');
Route::get('/index/latestCourses', [\App\Http\Controllers\Api\IndexController::class, 'latestCourses'])->name('api.index.latest-courses');
Route::get('/index/freeCourses', [\App\Http\Controllers\Api\IndexController::class, 'freeCourses'])->name('api.index.free-courses');
Route::get('/categories', [\App\Http\Controllers\Api\IndexController::class, 'categoriesList'])->name('api.categories.index');
Route::get('/paths', [\App\Http\Controllers\Api\IndexController::class, 'paths'])->name('api.paths.index');
Route::get('/path/{pathSlug}', [\App\Http\Controllers\Api\IndexController::class, 'getPath'])->name('api.paths.show');

// Comments
Route::get('/comments', [\App\Http\Controllers\Api\CommentController::class, 'index'])->name('api.comments.index');
Route::middleware('auth:sanctum')->post('/comments/store', [\App\Http\Controllers\Api\CommentController::class, 'store'])->name('api.comments.store');

// Courses
Route::get('/courses', [\App\Http\Controllers\Api\Course\CourseController::class, 'courses'])->name('api.courses.index');
Route::get('/courses-page-filter', [\App\Http\Controllers\Api\Course\CourseController::class, 'filters'])->name('api.courses.filters');
Route::get('/course/{courseSlug}', [\App\Http\Controllers\Api\Course\CourseController::class, 'getCourse'])->name('api.courses.show');
Route::get('/course/{courseSlug}/episode/{episodeOrder}', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'getEpisode'])->where('episodeOrder', '[0-9]+')->name('api.episodes.show');

// FAQs - Public access
Route::get('/faqs', [\App\Http\Controllers\Api\Admin\FaqController::class, 'index'])->name('api.faqs.index');

// SEO Routes - Public access for search engines
Route::get('/sitemap.xml', [\App\Http\Controllers\Api\Seo\SeoController::class, 'sitemap'])->name('api.seo.sitemap');
Route::get('/sitemap-index.xml', [\App\Http\Controllers\Api\Seo\SeoController::class, 'sitemapIndex'])->name('api.seo.sitemap-index');
Route::get('/sitemap-images.xml', [\App\Http\Controllers\Api\Seo\SeoController::class, 'imageSitemap'])->name('api.seo.sitemap-images');
Route::get('/sitemap-videos.xml', [\App\Http\Controllers\Api\Seo\SeoController::class, 'videoSitemap'])->name('api.seo.sitemap-videos');
Route::get('/robots.txt', [\App\Http\Controllers\Api\Seo\SeoController::class, 'robots'])->name('api.seo.robots');
Route::get('/rss.xml', [\App\Http\Controllers\Api\Seo\SeoController::class, 'rssFeed'])->name('api.seo.rss');

// SEO Schema and Meta Tags
Route::get('/seo/course/{courseSlug}/schema.json', [\App\Http\Controllers\Api\Seo\SeoController::class, 'courseSchema'])->name('api.seo.course-schema');
Route::get('/seo/course/{courseSlug}/episode/{episodeOrder}/schema.json', [\App\Http\Controllers\Api\Seo\SeoController::class, 'episodeSchema'])->where('episodeOrder', '[0-9]+')->name('api.seo.episode-schema');
Route::get('/seo/path/{pathSlug}/schema.json', [\App\Http\Controllers\Api\Seo\SeoController::class, 'pathSchema'])->name('api.seo.path-schema');
Route::get('/seo/question/{questionSlug}/schema.json', [\App\Http\Controllers\Api\Seo\SeoController::class, 'questionSchema'])->name('api.seo.question-schema');
Route::get('/seo/meta-tags', [\App\Http\Controllers\Api\Seo\SeoController::class, 'metaTags'])->name('api.seo.meta-tags');
Route::get('/seo/health-check', [\App\Http\Controllers\Api\Seo\SeoController::class, 'seoHealthCheck'])->name('api.seo.health-check');

Route::get('/promotions/active', [\App\Http\Controllers\Api\PromotionController::class, 'active'])->name('api.promotions.active');
Route::get('/promotions/{code}', [\App\Http\Controllers\Api\PromotionController::class, 'show'])->name('api.promotions.show');


