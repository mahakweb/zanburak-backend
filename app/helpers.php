<?php

use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;
use Carbon\Carbon;


if (!function_exists("get_mission_current_level")) {
    function get_mission_current_level(Mission $mission, UserMission|null $userMission)
    {
        $levels = json_decode($mission->levels, true);
        ksort($levels);
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
            if ($userMission->progress < $level['goal']) {
                break;
            } else {
                $lastLevel = (object) [
                    "level" => $key,
                    "exp" => $level['exp'],
                    "goal" => $level['goal'],
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
        $levels = json_decode($mission->levels, true);
        ksort($levels);
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
                        "exp" => $levels[$previousLevelIndex]['exp'],
                        "goal" => $levels[$previousLevelIndex]['goal'],
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
        $levels = json_decode($mission->levels, true);
        ksort($levels);
        if (!$userMission) {
            return (object) [
                "level" => 1,
                "exp" => $levels[1]['exp'],
                "goal" => $levels[1]['goal'],
                "requirements" => $levels[1]['requirements'] ?? []
            ];
        }

        foreach ($levels as $key => $level) {
            if ($userMission->progress < $level['goal']) {
                return (object) [
                    "level" => $key,
                    "exp" => $level['exp'],
                    "goal" => $level['goal'],
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

        if (!is_array($requirements)) {
            dd('Invalid requirements format:', $requirements);
            return false;
        }

        foreach ($requirements as $key => $requirement) {
            if (!is_array($requirement) || !isset($requirement['id']) || !isset($requirement['level'])) {
                dd('Invalid requirement structure:', $requirement);
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

if (!function_exists("upgrade_mission_for_user")) {
    function upgrade_mission_for_user($userId, $missionId, $defaultProgressValue = 1, $defaultIncrementValue = 1)
    {
        $mission = Mission::where('id', $missionId)->first();
        $userMission = UserMission::where([
            ['mission_id', $missionId],
            ['user_id', $userId],
        ])->first();
        $currentLevel = get_mission_current_level($mission, $userMission);
        $nextLevel = get_mission_next_level($mission, $userMission);

        $levels = json_decode($mission->levels, true);
        ksort($levels);

        if (!$userMission) {
            $firstLevel = reset($levels);
            if (isset($firstLevel['requirements']) && is_array($firstLevel['requirements'])) {
                // dd($firstLevel['requirements']);
                if (!check_requirements($firstLevel['requirements'], $userId)) {
                    echo 'requirements for creating mission not met <br>';
                    return;
                }
            }
            $userMission = UserMission::create([
                'user_id' => $userId,
                "mission_id" => $missionId,
                "progress" => $defaultProgressValue,
            ]);
            echo 'created <br>';
        } else if (!$userMission->completed_at) {
            if ($userMission->progress < $currentLevel->goal || $nextLevel->level > 0) {
                if ($userMission->progress + $defaultIncrementValue >= $currentLevel->goal) {
                    if (isset($nextLevel->requirements) && is_array($nextLevel->requirements)) {
                        if (!check_requirements($nextLevel->requirements, $userId)) {
                            echo 'requirements for creating mission not met <br>';
                            return;
                        }
                    }
                }
                $userMission->increment('progress', $defaultIncrementValue);
                echo 'upgraded <br>';
            }
        }
        $newCurrentLevel = get_mission_current_level($mission, $userMission);
        $newNextLevel = get_mission_next_level($mission, $userMission);
        if (!$userMission->completed_at && $newNextLevel->level == 0) {
            $userMission->completed_at = Carbon::now();
            $userMission->save();
            echo 'completed <br>';
            
            // ارسال notification دستیابی به Mission
            $user = User::find($userId);
            if ($user) {
                $user->notify(new \App\Notifications\Achievement\MissionAchievedNotification($mission));
            }
        }
        if ($newCurrentLevel->level != $currentLevel->level && $newCurrentLevel->exp > 0) {
            User::find($userId)->scores()->create([
                "description" => "بابت اتمام ماموریت $mission->title ، سطح $newCurrentLevel->level",
                "score" => $newCurrentLevel->exp,
            ]);
            echo 'scored <br>';
        }
        // dd($currentLevel, $nextLevel, $newCurrentLevel, $newNextLevel, $userMission->progress);
    }
}

if (!function_exists("calculate_progress_percent")) {
    function calculate_progress_percent($progress, $levels)
    {
        if (is_string($levels)) {
            $levels = json_decode($levels, true);
        }

        if (!is_array($levels)) {
            dd('Invalid requirements format:', $levels);
            return false;
        }

        ksort($levels);

        $previousLevelGoal = 0;
        $currentLevelGoal = 0;
        $currentLevel = 0;

        foreach ($levels as $key => $level) {
            $currentLevel = $key;
            $currentLevelGoal = $level['goal'];

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
