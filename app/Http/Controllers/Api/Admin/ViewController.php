<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use App\Models\Question;
use App\Models\User;
use App\Models\View;
use App\Services\IpGeolocationService;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class ViewController extends Controller
{
    private const TYPE_MAP = [
        Course::class => [
            'label' => 'دوره',
            'short' => 'Course',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Episode::class => [
            'label' => 'قسمت',
            'short' => 'Episode',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Question::class => [
            'label' => 'پرسش',
            'short' => 'Question',
            'title_field' => 'subject',
            'slug_field' => 'slug',
        ],
        Path::class => [
            'label' => 'مسیر',
            'short' => 'Path',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        User::class => [
            'label' => 'پروفایل',
            'short' => 'User',
            'title_field' => 'username',
            'slug_field' => 'username',
        ],
    ];

    public function __construct(private IpGeolocationService $geoService)
    {
    }

    public function stats(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $periodDays = $this->calculatePeriodDays($dateFrom, $dateTo);
        $previousFrom = Carbon::parse($dateFrom)->subDays($periodDays)->toDateString();
        $previousTo = Carbon::parse($dateFrom)->subDay()->toDateString();

        $periodQuery = View::query();
        $this->applyDateRange($periodQuery, $dateFrom, $dateTo);
        $this->applyTypeFilter($periodQuery, $request);
        $this->applyUserTypeFilter($periodQuery, $request);

        $totalViews = (clone $periodQuery)->count();
        $authenticatedViews = (clone $periodQuery)->whereNotNull('user_id')->count();
        $guestViews = (clone $periodQuery)->whereNull('user_id')->count();
        $uniqueUsers = (clone $periodQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $uniqueIps = (clone $periodQuery)->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address');

        $todayViews = View::whereDate('created_at', today())->count();
        $weekViews = View::where('created_at', '>=', now()->subDays(7))->count();
        $allTimeViews = View::count();

        $previousQuery = View::query();
        $this->applyDateRange($previousQuery, $previousFrom, $previousTo);
        $this->applyTypeFilter($previousQuery, $request);
        $this->applyUserTypeFilter($previousQuery, $request);
        $previousTotal = $previousQuery->count();
        $previousGuest = (clone $previousQuery)->whereNull('user_id')->count();
        $previousAuthenticated = (clone $previousQuery)->whereNotNull('user_id')->count();
        $previousUniqueUsers = (clone $previousQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $previousUniqueIps = (clone $previousQuery)->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address');

        $changePercent = $previousTotal > 0
            ? round((($totalViews - $previousTotal) / $previousTotal) * 100, 1)
            : ($totalViews > 0 ? 100 : 0);

        $dailyRaw = (clone $periodQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $period = CarbonPeriod::create($dateFrom, $dateTo);
        $dailyViews = collect($period)->map(function ($date) use ($dailyRaw) {
            $key = $date->toDateString();
            return ['date' => $key, 'count' => (int) ($dailyRaw[$key] ?? 0)];
        })->values();

        $byType = (clone $periodQuery)
            ->select('viewable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('viewable_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'type' => $this->resolveTypeShort($row->viewable_type),
                'label' => $this->resolveTypeLabel($row->viewable_type),
                'count' => (int) $row->count,
            ])
            ->values();

        $topContent = (clone $periodQuery)
            ->select('viewable_type', 'viewable_id', DB::raw('COUNT(*) as views_count'))
            ->groupBy('viewable_type', 'viewable_id')
            ->orderByDesc('views_count')
            ->limit(15)
            ->get()
            ->map(fn ($row) => $this->formatTopContentItem($row))
            ->filter()
            ->values();

        $hourlyRaw = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::hour('created_at').' as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        $hourlyViews = collect(range(0, 23))->map(fn ($hour) => [
            'hour' => $hour,
            'label' => sprintf('%02d:00', $hour),
            'count' => (int) ($hourlyRaw[$hour] ?? 0),
        ])->values();

        $previousDailyRaw = (clone $previousQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $previousPeriod = CarbonPeriod::create($previousFrom, $previousTo);
        $previousDailyViews = collect($previousPeriod)->map(function ($date) use ($previousDailyRaw) {
            $key = $date->toDateString();
            return (int) ($previousDailyRaw[$key] ?? 0);
        })->values();

        $dailyComparison = [
            'labels' => collect(range(1, $periodDays))->map(fn ($i) => 'روز ' . $i)->values(),
            'current' => $dailyViews->pluck('count')->values(),
            'previous' => $previousDailyViews->pad($periodDays, 0)->take($periodDays)->values(),
        ];

        $byTypeComparison = $this->buildTypeComparison($periodQuery, $previousQuery);

        $comparison = [
            'total_views' => $this->compareMetric($totalViews, $previousTotal),
            'guest_views' => $this->compareMetric($guestViews, $previousGuest),
            'authenticated_views' => $this->compareMetric($authenticatedViews, $previousAuthenticated),
            'unique_users' => $this->compareMetric($uniqueUsers, $previousUniqueUsers),
            'unique_ips' => $this->compareMetric($uniqueIps, $previousUniqueIps),
        ];

        $visitorComparison = [
            'labels' => ['مهمان', 'کاربر واردشده'],
            'current' => [$guestViews, $authenticatedViews],
            'previous' => [$previousGuest, $previousAuthenticated],
        ];

        $weekdayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $weekdayRaw = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::dayOfWeek('created_at').' as weekday'), DB::raw('COUNT(*) as count'))
            ->groupBy('weekday')
            ->pluck('count', 'weekday');

        $byWeekday = collect(range(1, 7))->map(fn ($day) => [
            'weekday' => $day,
            'label' => $weekdayLabels[$day - 1] ?? (string) $day,
            'count' => (int) ($weekdayRaw[$day] ?? 0),
        ])->values();

        $geoAnalytics = $this->buildGeoAnalytics($periodQuery);
        $deviceAnalytics = $this->buildDeviceAnalytics($periodQuery);

        return response()->json([
            'message' => 'Success',
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'stats' => [
                'total_views' => $totalViews,
                'today_views' => $todayViews,
                'week_views' => $weekViews,
                'all_time_views' => $allTimeViews,
                'authenticated_views' => $authenticatedViews,
                'guest_views' => $guestViews,
                'unique_users' => $uniqueUsers,
                'unique_ips' => $uniqueIps,
                'countries_count' => count($geoAnalytics['by_country']),
                'cities_count' => count($geoAnalytics['by_city']),
                'trend' => [
                    'previous_total' => $previousTotal,
                    'change' => $totalViews - $previousTotal,
                    'change_percent' => $changePercent,
                ],
                'daily_views' => $dailyViews,
                'by_type' => $byType,
                'top_content' => $topContent,
                'hourly_views' => $hourlyViews,
                'by_weekday' => $byWeekday,
                'comparison' => $comparison,
                'daily_comparison' => $dailyComparison,
                'by_type_comparison' => $byTypeComparison,
                'visitor_comparison' => $visitorComparison,
                'geo' => $geoAnalytics,
                'devices' => $deviceAnalytics,
            ],
        ], 200);
    }

    public function index(Request $request)
    {
        $query = View::query()->with([
            'user:id,first_name,last_name,username,profile_pic',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $this->applyTypeFilter($query, $request);
        $this->applyUserTypeFilter($query, $request);

        if ($request->filled('country')) {
            $country = $request->country;
            $matchingIps = $this->findIpsByCountry($query, $country);
            $query->whereIn('ip_address', $matchingIps ?: ['__none__']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        $sort = $request->input('sort', 'newest');
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        $perPage = (int) $request->input('perPage', 20);
        $views = $query->paginate($perPage);

        $ips = collect($views->items())->pluck('ip_address')->filter()->unique()->values()->all();
        $geoMap = $this->geoService->lookupMany($ips);

        $items = collect($views->items())->map(function (View $view) use ($geoMap) {
            $content = $this->resolveViewableContent($view->viewable_type, $view->viewable_id);
            $device = $this->parseUserAgent($view->user_agent);
            $geo = $view->ip_address ? ($geoMap[$view->ip_address] ?? null) : null;

            return [
                'id' => $view->id,
                'ip_address' => $view->ip_address,
                'user_agent' => $view->user_agent,
                'created_at' => $view->created_at,
                'viewable_type' => $this->resolveTypeShort($view->viewable_type),
                'viewable_type_label' => $this->resolveTypeLabel($view->viewable_type),
                'viewable_id' => $view->viewable_id,
                'content' => $content,
                'device' => $device,
                'geo' => $geo,
                'user' => $view->user ? [
                    'id' => $view->user->id,
                    'first_name' => $view->user->first_name,
                    'last_name' => $view->user->last_name,
                    'username' => $view->user->username,
                    'profile_pic' => $view->user->profile_pic,
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'views' => $items,
            'pagination' => [
                'current_page' => $views->currentPage(),
                'last_page' => $views->lastPage(),
                'per_page' => $views->perPage(),
                'total' => $views->total(),
                'from' => $views->firstItem(),
                'to' => $views->lastItem(),
            ],
        ], 200);
    }

    public function ipInfo(Request $request)
    {
        $request->validate(['ip' => 'required|string|max:45']);

        $geo = $this->geoService->lookup($request->ip);

        return response()->json([
            'message' => 'Success',
            'geo' => $geo,
        ], 200);
    }

    private function compareMetric(int $current, int $previous): array
    {
        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'change_percent' => $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : ($current > 0 ? 100 : 0),
        ];
    }

    private function buildTypeComparison($currentQuery, $previousQuery): array
    {
        $labels = [];
        $current = [];
        $previous = [];

        foreach (self::TYPE_MAP as $class => $meta) {
            $labels[] = $meta['label'];
            $current[] = (clone $currentQuery)->where(function ($q) use ($class, $meta) {
                $q->where('viewable_type', $class)
                    ->orWhere('viewable_type', 'like', '%\\' . $meta['short']);
            })->count();
            $previous[] = (clone $previousQuery)->where(function ($q) use ($class, $meta) {
                $q->where('viewable_type', $class)
                    ->orWhere('viewable_type', 'like', '%\\' . $meta['short']);
            })->count();
        }

        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previous,
        ];
    }

    private function buildGeoAnalytics($periodQuery): array
    {
        $ipRows = (clone $periodQuery)
            ->whereNotNull('ip_address')
            ->select('ip_address', DB::raw('COUNT(*) as views_count'))
            ->groupBy('ip_address')
            ->orderByDesc('views_count')
            ->limit(80)
            ->get();

        $ips = $ipRows->pluck('ip_address')->all();
        $geoMap = $this->geoService->lookupMany($ips);

        $byCountry = [];
        $byCity = [];
        $topIps = [];

        foreach ($ipRows as $row) {
            $geo = $geoMap[$row->ip_address] ?? null;
            $country = $geo['country'] ?? 'نامشخص';
            $cityKey = ($geo['city'] ?? 'نامشخص') . '|' . ($geo['country_code'] ?? '');

            $byCountry[$country] = ($byCountry[$country] ?? 0) + (int) $row->views_count;
            $byCity[$cityKey] = [
                'city' => $geo['city'] ?? 'نامشخص',
                'country' => $country,
                'country_code' => $geo['country_code'] ?? null,
                'count' => ($byCity[$cityKey]['count'] ?? 0) + (int) $row->views_count,
            ];

            $topIps[] = [
                'ip' => $row->ip_address,
                'views_count' => (int) $row->views_count,
                'geo' => $geo,
            ];
        }

        arsort($byCountry);
        $byCountryList = collect($byCountry)->map(fn ($count, $country) => [
            'country' => $country,
            'count' => $count,
        ])->values()->take(12);

        $byCityList = collect($byCity)
            ->sortByDesc('count')
            ->values()
            ->take(12)
            ->map(fn ($item) => [
                'city' => $item['city'],
                'country' => $item['country'],
                'country_code' => $item['country_code'],
                'count' => $item['count'],
                'label' => $item['city'] . ' (' . $item['country'] . ')',
            ]);

        return [
            'by_country' => $byCountryList,
            'by_city' => $byCityList,
            'top_ips' => array_slice($topIps, 0, 15),
        ];
    }

    private function buildDeviceAnalytics($periodQuery): array
    {
        $uaRows = (clone $periodQuery)
            ->whereNotNull('user_agent')
            ->select('user_agent', DB::raw('COUNT(*) as count'))
            ->groupBy('user_agent')
            ->orderByDesc('count')
            ->limit(60)
            ->get();

        $byBrowser = [];
        $byPlatform = [];
        $byDeviceType = ['mobile' => 0, 'tablet' => 0, 'desktop' => 0, 'unknown' => 0];

        foreach ($uaRows as $row) {
            $parsed = $this->parseUserAgent($row->user_agent);
            $count = (int) $row->count;

            $browser = $parsed['browser'] ?? 'نامشخص';
            $platform = $parsed['platform'] ?? 'نامشخص';
            $deviceType = $parsed['device_type'] ?? 'unknown';

            $byBrowser[$browser] = ($byBrowser[$browser] ?? 0) + $count;
            $byPlatform[$platform] = ($byPlatform[$platform] ?? 0) + $count;
            $byDeviceType[$deviceType] = ($byDeviceType[$deviceType] ?? 0) + $count;
        }

        arsort($byBrowser);
        arsort($byPlatform);

        return [
            'by_browser' => collect($byBrowser)->map(fn ($c, $b) => ['label' => $b, 'count' => $c])->values()->take(8),
            'by_platform' => collect($byPlatform)->map(fn ($c, $p) => ['label' => $p, 'count' => $c])->values()->take(8),
            'by_device_type' => collect($byDeviceType)->map(fn ($c, $type) => [
                'type' => $type,
                'label' => match ($type) {
                    'mobile' => 'موبایل',
                    'tablet' => 'تبلت',
                    'desktop' => 'دسکتاپ',
                    default => 'نامشخص',
                },
                'count' => $c,
            ])->values()->filter(fn ($item) => $item['count'] > 0)->values(),
        ];
    }

    private function findIpsByCountry($baseQuery, string $country): array
    {
        $ips = (clone $baseQuery)
            ->whereNotNull('ip_address')
            ->distinct()
            ->pluck('ip_address')
            ->take(100)
            ->all();

        $geoMap = $this->geoService->lookupMany($ips);

        return collect($geoMap)
            ->filter(fn ($geo) => $geo && ($geo['country'] ?? '') === $country)
            ->keys()
            ->all();
    }

    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'browser' => null,
                'browser_version' => null,
                'platform' => null,
                'platform_version' => null,
                'device_type' => 'unknown',
                'device_label' => 'نامشخص',
                'is_robot' => false,
            ];
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $browser = $agent->browser();
        $platform = $agent->platform();
        $deviceType = $agent->isMobile()
            ? 'mobile'
            : ($agent->isTablet() ? 'tablet' : ($agent->isDesktop() ? 'desktop' : 'unknown'));

        $deviceLabel = trim(implode(' • ', array_filter([
            match ($deviceType) {
                'mobile' => 'موبایل',
                'tablet' => 'تبلت',
                'desktop' => 'دسکتاپ',
                default => null,
            },
            $browser,
            $platform,
        ])));

        return [
            'browser' => $browser ?: null,
            'browser_version' => $browser ? $agent->version($browser) : null,
            'platform' => $platform ?: null,
            'platform_version' => $platform ? $agent->version($platform) : null,
            'device_type' => $deviceType,
            'device_label' => $deviceLabel ?: 'نامشخص',
            'is_robot' => $agent->isRobot(),
        ];
    }

    private function applyDateRange($query, string $dateFrom, string $dateTo): void
    {
        $query->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
    }

    private function calculatePeriodDays(string $dateFrom, string $dateTo): int
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }

    private function applyUserTypeFilter($query, Request $request): void
    {
        if ($request->input('user_type') === 'guest') {
            $query->whereNull('user_id');
        } elseif ($request->input('user_type') === 'authenticated') {
            $query->whereNotNull('user_id');
        }
    }

    private function applyTypeFilter($query, Request $request): void
    {
        if (!$request->filled('viewable_type') || $request->viewable_type === 'all') {
            return;
        }

        $type = $request->viewable_type;
        $class = $this->resolveTypeClass($type);

        if ($class) {
            $query->where(function ($q) use ($class, $type) {
                $q->where('viewable_type', $class)
                    ->orWhere('viewable_type', 'like', '%\\' . $type);
            });
        }
    }

    private function resolveTypeClass(string $type): ?string
    {
        foreach (self::TYPE_MAP as $class => $meta) {
            if ($meta['short'] === $type || class_basename($class) === $type) {
                return $class;
            }
        }

        return null;
    }

    private function resolveTypeShort(?string $type): string
    {
        if (!$type) {
            return 'Unknown';
        }

        foreach (self::TYPE_MAP as $class => $meta) {
            if ($type === $class || str_ends_with($type, '\\' . $meta['short'])) {
                return $meta['short'];
            }
        }

        return class_basename($type);
    }

    private function resolveTypeLabel(?string $type): string
    {
        if (!$type) {
            return 'نامشخص';
        }

        foreach (self::TYPE_MAP as $class => $meta) {
            if ($type === $class || str_ends_with($type, '\\' . $meta['short'])) {
                return $meta['label'];
            }
        }

        return class_basename($type);
    }

    private function resolveViewableContent(?string $type, ?int $id): ?array
    {
        if (!$type || !$id) {
            return null;
        }

        $class = null;
        $meta = null;

        foreach (self::TYPE_MAP as $modelClass => $modelMeta) {
            if ($type === $modelClass || str_ends_with($type, '\\' . $modelMeta['short'])) {
                $class = $modelClass;
                $meta = $modelMeta;
                break;
            }
        }

        if (!$class) {
            return null;
        }

        $model = $class::query()->find($id);

        if (!$model) {
            return [
                'title' => '(حذف شده)',
                'slug' => null,
                'deleted' => true,
            ];
        }

        return [
            'title' => $this->formatViewableTitle($model, $class),
            'slug' => $model->{$meta['slug_field']} ?? null,
            'deleted' => false,
        ];
    }

    private function formatViewableTitle($model, string $class): string
    {
        if ($class === User::class) {
            $name = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));

            return $name !== '' ? $name : '@' . ($model->username ?? 'کاربر');
        }

        $meta = self::TYPE_MAP[$class] ?? null;
        $field = $meta['title_field'] ?? null;

        return ($field && isset($model->{$field})) ? $model->{$field} : '(بدون عنوان)';
    }

    private function formatTopContentItem($row): ?array
    {
        $content = $this->resolveViewableContent($row->viewable_type, $row->viewable_id);

        if (!$content) {
            return null;
        }

        $title = $content['title'];
        $shortTitle = mb_strlen($title) > 40 ? mb_substr($title, 0, 40) . '…' : $title;

        return [
            'viewable_type' => $this->resolveTypeShort($row->viewable_type),
            'viewable_type_label' => $this->resolveTypeLabel($row->viewable_type),
            'viewable_id' => $row->viewable_id,
            'title' => $content['title'],
            'short_title' => $shortTitle,
            'slug' => $content['slug'],
            'deleted' => $content['deleted'],
            'views_count' => (int) $row->views_count,
        ];
    }
}
