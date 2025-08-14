<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{

    public function setRate(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['Message' => 'Error!  login first'], 401);
        }

        $validData = Validator::make($request->all(), [
            'rateable_id' => ['required'],
            'rateable_type' => ['required'],
            'rating' => ['required'],
            'comment' => ['nullable'],
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error!', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $rateableType = ucfirst($request->input('rateable_type'));
            $rateableId = $request->input('rateable_id');

            if (!$rateableType || !$rateableId) {
                return response()->json(['message' => 'Invalid rateable type or ID provided'], 400);
            }

            $className = "App\\Models\\" . $rateableType;
            if (!class_exists($className)) {
                return response()->json(['message' => 'Invalid rateable type provided'], 400);
            }

            $modelInstance = $className::find($rateableId);
            if (!$modelInstance) {
                return response()->json(['message' => 'Rateable object not found'], 404);
            }

            if($className == "App\Models\Course"){
                $userHasCourse = $user?->hasCourse($modelInstance) ?? false;
                if(!$userHasCourse){
                    return response()->json(['message' => 'You dont have this course'], 403);
                }
            }

            $check = $modelInstance->ratings()->where('user_id', $user->id)->first();
            if (!$check) {
                $insertData = $modelInstance->ratings()->create([
                    'user_id' => $user->id,
                    'rating' => $request['rating'],
                    'comment' => $request['comment'],
                ]);

                $ratings = [
                    'countOfOne' => $modelInstance->sumOfRateNumber(1),
                    'countOfTwo' => $modelInstance->sumOfRateNumber(2),
                    'countOfThree' => $modelInstance->sumOfRateNumber(3),
                    'countOfFour' => $modelInstance->sumOfRateNumber(4),
                    'countOfFive' => $modelInstance->sumOfRateNumber(5),
                    'countOfAll' => $modelInstance->sumOfAllRate(),
                    'sumOfAll' => $modelInstance->sumRating(),
                    'averageRating' => $modelInstance->averageRating(),
                    'currentUserRate' => [
                        'rating' => $request['rating'],
                        'comment' => $request['comment']
                    ],
                ];

                return response()->json(['message' => 'Success, your rate has been successfully saved', 'ratings' => $ratings], 200);
                
            } else {
                return response()->json(['message' => 'you already rated !'], 409);
            }

        }
    }

}
