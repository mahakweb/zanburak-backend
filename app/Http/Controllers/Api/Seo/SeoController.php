<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SeoController extends Controller
{
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

        // Static Pages
        $xml .= $this->generateUrl($siteUrl . '/about', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/contact', '0.8', 'monthly', now());
        $xml .= $this->generateUrl($siteUrl . '/faq', '0.8', 'monthly', now());

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
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /panel/\n";
        $robots .= "Disallow: /auth/\n";
        $robots .= "Disallow: /cart\n";
        $robots .= "Disallow: /payment/\n";
        $robots .= "Disallow: /*?*\n"; // Disallow URLs with query parameters
        $robots .= "Disallow: /*.json$\n";
        $robots .= "\n";
        $robots .= "User-agent: Googlebot\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /panel/\n";
        $robots .= "\n";
        $robots .= "User-agent: Bingbot\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /panel/\n";
        $robots .= "\n";
        $robots .= "Sitemap: " . $siteUrl . "/sitemap.xml\n";
        
        return Response::make($robots, 200)
            ->header('Content-Type', 'text/plain');
    }
}

