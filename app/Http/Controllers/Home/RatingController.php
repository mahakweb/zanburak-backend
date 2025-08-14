<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    public function store(Request $request){

        $validData = Validator::make($request->all(),[
            'rateable_id' => ['required'],
            'rateable_type' => ['required'],
            'rating' => ['required'],
        ]);

        if (!$validData->passes()) {
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        } else {
            $check = auth()->user()->ratings()->where('rateable_id',$request['rateable_id'])->where('rateable_type', $request['rateable_type'])->first();
            if(!$check){
                $insertData = auth()->user()->ratings()->create([
                    'rateable_id' => $request['rateable_id'],
                    'rateable_type' => $request['rateable_type'],
                    'rating' => $request['rating'],
                    'comment' => $request['comment'],
                ]);

                if ($insertData) {
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully saved !']);
                }
            }else{
                return response()->json(['status' => 2, 'msg' => 'you already rated !']);
            }

        }



    }
}
