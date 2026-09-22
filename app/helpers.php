<?php

use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


if (!function_exists("parse_mission_levels")) {
    /**
     * Normalize mission levels JSON/array to a sorted associative array.
     */
    function parse_mission_levels(mixed $levels): array
    {
        if (is_string($levels)) {
            $levels = json_decode($levels, true);
        }

        if (!is_array($levels) || $levels === []) {
            return [];
        }

        $normalized = [];
        foreach ($levels as $key => $level) {
            if (!is_array($level)) {
                continue;
            }
            $normalized[(int) $key] = $level;
        }
        ksort($normalized);

        return $normalized;
    }
}

if (!function_exists("get_mission_current_level")) {
    function get_mission_current_level(Mission $mission, UserMission|null $userMission)
    {
        $levels = parse_mission_levels($mission->levels);
        if (!$userMission) {
            return (object) [
                "level" => 0,
                "exp" => 0,
                "goal" => 0,
                "requirements" => []
            ];
        }

        $lastLevel = (object) [
            "level" => 0,
            "exp" => 0,
            "goal" => 0,
            "requirements" => []
        ];
        foreach ($levels as $key => $level) {
            if ($userMission->progress < ($level['goal'] ?? 0)) {
                break;
            } else {
                $lastLevel = (object) [
                    "level" => $key,
                    "exp" => $level['exp'] ?? 0,
                    "goal" => $level['goal'] ?? 0,
                    "requirements" => $level['requirements'] ?? []
                ];
            }
        }

        return $lastLevel;
    }
}

if (!function_exists("get_mission_previous_level")) {
    function get_mission_previous_level(Mission $mission, UserMission|null $userMission)
    {
        $levels = parse_mission_levels($mission->levels);
        $previousLevel = (object) [
            "level" => 0,
            "exp" => 0,
            "goal" => 0,
            "requirements" => []
        ];

        if ($userMission) {
            $currentLevel = get_mission_current_level($mission, $userMission);
            if ($currentLevel->level > 0) {
                $previousLevelIndex = $currentLevel->level - 1;
                if (isset($levels[$previousLevelIndex])) {
                    $previousLevel = (object) [
                        "level" => $previousLevelIndex,
                        "exp" => $levels[$previousLevelIndex]['exp'] ?? 0,
                        "goal" => $levels[$previousLevelIndex]['goal'] ?? 0,
                        "requirements" => $levels[$previousLevelIndex]['requirements'] ?? []
                    ];
                }
            }
        }

        return $previousLevel;
    }
}

if (!function_exists("get_mission_next_level")) {
    function get_mission_next_level(Mission $mission, UserMission|null $userMission)
    {
        $levels = parse_mission_levels($mission->levels);
        if ($levels === []) {
            return (object) [
                "level" => 0,
                "exp" => 0,
                "goal" => 0,
                "requirements" => []
            ];
        }

        if (!$userMission) {
            $firstKey = array_key_first($levels);
            $first = $levels[$firstKey];

            return (object) [
                "level" => $firstKey,
                "exp" => $first['exp'] ?? 0,
                "goal" => $first['goal'] ?? 0,
                "requirements" => $first['requirements'] ?? []
            ];
        }

        foreach ($levels as $key => $level) {
            if ($userMission->progress < ($level['goal'] ?? 0)) {
                return (object) [
                    "level" => $key,
                    "exp" => $level['exp'] ?? 0,
                    "goal" => $level['goal'] ?? 0,
                    "requirements" => $level['requirements'] ?? []
                ];
            }
        }

        return (object) [
            "level" => 0,
            "exp" => 0,
            "goal" => 0,
            "requirements" => []
        ];
    }
}

if (!function_exists("check_requirements")) {
    function check_requirements($requirements, $userId)
    {

        if (is_string($requirements)) {
            $requirements = json_decode($requirements, true);
        }

        if (!is_array($requirements) || empty($requirements)) {
            return true; // اگر requirements خالی باشد، نیازی به بررسی نیست
        }

        foreach ($requirements as $key => $requirement) {
            if (!is_array($requirement) || !isset($requirement['id']) || !isset($requirement['level'])) {
                \Log::warning('Invalid requirement structure:', ['requirement' => $requirement]);
                return false;
            }

            $userMission = UserMission::where('user_id', $userId)
                ->where('mission_id', $requirement['id'])
                ->first();

            if (!$userMission || get_mission_current_level(Mission::find($requirement['id']), $userMission)->level < $requirement['level']) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists("get_mission_level_exp")) {
    /**
     * خواندن امتیاز (exp) یک سطح ماموریت از دیتابیس
     */
    function get_mission_level_exp(string $missionId, int $level = 1): int
    {
        $mission = Mission::find($missionId);
        if (!$mission || !($mission->is_active ?? true)) {
            return 0;
        }

        $levels = parse_mission_levels($mission->levels);

        if (!isset($levels[$level])) {
            return 0;
        }

        return max(0, (int) ($levels[$level]['exp'] ?? 0));
    }
}

if (!function_exists("award_mission_exp")) {
    /**
     * اعطای امتیاز مستقیم از levels ماموریت (بدون نیاز به level-up).
     * برای رویدادهای تکراری مثل لایک، خرید، ورود روزانه.
     */
    function award_mission_exp($user, string $missionId, ?string $description = null, int $level = 1): ?\App\Models\Score
    {
        $mission = Mission::find($missionId);
        if (!$mission || !($mission->is_active ?? true)) {
            return null;
        }

        $exp = get_mission_level_exp($missionId, $level);
        if ($exp <= 0) {
            return null;
        }

        $userModel = $user instanceof User ? $user : User::find($user);
        if (!$userModel) {
            return null;
        }

        $finalDescription = $description ?? ("بابت ماموریت {$mission->title}");

        return $userModel->scores()->create([
            'description' => $finalDescription,
            'score' => $exp,
        ]);
    }
}

if (!function_exists("elapsed_hours")) {
    function elapsed_hours(mixed $from, mixed $to): float
    {
        if (!$from || !$to) {
            return INF;
        }

        $start = $from instanceof \DateTimeInterface ? $from->getTimestamp() : strtotime((string) $from);
        $end = $to instanceof \DateTimeInterface ? $to->getTimestamp() : strtotime((string) $to);

        if ($start === false || $end === false) {
            return INF;
        }

        return abs($end - $start) / 3600;
    }
}

if (!function_exists("upgrade_mission_for_user")) {
    /**
     * Advance a mission by a fixed increment (one user action = +1).
     */
    function upgrade_mission_for_user($userId, $missionId, $defaultProgressValue = 1, $defaultIncrementValue = 1, bool $awardScore = true)
    {
        $existing = UserMission::where([
            ['mission_id', $missionId],
            ['user_id', $userId],
        ])->first();

        if ($existing && $existing->completed_at) {
            return;
        }

        $target = $existing
            ? (int) $existing->progress + (int) $defaultIncrementValue
            : (int) $defaultProgressValue;

        apply_mission_progress($userId, $missionId, $target, $awardScore);
    }
}

if (!function_exists("sync_mission_progress_for_user")) {
    /**
     * Set mission progress to an absolute count (purchases, likes, invites, ...).
     * Progress never goes backwards, and EXP is granted for every level crossed.
     */
    function sync_mission_progress_for_user($userId, $missionId, int $progress, bool $awardScore = true): void
    {
        if ($progress <= 0) {
            return;
        }

        $existing = UserMission::where([
            ['mission_id', $missionId],
            ['user_id', $userId],
        ])->first();

        if ($existing && ($existing->completed_at || (int) $existing->progress >= $progress)) {
            return;
        }

        apply_mission_progress($userId, $missionId, $progress, $awardScore);
    }
}

if (!function_exists("apply_mission_progress")) {
    function apply_mission_progress($userId, $missionId, int $targetProgress, bool $awardScore = true): void
    {
        if ($targetProgress <= 0) {
            return;
        }

        $mission = Mission::where('id', $missionId)->first();
        if (!$mission || !($mission->is_active ?? true)) {
            return;
        }

        $levels = parse_mission_levels($mission->levels);
        if ($levels === []) {
            return;
        }

        $userMission = UserMission::where([
            ['mission_id', $missionId],
            ['user_id', $userId],
        ])->first();

        if ($userMission && $userMission->completed_at) {
            return;
        }

        $previousLevel = get_mission_current_level($mission, $userMission);

        if (!$userMission) {
            $firstLevel = reset($levels);
            $requirements = $firstLevel['requirements'] ?? [];
            if (is_array($requirements) && $requirements !== [] && !check_requirements($requirements, $userId)) {
                return;
            }

            $userMission = UserMission::create([
                'user_id' => $userId,
                'mission_id' => $missionId,
                'progress' => 0,
            ]);
        }

        foreach ($levels as $level) {
            $goal = (int) ($level['goal'] ?? 0);
            $requirements = $level['requirements'] ?? [];
            if ($userMission->progress < $goal && $targetProgress >= $goal && is_array($requirements) && $requirements !== []) {
                if (!check_requirements($requirements, $userId)) {
                    $targetProgress = max((int) $userMission->progress, $goal - 1);
                    break;
                }
            }
        }

        if ($targetProgress <= (int) $userMission->progress) {
            return;
        }

        $userMission->progress = $targetProgress;
        $userMission->save();

        $newCurrentLevel = get_mission_current_level($mission, $userMission);
        $newNextLevel = get_mission_next_level($mission, $userMission);

        if (!$userMission->completed_at && $newNextLevel->level == 0 && $newCurrentLevel->level > 0) {
            $userMission->completed_at = Carbon::now();
            $userMission->save();

            $user = User::find($userId);
            if ($user) {
                try {
                    $user->notify(new \App\Notifications\Achievement\MissionAchievedNotification($mission));
                } catch (\Throwable $e) {
                    Log::warning('Mission achievement notification failed: '.$e->getMessage(), [
                        'user_id' => $userId,
                        'mission_id' => $missionId,
                    ]);
                }
            }
        }

        if (!$awardScore || $newCurrentLevel->level <= $previousLevel->level) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        foreach ($levels as $key => $level) {
            $levelNumber = (int) $key;
            if ($levelNumber <= $previousLevel->level || $levelNumber > $newCurrentLevel->level) {
                continue;
            }

            $exp = (int) ($level['exp'] ?? 0);
            if ($exp <= 0) {
                continue;
            }

            $user->scores()->create([
                'description' => "بابت اتمام ماموریت {$mission->title} ، سطح {$levelNumber}",
                'score' => $exp,
            ]);
        }
    }
}

if (!function_exists("calculate_progress_percent")) {
    function calculate_progress_percent($progress, $levels)
    {
        $levels = parse_mission_levels($levels);

        if ($levels === []) {
            return 0;
        }

        $previousLevelGoal = 0;
        $currentLevelGoal = 0;

        foreach ($levels as $level) {
            $currentLevelGoal = (int) ($level['goal'] ?? 0);

            if ($progress <= $currentLevelGoal) {
                break;
            }

            $previousLevelGoal = $currentLevelGoal;
        }

        if ($currentLevelGoal > $previousLevelGoal) {
            $totalGoalRange = $currentLevelGoal - $previousLevelGoal;
            if ($totalGoalRange > 0) {
                return min(100, (($progress - $previousLevelGoal) / $totalGoalRange) * 100);
            }
        }

        return 0;
    }
}

if (!function_exists('frontendUrl')) {
    /**
     * Helper function برای ساخت URL فرانت‌اند
     * 
     * این تابع از FRONT_APP_URL در .env استفاده می‌کند
     * که در config/app.php به عنوان frontend_url تعریف شده است.
     *
     * @param string $path
     * @return string
     */
    function frontendUrl(string $path = ''): string
    {
        $baseUrl = rtrim(config('app.frontend_url', 'https://zanburak.ir'), '/');
        $path = ltrim($path, '/');
        return $path ? "{$baseUrl}/{$path}" : $baseUrl;
    }
}