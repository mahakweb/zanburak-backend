<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use App\Models\Article;
use App\Models\Question;
use App\Models\User;
use App\Models\Category;
use App\Models\Rating;
use App\Models\Video;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SeoController extends Controller
{
    protected $siteUrl;
    protected $siteName = 'زنبورک';
    protected $defaultDescription = 'آموزش برنامه‌نویسی و توسعه وب با دوره‌های تخصصی و حرفه‌ای';

    public function __construct()
    {
        $this->siteUrl = config('app.frontend_url', env('FRONT_APP_URL', 'https://zanburak.ir'));
    }
    public function sitemap()
    {
        $siteUrl = config('app.frontend_url', env('FRONT_APP_URL', 'https://zanburak.ir'));
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Homepage
        $xml .= $this->generateUrl($siteUrl . '/', '1.0', 'daily', now());

        // Courses List
        $xml .= $this->generateUrl($siteUrl . '/courses', '1.0', 'daily', now());

        // Paths List
        $xml .= $this->generateUrl($siteUrl . '/paths', '0.9', 'weekly', now());

        // Questions List
        $xml .= $this->generateUrl($siteUrl . '/discuss', '0.9', 'daily', now());

        // Articles List
        $xml .= $this->generateUrl($siteUrl . '/articles', '0.9', 'daily', now());

        // Static Pages
        $xml .= $this->generateUrl($siteUrl . '/about', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/contact', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/cooperation', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/faq', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/what-is-vip', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/what-is-certification', '0.8', 'monthly', now());
        
        // Auth Pages
        $xml .= $this->generateUrl($siteUrl . '/auth/login', '0.7', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/auth/register', '0.7', 'monthly', now());
        
        // Discuss Pages
        $xml .= $this->generateUrl($siteUrl . '/discuss/create', '0.7', 'weekly', now());

        // Courses
        $courses = Course::where('publish', 1)
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($courses as $course) {
            $xml .= $this->generateUrl(
                $siteUrl . '/course/' . $course->slug,
                '0.8',
                'weekly',
                $course->updated_at
            );
        }

        // Episodes
        $episodes = Episode::where('publish', 1)
            ->with(['section' => function($query) {
                $query->with(['course' => function($q) {
                    $q->where('publish', 1)->select('id', 'slug');
                }]);
            }])
            ->select('slug', 'section_id', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(500) // Limit episodes to prevent sitemap from being too large
            ->get();

        foreach ($episodes as $episode) {
            if ($episode->section && $episode->section->course) {
                $xml .= $this->generateUrl(
                    $siteUrl . '/course/' . $episode->section->course->slug . '/episode/' . $episode->slug,
                    '0.7',
                    'weekly',
                    $episode->updated_at
                );
            }
        }

        // Paths
        $paths = Path::where('status', 1)
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($paths as $path) {
            $xml .= $this->generateUrl(
                $siteUrl . '/path/' . $path->slug,
                '0.8',
                'weekly',
                $path->updated_at
            );
        }

        // Questions - Only include public (non-private) questions in sitemap
        $questions = Question::where(function($query) {
                $query->where('is_private', 0)
                      ->orWhereNull('is_private');
            })
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(1000) // Limit to prevent sitemap from being too large
            ->get();

        foreach ($questions as $question) {
            $xml .= $this->generateUrl(
                $siteUrl . '/discuss/' . $question->slug,
                '0.7',
                'daily',
                $question->updated_at
            );
        }

        // Articles - Only include published articles in sitemap
        $articles = Article::where('publish', true)
            ->where('status', 'published')
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(1000)
            ->get();

        foreach ($articles as $article) {
            $xml .= $this->generateUrl(
                $siteUrl . '/article/' . $article->slug,
                '0.7',
                'weekly',
                $article->updated_at
            );
        }

        // User Profiles - Only include active users with public profiles
        $users = User::where('active', 1)
            ->whereNotNull('username')
            ->select('username', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(500) // Limit to prevent sitemap from being too large
            ->get();

        foreach ($users as $user) {
            $xml .= $this->generateUrl(
                $siteUrl . '/@' . $user->username,
                '0.6',
                'weekly',
                $user->updated_at
            );
        }

        $xml .= '</urlset>';

        return Response::make($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    private function generateUrl($loc, $priority, $changefreq, $lastmod)
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        $xml .= "    <lastmod>" . $lastmod->format('Y-m-d\TH:i:sP') . "</lastmod>\n";
        $xml .= "    <changefreq>" . $changefreq . "</changefreq>\n";
        $xml .= "    <priority>" . $priority . "</priority>\n";
        $xml .= "  </url>\n";
        
        return $xml;
    }

    public function robots()
    {
        $siteUrl = config('app.frontend_url', env('FRONT_APP_URL', 'https://zanburak.ir'));
        
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /auth/login\n";
        $robots .= "Allow: /auth/register\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /panel/\n";
        $robots .= "Disallow: /auth/\n";
        $robots .= "Disallow: /cart\n";
        $robots .= "Disallow: /payment/\n";
        $robots .= "Disallow: /*?*\n"; // Disallow URLs with query parameters (except allowed ones)
        $robots .= "Disallow: /*.json$\n";
        $robots .= "\n";
        $robots .= "User-agent: Googlebot\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /auth/login\n";
        $robots .= "Allow: /auth/register\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /panel/\n";
        $robots .= "Disallow: /auth/\n";
        $robots .= "\n";
        $robots .= "User-agent: Bingbot\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /auth/login\n";
        $robots .= "Allow: /auth/register\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /panel/\n";
        $robots .= "Disallow: /auth/\n";
        $robots .= "\n";
        $robots .= "Sitemap: " . $siteUrl . "/sitemap.xml\n";
        $robots .= "Sitemap: " . $siteUrl . "/sitemap-index.xml\n";
        $robots .= "Sitemap: " . $siteUrl . "/sitemap-images.xml\n";
        $robots .= "Sitemap: " . $siteUrl . "/sitemap-videos.xml\n";
        
        return Response::make($robots, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Generate JSON-LD Schema for Course
     */
    public function courseSchema(Request $request, $courseSlug)
    {
        $course = Course::where('slug', $courseSlug)
            ->where('publish', 1)
            ->with(['teacher', 'category', 'ratings', 'section.episode'])
            ->firstOrFail();

        $schema = $this->generateCourseSchema($course);

        return response()->json($schema, 200, [
            'Content-Type' => 'application/ld+json'
        ]);
    }

    /**
     * Generate JSON-LD Schema for Episode
     */
    public function episodeSchema(Request $request, $courseSlug, $episodeSlug)
    {
        $episode = Episode::where('slug', $episodeSlug)
            ->where('publish', 1)
            ->with(['section.course', 'videos'])
            ->whereHas('section.course', function($q) use ($courseSlug) {
                $q->where('slug', $courseSlug)->where('publish', 1);
            })
            ->firstOrFail();

        $schema = $this->generateEpisodeSchema($episode);

        return response()->json($schema, 200, [
            'Content-Type' => 'application/ld+json'
        ]);
    }

    /**
     * Generate JSON-LD Schema for Path
     */
    public function pathSchema(Request $request, $pathSlug)
    {
        $path = Path::where('slug', $pathSlug)
            ->where('status', 1)
            ->with(['courses'])
            ->firstOrFail();

        $schema = $this->generatePathSchema($path);

        return response()->json($schema, 200, [
            'Content-Type' => 'application/ld+json'
        ]);
    }

    /**
     * Generate JSON-LD Schema for Question
     */
    public function questionSchema(Request $request, $questionSlug)
    {
        $question = Question::where('slug', $questionSlug)
            ->where(function($q) {
                $q->where('is_private', 0)->orWhereNull('is_private');
            })
            ->with(['user', 'category', 'answers', 'bestAnswer'])
            ->firstOrFail();

        $schema = $this->generateQuestionSchema($question);

        return response()->json($schema, 200, [
            'Content-Type' => 'application/ld+json'
        ]);
    }

    /**
     * Get Meta Tags for a specific page
     */
    public function metaTags(Request $request)
    {
        $type = $request->input('type');
        $slug = $request->input('slug');
        $courseSlug = $request->input('course_slug');

        $meta = [];

        switch ($type) {
            case 'course':
                $course = Course::where('slug', $slug)->where('publish', 1)->first();
                if ($course) {
                    $meta = $this->getCourseMetaTags($course);
                }
                break;

            case 'episode':
                $episode = Episode::where('slug', $slug)
                    ->where('publish', 1)
                    ->whereHas('section.course', function($q) use ($courseSlug) {
                        $q->where('slug', $courseSlug)->where('publish', 1);
                    })
                    ->with(['section.course'])
                    ->first();
                if ($episode) {
                    $meta = $this->getEpisodeMetaTags($episode);
                }
                break;

            case 'path':
                $path = Path::where('slug', $slug)->where('status', 1)->first();
                if ($path) {
                    $meta = $this->getPathMetaTags($path);
                }
                break;

            case 'question':
                $question = Question::where('slug', $slug)
                    ->where(function($q) {
                        $q->where('is_private', 0)->orWhereNull('is_private');
                    })
                    ->first();
                if ($question) {
                    $meta = $this->getQuestionMetaTags($question);
                }
                break;
        }

        return response()->json($meta);
    }

    /**
     * Generate RSS Feed
     */
    public function rssFeed()
    {
        $cacheKey = 'seo_rss_feed';
        $rss = Cache::remember($cacheKey, 3600, function() {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
            $xml .= '  <channel>' . "\n";
            $xml .= '    <title>' . htmlspecialchars($this->siteName) . '</title>' . "\n";
            $xml .= '    <link>' . htmlspecialchars($this->siteUrl) . '</link>' . "\n";
            $xml .= '    <description>' . htmlspecialchars($this->defaultDescription) . '</description>' . "\n";
            $xml .= '    <language>fa-IR</language>' . "\n";
            $xml .= '    <lastBuildDate>' . now()->format('D, d M Y H:i:s T') . '</lastBuildDate>' . "\n";
            $xml .= '    <atom:link href="' . htmlspecialchars($this->siteUrl . '/rss.xml') . '" rel="self" type="application/rss+xml" />' . "\n";

            // Latest Courses
            $courses = Course::where('publish', 1)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            foreach ($courses as $course) {
                $xml .= '    <item>' . "\n";
                $xml .= '      <title>' . htmlspecialchars($course->title) . '</title>' . "\n";
                $xml .= '      <link>' . htmlspecialchars($this->siteUrl . '/course/' . $course->slug) . '</link>' . "\n";
                $xml .= '      <guid>' . htmlspecialchars($this->siteUrl . '/course/' . $course->slug) . '</guid>' . "\n";
                $xml .= '      <description>' . htmlspecialchars(strip_tags($course->short_description ?? $course->description ?? '')) . '</description>' . "\n";
                $xml .= '      <pubDate>' . $course->created_at->format('D, d M Y H:i:s T') . '</pubDate>' . "\n";
                if ($course->poster) {
                    $xml .= '      <enclosure url="' . htmlspecialchars($course->poster) . '" type="image/jpeg" />' . "\n";
                }
                $xml .= '    </item>' . "\n";
            }

            // Latest Questions
            $questions = Question::where(function($q) {
                    $q->where('is_private', 0)->orWhereNull('is_private');
                })
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($questions as $question) {
                $xml .= '    <item>' . "\n";
                $xml .= '      <title>' . htmlspecialchars($question->subject) . '</title>' . "\n";
                $xml .= '      <link>' . htmlspecialchars($this->siteUrl . '/discuss/' . $question->slug) . '</link>' . "\n";
                $xml .= '      <guid>' . htmlspecialchars($this->siteUrl . '/discuss/' . $question->slug) . '</guid>' . "\n";
                $xml .= '      <description>' . htmlspecialchars(strip_tags($question->question ?? '')) . '</description>' . "\n";
                $xml .= '      <pubDate>' . $question->created_at->format('D, d M Y H:i:s T') . '</pubDate>' . "\n";
                $xml .= '    </item>' . "\n";
            }

            $xml .= '  </channel>' . "\n";
            $xml .= '</rss>';

            return $xml;
        });

        return Response::make($rss, 200)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Generate Sitemap Index (for large sites)
     */
    public function sitemapIndex()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/sitemap.xml') . '</loc>' . "\n";
        $xml .= '    <lastmod>' . now()->format('Y-m-d\TH:i:sP') . '</lastmod>' . "\n";
        $xml .= '  </sitemap>' . "\n";

        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/sitemap-images.xml') . '</loc>' . "\n";
        $xml .= '    <lastmod>' . now()->format('Y-m-d\TH:i:sP') . '</lastmod>' . "\n";
        $xml .= '  </sitemap>' . "\n";

        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/sitemap-videos.xml') . '</loc>' . "\n";
        $xml .= '    <lastmod>' . now()->format('Y-m-d\TH:i:sP') . '</lastmod>' . "\n";
        $xml .= '  </sitemap>' . "\n";

        $xml .= '</sitemapindex>';

        return Response::make($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate Image Sitemap
     */
    public function imageSitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Course Images
        $courses = Course::where('publish', 1)
            ->whereNotNull('poster')
            ->select('slug', 'poster', 'title', 'updated_at')
            ->get();

        foreach ($courses as $course) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/course/' . $course->slug) . '</loc>' . "\n";
            $xml .= '    <image:image>' . "\n";
            $xml .= '      <image:loc>' . htmlspecialchars($course->poster) . '</image:loc>' . "\n";
            $xml .= '      <image:title>' . htmlspecialchars($course->title) . '</image:title>' . "\n";
            $xml .= '    </image:image>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        // Path Images
        $paths = Path::where('status', 1)
            ->whereNotNull('poster')
            ->select('slug', 'poster', 'title', 'updated_at')
            ->get();

        foreach ($paths as $path) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/path/' . $path->slug) . '</loc>' . "\n";
            $xml .= '    <image:image>' . "\n";
            $xml .= '      <image:loc>' . htmlspecialchars($path->poster) . '</image:loc>' . "\n";
            $xml .= '      <image:title>' . htmlspecialchars($path->title) . '</image:title>' . "\n";
            $xml .= '    </image:image>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        // Article cover images
        $articles = Article::where('publish', true)
            ->where('status', 'published')
            ->whereNotNull('cover_image')
            ->select('slug', 'cover_image', 'title')
            ->limit(1000)
            ->get();

        foreach ($articles as $article) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/article/' . $article->slug) . '</loc>' . "\n";
            $xml .= '    <image:image>' . "\n";
            $xml .= '      <image:loc>' . htmlspecialchars($article->cover_image) . '</image:loc>' . "\n";
            $xml .= '      <image:title>' . htmlspecialchars($article->title) . '</image:title>' . "\n";
            $xml .= '    </image:image>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return Response::make($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate Video Sitemap
     */
    public function videoSitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

        $episodes = Episode::where('publish', 1)
            ->with(['section.course', 'videos'])
            ->whereHas('section.course', function($q) {
                $q->where('publish', 1);
            })
            ->limit(1000)
            ->get();

        foreach ($episodes as $episode) {
            if ($episode->section && $episode->section->course && $episode->videos->count() > 0) {
                $video = $episode->videos->first();
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($this->siteUrl . '/course/' . $episode->section->course->slug . '/episode/' . $episode->slug) . '</loc>' . "\n";
                $xml .= '    <video:video>' . "\n";
                $xml .= '      <video:thumbnail_loc>' . htmlspecialchars($episode->section->course->poster ?? '') . '</video:thumbnail_loc>' . "\n";
                $xml .= '      <video:title>' . htmlspecialchars($episode->title) . '</video:title>' . "\n";
                $xml .= '      <video:description>' . htmlspecialchars(strip_tags($episode->description ?? '')) . '</video:description>' . "\n";
                if ($episode->total_time) {
                    $xml .= '      <video:duration>' . $this->convertTimeToSeconds($episode->total_time) . '</video:duration>' . "\n";
                }
                if ($episode->publish_date) {
                    $publishDate = is_string($episode->publish_date) ? Carbon::parse($episode->publish_date) : $episode->publish_date;
                    $xml .= '      <video:publication_date>' . $publishDate->format('Y-m-d\TH:i:sP') . '</video:publication_date>' . "\n";
                }
                $xml .= '      <video:family_friendly>yes</video:family_friendly>' . "\n";
                $xml .= '    </video:video>' . "\n";
                $xml .= '  </url>' . "\n";
            }
        }

        $xml .= '</urlset>';

        return Response::make($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * SEO Health Check
     */
    public function seoHealthCheck()
    {
        $issues = [];

        // Check courses without meta keywords
        $coursesWithoutMeta = Course::where('publish', 1)
            ->where(function($q) {
                $q->whereNull('meta_keywords')->orWhere('meta_keywords', '');
            })
            ->count();

        if ($coursesWithoutMeta > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$coursesWithoutMeta} دوره بدون meta keywords وجود دارد",
                'severity' => 'medium'
            ];
        }

        // Check courses without description
        $coursesWithoutDesc = Course::where('publish', 1)
            ->where(function($q) {
                $q->whereNull('description')->orWhere('description', '');
            })
            ->count();

        if ($coursesWithoutDesc > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$coursesWithoutDesc} دوره بدون توضیحات وجود دارد",
                'severity' => 'high'
            ];
        }

        // Check questions without answers
        $questionsWithoutAnswers = Question::where(function($q) {
                $q->where('is_private', 0)->orWhereNull('is_private');
            })
            ->doesntHave('answers')
            ->count();

        if ($questionsWithoutAnswers > 0) {
            $issues[] = [
                'type' => 'info',
                'message' => "{$questionsWithoutAnswers} سوال بدون پاسخ وجود دارد",
                'severity' => 'low'
            ];
        }

        return response()->json([
            'status' => count($issues) === 0 ? 'healthy' : 'issues_found',
            'issues' => $issues,
            'checked_at' => now()->toIso8601String()
        ]);
    }

    // Private helper methods

    private function generateCourseSchema($course)
    {
        $averageRating = $course->averageRating();
        $ratingCount = $course->sumOfAllRate();
        $siteUrl = $this->siteUrl;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => strip_tags($course->description ?? $course->short_description ?? ''),
            'url' => $siteUrl . '/course/' . $course->slug,
            'provider' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'url' => $siteUrl,
            ],
            'inLanguage' => 'fa-IR',
            'courseCode' => $course->slug,
        ];

        if ($course->poster) {
            $schema['image'] = $course->poster;
        }

        if ($course->teacher) {
            $schema['instructor'] = [
                '@type' => 'Person',
                'name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
            ];
        }

        if ($course->category && $course->category->count() > 0) {
            $schema['about'] = $course->category->pluck('title')->toArray();
        }

        if ($course->created_at) {
            $schema['datePublished'] = $course->created_at->toIso8601String();
        }

        if ($course->updated_at) {
            $schema['dateModified'] = $course->updated_at->toIso8601String();
        }

        if ($ratingCount > 0 && $averageRating > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($averageRating, 1),
                'ratingCount' => $ratingCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        if ($course->price !== null) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $course->price,
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'validFrom' => $course->created_at->toIso8601String(),
            ];
        }

        if ($course->total_time) {
            $schema['timeRequired'] = 'PT' . $this->convertTimeToSeconds($course->total_time) . 'S';
        }

        return $schema;
    }

    private function generateEpisodeSchema($episode)
    {
        $course = $episode->section->course;
        $siteUrl = $this->siteUrl;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $episode->title,
            'description' => strip_tags($episode->description ?? ''),
            'url' => $siteUrl . '/course/' . $course->slug . '/episode/' . $episode->slug,
            'thumbnailUrl' => $course->poster ?? '',
            'uploadDate' => $episode->publish_date ? (is_string($episode->publish_date) ? Carbon::parse($episode->publish_date)->toIso8601String() : $episode->publish_date->toIso8601String()) : $episode->created_at->toIso8601String(),
            'inLanguage' => 'fa-IR',
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteUrl . '/assets/image/logo/logo.png',
                ],
            ],
        ];

        if ($episode->total_time) {
            $schema['duration'] = 'PT' . $this->convertTimeToSeconds($episode->total_time) . 'S';
        }

        if ($course->teacher) {
            $schema['creator'] = [
                '@type' => 'Person',
                'name' => $course->teacher->first_name . ' ' . $course->teacher->last_name,
            ];
        }

        return $schema;
    }

    private function generatePathSchema($path)
    {
        $siteUrl = $this->siteUrl;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LearningResource',
            'name' => $path->title,
            'description' => strip_tags($path->description ?? $path->short_description ?? ''),
            'url' => $siteUrl . '/path/' . $path->slug,
            'provider' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteUrl . '/assets/image/logo/logo.png',
                ],
            ],
            'inLanguage' => 'fa-IR',
        ];

        if ($path->poster) {
            $schema['image'] = $path->poster;
        }

        if ($path->courses && $path->courses->count() > 0) {
            $schema['teaches'] = $path->courses->map(function($course) {
                return [
                    '@type' => 'Course',
                    'name' => $course->title,
                    'url' => $this->siteUrl . '/course/' . $course->slug,
                ];
            })->toArray();
        }

        if ($path->created_at) {
            $schema['datePublished'] = $path->created_at->toIso8601String();
        }

        if ($path->updated_at) {
            $schema['dateModified'] = $path->updated_at->toIso8601String();
        }

        return $schema;
    }

    private function generateQuestionSchema($question)
    {
        $siteUrl = $this->siteUrl;
        $bestAnswer = $question->bestAnswer();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Question',
            'name' => $question->subject,
            'text' => strip_tags($question->question ?? ''),
            'url' => $siteUrl . '/discuss/' . $question->slug,
            'dateCreated' => $question->created_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $question->user->first_name . ' ' . $question->user->last_name,
            ],
            'inLanguage' => 'fa-IR',
        ];

        if ($question->category) {
            $schema['about'] = [
                '@type' => 'Thing',
                'name' => $question->category->title,
            ];
        }

        if ($bestAnswer) {
            $schema['acceptedAnswer'] = [
                '@type' => 'Answer',
                'text' => strip_tags($bestAnswer->answer ?? ''),
                'dateCreated' => $bestAnswer->created_at->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $bestAnswer->user->first_name . ' ' . $bestAnswer->user->last_name,
                ],
            ];
        } elseif ($question->answers && $question->answers->count() > 0) {
            $latestAnswer = $question->answers->latest()->first();
            $schema['suggestedAnswer'] = [
                '@type' => 'Answer',
                'text' => strip_tags($latestAnswer->answer ?? ''),
                'dateCreated' => $latestAnswer->created_at->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $latestAnswer->user->first_name . ' ' . $latestAnswer->user->last_name,
                ],
            ];
        }

        return $schema;
    }

    private function getCourseMetaTags($course)
    {
        $url = $this->siteUrl . '/course/' . $course->slug;
        $title = $course->title . ' | ' . $this->siteName;
        $description = $course->short_description ?? strip_tags($course->description ?? '') ?? $this->defaultDescription;
        $image = $course->poster ?? $this->siteUrl . '/assets/image/logo/logo.png';
        $keywords = $course->meta_keywords ?? '';

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'og:title' => $title,
            'og:description' => $description,
            'og:image' => $image,
            'og:url' => $url,
            'og:type' => 'website',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $image,
            'canonical' => $url,
        ];
    }

    private function getEpisodeMetaTags($episode)
    {
        $course = $episode->section->course;
        $url = $this->siteUrl . '/course/' . $course->slug . '/episode/' . $episode->slug;
        $title = $episode->title . ' | ' . $course->title . ' | ' . $this->siteName;
        $description = strip_tags($episode->description ?? '') ?? $this->defaultDescription;
        $image = $course->poster ?? $this->siteUrl . '/assets/image/logo/logo.png';

        return [
            'title' => $title,
            'description' => $description,
            'og:title' => $title,
            'og:description' => $description,
            'og:image' => $image,
            'og:url' => $url,
            'og:type' => 'video.episode',
            'video:duration' => $episode->total_time ? $this->convertTimeToSeconds($episode->total_time) : null,
            'video:release_date' => $episode->publish_date ? (is_string($episode->publish_date) ? Carbon::parse($episode->publish_date)->toIso8601String() : $episode->publish_date->toIso8601String()) : null,
            'twitter:card' => 'player',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $image,
            'canonical' => $url,
        ];
    }

    private function getPathMetaTags($path)
    {
        $url = $this->siteUrl . '/path/' . $path->slug;
        $title = $path->title . ' | ' . $this->siteName;
        $description = $path->short_description ?? strip_tags($path->description ?? '') ?? $this->defaultDescription;
        $image = $path->poster ?? $this->siteUrl . '/assets/image/logo/logo.png';
        $keywords = $path->meta_keywords ?? '';

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'og:title' => $title,
            'og:description' => $description,
            'og:image' => $image,
            'og:url' => $url,
            'og:type' => 'website',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'twitter:image' => $image,
            'canonical' => $url,
        ];
    }

    private function getQuestionMetaTags($question)
    {
        $url = $this->siteUrl . '/discuss/' . $question->slug;
        $title = $question->subject . ' | ' . $this->siteName;
        $description = strip_tags($question->question ?? '') ?? $this->defaultDescription;
        $keywords = $question->meta_keywords ?? '';

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $url,
            'og:type' => 'article',
            'article:published_time' => $question->created_at->toIso8601String(),
            'article:modified_time' => $question->updated_at->toIso8601String(),
            'twitter:card' => 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
            'canonical' => $url,
        ];
    }

    private function convertTimeToSeconds($time)
    {
        // Convert time format (HH:MM:SS or MM:SS) to seconds
        if (is_numeric($time)) {
            return (int) $time;
        }

        $parts = explode(':', $time);
        $seconds = 0;
        
        if (count($parts) === 3) {
            $seconds = (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
        } elseif (count($parts) === 2) {
            $seconds = (int)$parts[0] * 60 + (int)$parts[1];
        } else {
            $seconds = (int)$parts[0];
        }

        return $seconds;
    }
}

