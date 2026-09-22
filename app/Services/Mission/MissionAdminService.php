<?php

namespace App\Services\Mission;

use App\Models\Mission;
use App\Models\MissionCategory;
use App\Models\Score;
use App\Models\UserMission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MissionAdminService
{
    protected ?array $wiredMissionIds = null;

    public function getWiredMissionIds(): array
    {
        if ($this->wiredMissionIds !== null) {
            return $this->wiredMissionIds;
        }

        $ids = [];
        $paths = [
            app_path('Listeners/Mission'),
            app_path('Listeners/Score'),
        ];

        foreach ($paths as $listenerPath) {
            if (!File::isDirectory($listenerPath)) {
                continue;
            }
            foreach (File::allFiles($listenerPath) as $file) {
                $contents = File::get($file->getPathname());
                if (preg_match_all("/(?:protected\s+)?(?:\\\$this->)?\\\$missionId\s*=\s*['\"]([^'\"]+)['\"]/", $contents, $matches)) {
                    foreach ($matches[1] as $id) {
                        $ids[] = $id;
                    }
                }
                if (preg_match_all("/(?:upgrade_mission_for_user|sync_mission_progress_for_user|award_mission_exp|get_mission_level_exp)\s*\(\s*[^,]+,\s*['\"]([^'\"]+)['\"]/", $contents, $matches)) {
                    foreach ($matches[1] as $id) {
                        $ids[] = $id;
                    }
                }
                if (preg_match_all("/['\"](submit-first-answer|submit-answer|like-on-answer|like-on-question|like-on-comment)['\"]/", $contents, $matches)) {
                    foreach ($matches[1] as $id) {
                        $ids[] = $id;
                    }
                }
            }
        }

        $this->wiredMissionIds = array_values(array_unique($ids));

        return $this->wiredMissionIds;
    }

    public function isMissionWired(string $missionId): bool
    {
        return in_array($missionId, $this->getWiredMissionIds(), true);
    }

    public function transformMission(Mission $mission, bool $detailed = false): array
    {
        $levels = $this->normalizeLevels($mission->levels);
        $wired = $this->isMissionWired($mission->id);

        $participantsCount = UserMission::where('mission_id', $mission->id)->count();
        $completedCount = UserMission::where('mission_id', $mission->id)
            ->whereNotNull('completed_at')->count();
        $inProgressCount = $participantsCount - $completedCount;

        $data = [
            'id' => $mission->id,
            'title' => $mission->title,
            'category_id' => $mission->category_id,
            'category' => $mission->category ? [
                'id' => $mission->category->id,
                'title' => $mission->category->title,
                'slug' => $mission->category->slug,
            ] : null,
            'icon' => $mission->icon,
            'description' => $mission->description,
            'is_active' => (bool) ($mission->is_active ?? true),
            'levels' => $levels,
            'levels_count' => count($levels),
            'max_goal' => $this->getMaxGoal($levels),
            'total_exp' => $this->getTotalExp($levels),
            'expired_at' => $mission->expired_at,
            'is_wired' => $wired,
            'listener_status' => $wired ? 'wired' : 'unwired',
            'participants_count' => $participantsCount,
            'completed_count' => $completedCount,
            'in_progress_count' => $inProgressCount,
            'completion_rate' => $participantsCount > 0
                ? round(($completedCount / $participantsCount) * 100, 1) : 0,
            'created_at' => $mission->created_at,
            'updated_at' => $mission->updated_at,
        ];

        if ($detailed) {
            $data['recent_participants'] = $this->getMissionParticipants($mission->id, 10);
        }

        return $data;
    }

    public function getMissionParticipants(string $missionId, int $limit = 20, ?string $status = null): array
    {
        $query = UserMission::with(['user:id,username,first_name,last_name,profile_pic'])
            ->where('mission_id', $missionId);

        if ($status === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($status === 'in_progress') {
            $query->whereNull('completed_at');
        }

        return $query->latest('updated_at')->limit($limit)->get()->map(function ($um) use ($missionId) {
            return $this->transformParticipant($um, Mission::find($missionId));
        })->toArray();
    }

    public function getAllParticipants(array $filters = []): array
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 30), 1), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $status = $filters['status'] ?? null;
        $missionId = $filters['mission_id'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        $query = UserMission::with([
            'user:id,username,first_name,last_name,profile_pic',
            'mission:id,title,icon,category_id',
            'mission.category:id,title',
        ]);

        if ($missionId) {
            $query->where('mission_id', $missionId);
        }

        if ($status === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($status === 'in_progress') {
            $query->whereNull('completed_at');
        }

        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'ilike', "%{$search}%")
                    ->orWhere('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%");
            });
        }

        $paginator = $query->latest('updated_at')->paginate($perPage, ['*'], 'page', $page);

        $missionsCache = [];
        $items = $paginator->getCollection()->map(function ($um) use (&$missionsCache) {
            $mission = $um->mission;
            if ($mission && !isset($missionsCache[$mission->id])) {
                $missionsCache[$mission->id] = $mission;
            }
            $row = $this->transformParticipant($um, $missionsCache[$um->mission_id] ?? $mission);
            $row['mission'] = $mission ? [
                'id' => $mission->id,
                'title' => $mission->title,
                'icon' => $mission->icon,
                'category' => $mission->category?->title,
            ] : null;

            return $row;
        })->values()->toArray();

        $base = UserMission::query();
        if ($missionId) {
            $base->where('mission_id', $missionId);
        }

        $total = (clone $base)->count();
        $completed = (clone $base)->whereNotNull('completed_at')->count();

        return [
            'participants' => $items,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $total - $completed,
                'unique_users' => (clone $base)->distinct('user_id')->count('user_id'),
            ],
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    public function syncApprovedReportMissions(): array
    {
        $synced = 0;
        $users = \App\Models\User::whereHas('reports', fn ($q) => $q->where('status', true))
            ->with(['reports' => fn ($q) => $q->where('status', true)->latest()])
            ->get();

        foreach ($users as $user) {
            $report = $user->reports->first();
            if (!$report) {
                continue;
            }

            event(new \App\Events\Mission\CommunityActivityEvent($user, 'report', $report));

            // One-time catch-up for report-approved mission if never started
            $hasReportApproved = UserMission::where([
                ['user_id', $user->id],
                ['mission_id', 'report-approved'],
            ])->exists();

            if (!$hasReportApproved) {
                event(new \App\Events\Score\Report\ReportApprovedForScores($user, $report));
            }

            $synced++;
        }

        return ['synced_users' => $synced];
    }

    protected function transformParticipant($um, $mission = null): array
    {
        $mission = $mission ?: Mission::find($um->mission_id);
        $currentLevel = $mission ? get_mission_current_level($mission, $um) : null;
        $maxGoal = $mission ? $this->getMaxGoal($this->normalizeLevels($mission->levels)) : 1;
        $progressPercent = $maxGoal > 0 ? min(100, round(($um->progress / $maxGoal) * 100, 1)) : 0;

        return [
            'id' => $um->id,
            'user_id' => $um->user_id,
            'mission_id' => $um->mission_id,
            'username' => $um->user?->username,
            'name' => trim(($um->user?->first_name ?? '') . ' ' . ($um->user?->last_name ?? '')),
            'profile_pic' => $um->user?->profile_pic,
            'progress' => $um->progress,
            'progress_percent' => $progressPercent,
            'current_level' => $currentLevel?->level ?? 1,
            'completed_at' => $um->completed_at,
            'status' => $um->completed_at ? 'completed' : 'in_progress',
            'updated_at' => $um->updated_at,
        ];
    }

    public function normalizeLevels(mixed $levels): array
    {
        if (is_string($levels)) {
            $levels = json_decode($levels, true) ?? [];
        }
        if (!is_array($levels)) {
            return [];
        }

        $normalized = [];
        foreach ($levels as $levelNumber => $levelData) {
            if (!is_array($levelData)) {
                continue;
            }
            $normalized[(int) $levelNumber] = [
                'goal' => (int) ($levelData['goal'] ?? 1),
                'exp' => (int) ($levelData['exp'] ?? 0),
                'requirements' => $levelData['requirements'] ?? [],
            ];
        }
        ksort($normalized);

        return $normalized;
    }

    public function prepareLevelsForStorage(array $levels): array
    {
        $prepared = [];
        foreach ($levels as $index => $level) {
            if (!is_array($level)) {
                continue;
            }
            $levelNumber = is_numeric($index) ? ((int) $index + 1) : ((int) ($level['level'] ?? $index));
            if ($levelNumber < 1) {
                $levelNumber = count($prepared) + 1;
            }
            $prepared[$levelNumber] = [
                'goal' => (int) ($level['goal'] ?? 1),
                'exp' => (int) ($level['exp'] ?? 0),
                'requirements' => $level['requirements'] ?? [],
            ];
        }
        if ($prepared === []) {
            $prepared[1] = ['goal' => 1, 'exp' => 0, 'requirements' => []];
        }
        ksort($prepared);

        return $prepared;
    }

    public function generateMissionId(string $title): string
    {
        $base = Str::slug($title) ?: 'mission';
        $candidate = $base;
        $counter = 1;
        while (Mission::where('id', $candidate)->exists()) {
            $candidate = $base . '-' . $counter++;
        }

        return $candidate;
    }

    public function getStats(?int $days = 30): array
    {
        $wiredIds = $this->getWiredMissionIds();
        $allMissionIds = Mission::pluck('id')->toArray();
        $since = now()->subDays($days);

        return [
            'summary' => [
                'total_missions' => Mission::count(),
                'active_missions' => Mission::where('is_active', true)->count(),
                'inactive_missions' => Mission::where('is_active', false)->count(),
                'total_categories' => MissionCategory::count(),
                'wired_missions' => count(array_intersect($allMissionIds, $wiredIds)),
                'unwired_missions' => count(array_diff($allMissionIds, $wiredIds)),
                'missing_daily_login' => !Mission::where('id', 'daily-login')->exists(),
                'total_participants' => UserMission::count(),
                'total_completions' => UserMission::whereNotNull('completed_at')->count(),
                'active_participants' => UserMission::whereNull('completed_at')->count(),
                'completions_period' => UserMission::whereNotNull('completed_at')
                    ->where('completed_at', '>=', $since)->count(),
                'new_participants_period' => UserMission::where('created_at', '>=', $since)->count(),
                'total_scores_awarded' => (int) Score::where('score', '>', 0)->sum('score'),
                'total_score_records' => Score::count(),
                'avg_completion_rate' => $this->getAverageCompletionRate(),
            ],
            'charts' => [
                'daily_activity' => $this->getDailyActivityChart($days),
                'category_distribution' => $this->getCategoryDistributionChart(),
                'status_distribution' => $this->getStatusDistributionChart(),
                'top_by_participants' => $this->getTopMissionsChart('participants', 10),
                'top_by_completions' => $this->getTopMissionsChart('completions', 10),
                'top_by_completion_rate' => $this->getTopMissionsChart('completion_rate', 10),
                'category_comparison' => $this->getCategoryComparisonChart(),
                'listener_status' => $this->getListenerStatusChart(),
            ],
            'rankings' => [
                'most_popular' => $this->getTopMissionsList('participants', 5),
                'most_completed' => $this->getTopMissionsList('completions', 5),
                'highest_rate' => $this->getTopMissionsList('completion_rate', 5),
                'least_engaged' => $this->getTopMissionsList('participants', 5, 'asc'),
            ],
            'recent_completions' => $this->getRecentCompletions(10),
            'score_settings' => [
                'conversion_rate' => app(\App\Services\ScoresService::class)->getConversionRate(),
                'min_scores' => app(\App\Services\ScoresService::class)->getMinScores(),
            ],
            'period_days' => $days,
        ];
    }

    protected function getAverageCompletionRate(): float
    {
        // has('users') == participants_count > 0; PostgreSQL cannot reference a
        // SELECT subquery alias in HAVING, so use relationship existence instead.
        $missions = Mission::withCount([
            'users as participants_count',
            'users as completed_count' => fn ($q) => $q->whereNotNull('mission_user.completed_at'),
        ])->has('users')->get();

        if ($missions->isEmpty()) {
            return 0;
        }

        $total = $missions->sum(fn ($m) => ($m->completed_count / $m->participants_count) * 100);

        return round($total / $missions->count(), 1);
    }

    protected function getDailyActivityChart(int $days): array
    {
        $since = now()->subDays($days)->startOfDay();
        $labels = [];
        $participantsData = [];
        $completionsData = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $nextDate = $date->copy()->endOfDay();
            $labels[] = $date->format('m/d');

            $participantsData[] = UserMission::whereBetween('created_at', [$date, $nextDate])->count();
            $completionsData[] = UserMission::whereNotNull('completed_at')
                ->whereBetween('completed_at', [$date, $nextDate])->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'شرکت‌کنندگان جدید', 'data' => $participantsData, 'lineColor' => 'rgba(59, 130, 246, 1)', 'fillColor' => 'rgba(59, 130, 246, 0.1)'],
                ['label' => 'تکمیل‌ها', 'data' => $completionsData, 'lineColor' => 'rgba(16, 185, 129, 1)', 'fillColor' => 'rgba(16, 185, 129, 0.1)'],
            ],
        ];
    }

    protected function getCategoryDistributionChart(): array
    {
        $categories = MissionCategory::withCount('missions')->get();

        return [
            'labels' => $categories->pluck('title')->toArray(),
            'data' => $categories->pluck('missions_count')->toArray(),
            'colors' => ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899'],
        ];
    }

    protected function getStatusDistributionChart(): array
    {
        $completed = UserMission::whereNotNull('completed_at')->count();
        $inProgress = UserMission::whereNull('completed_at')->count();

        return [
            'labels' => ['تکمیل‌شده', 'در حال انجام'],
            'data' => [$completed, $inProgress],
            'colors' => ['#10b981', '#f59e0b'],
        ];
    }

    protected function getTopMissionsChart(string $metric, int $limit): array
    {
        $missions = Mission::withCount([
            'users as participants_count',
            'users as completed_count' => fn ($q) => $q->whereNotNull('mission_user.completed_at'),
        ])->get();

        $sorted = $missions->map(function ($m) use ($metric) {
            $rate = $m->participants_count > 0
                ? round(($m->completed_count / $m->participants_count) * 100, 1) : 0;

            return [
                'title' => $m->title,
                'icon' => $m->icon,
                'value' => match ($metric) {
                    'completions' => $m->completed_count,
                    'completion_rate' => $rate,
                    default => $m->participants_count,
                },
            ];
        })->sortByDesc('value')->take($limit)->values();

        return [
            'labels' => $sorted->pluck('title')->toArray(),
            'data' => $sorted->pluck('value')->toArray(),
            'icons' => $sorted->pluck('icon')->toArray(),
        ];
    }

    protected function getCategoryComparisonChart(): array
    {
        $categories = MissionCategory::all();
        $labels = [];
        $participants = [];
        $completions = [];

        foreach ($categories as $cat) {
            $missionIds = Mission::where('category_id', $cat->id)->pluck('id');
            $labels[] = $cat->title;
            $participants[] = UserMission::whereIn('mission_id', $missionIds)->count();
            $completions[] = UserMission::whereIn('mission_id', $missionIds)
                ->whereNotNull('completed_at')->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'شرکت‌کنندگان', 'data' => $participants, 'lineColor' => 'rgba(59, 130, 246, 0.85)'],
                ['label' => 'تکمیل‌ها', 'data' => $completions, 'lineColor' => 'rgba(16, 185, 129, 0.85)'],
            ],
        ];
    }

    protected function getListenerStatusChart(): array
    {
        $wired = count(array_intersect(Mission::pluck('id')->toArray(), $this->getWiredMissionIds()));
        $unwired = Mission::count() - $wired;

        return [
            'labels' => ['متصل به Listener', 'بدون Listener'],
            'data' => [$wired, max(0, $unwired)],
            'colors' => ['#10b981', '#f97316'],
        ];
    }

    protected function getTopMissionsList(string $metric, int $limit, string $direction = 'desc'): array
    {
        $missions = Mission::with('category')->withCount([
            'users as participants_count',
            'users as completed_count' => fn ($q) => $q->whereNotNull('mission_user.completed_at'),
        ])->get();

        $mapped = $missions->map(function ($m) {
            return array_merge($this->transformMission($m), [
                'participants_count' => $m->participants_count,
                'completed_count' => $m->completed_count,
            ]);
        });

        $sorted = $direction === 'asc'
            ? $mapped->sortBy('participants_count')
            : $mapped->sortByDesc(match ($metric) {
                'completions' => 'completed_count',
                'completion_rate' => 'completion_rate',
                default => 'participants_count',
            });

        return $sorted->take($limit)->values()->toArray();
    }

    protected function getRecentCompletions(int $limit): array
    {
        return UserMission::with(['user:id,username,first_name,last_name,profile_pic', 'mission:id,title,icon'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($um) => [
                'user' => [
                    'id' => $um->user?->id,
                    'username' => $um->user?->username,
                    'name' => trim(($um->user?->first_name ?? '') . ' ' . ($um->user?->last_name ?? '')),
                    'profile_pic' => $um->user?->profile_pic,
                ],
                'mission' => [
                    'id' => $um->mission?->id,
                    'title' => $um->mission?->title,
                    'icon' => $um->mission?->icon,
                ],
                'completed_at' => $um->completed_at,
            ])->toArray();
    }

    protected function getMaxGoal(array $levels): int
    {
        return $levels === [] ? 0 : max(array_column($levels, 'goal'));
    }

    protected function getTotalExp(array $levels): int
    {
        return array_sum(array_column($levels, 'exp'));
    }
}
