<?php

namespace App\Http\Controllers\Admin\Mission;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use Illuminate\Http\Request;

class MissionController extends Controller
{
    public function index(){
        return view("");
    }
    public function create(){
        $missions = Mission::all();
        $missionsData = [];
        foreach ($missions as $mission){
            $maxLevel = count(json_decode($mission->levels, true));
            $missionData = (object) [
                'id' => (string) $mission->id,
                'title' => $mission->title,
                'maxLevel' => $maxLevel
            ];
            $missionsData[] = $missionData;
        }

        return view("admin.apps.missions.create", ['missions' => $missionsData]);
    }
}
