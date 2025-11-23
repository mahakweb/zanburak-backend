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
            $type = $request->input('reportable_type');
            $className = "App\\Models\\" . ucfirst($type);
            
            if (!class_exists($className)) {
                return response()->json(['message' => 'Error! Invalid reportable type'], 422);
            }
            
            $model = new $className;

            if($obj = $model::find($request->input('reportable_id'))){
                // Store full class name for proper polymorphic relation
                $report = $user->reports()->create([
                    'report' => $request->input('report'),
                    'reportable_id' => $request->input('reportable_id'),
                    'reportable_type' => $className, // Store full class name
                ]);
                return response()->json(['Message' => 'Success'], 200);
            } else {
                return response()->json(['message' => 'Error! Content not found'], 404);
            }
        }
        
            
            

        
    }
}
