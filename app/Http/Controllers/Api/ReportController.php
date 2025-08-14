<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function sendReport(Request $request){
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['Message' => 'Error!  login first'], 403);
        }


        $validData = Validator::make($request->all(), [
            'reportable_id' => ['required'],
            'reportable_type' => ['required'],
            'report' => ['required']
        ]);
        if( ! $validData->passes() ){
            return response()->json(['message' => 'Error!', 'errors' => $validData->errors()->toArray()], 422);
        }else {
            $className = "App\\Models\\" . ucfirst($request->input('reportable_type'));
            $model = new $className;

            if($obj = $model::find($request->input('reportable_id'))){
                $report = $user->reports()->create([
                    'report' => $request->input('report'),
                    'reportable_id' => $request->input('reportable_id'),
                    'reportable_type' => $request->input('reportable_type'),
                ]);
                return response()->json(['Message' => 'Success'], 200);
            }
        }
        
            
            

        
    }
}
