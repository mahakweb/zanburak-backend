<?php



namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LikeRequest;
use App\Http\Requests\UnlikeRequest;
use App\Models\Answer;
use App\Models\Question;

class LikeController extends Controller
{


    public function store(LikeRequest $request){

        $auth = Auth::check();
        if (!$auth) {
            return Response::json(['status' => 0, 'msg' => 'login first !']);
        }
        else{

            auth()->user()->toggleLike($request->likeable());
            $count = $request->likeable()->likes()->count();
            $like = auth()->user()->hasLiked($request->likeable());
            return Response::json(['status' => 1, 'like' => $like, 'count' => $count, 'msg' => 'like successfully']);

        }

    }

    public function discussLike(LikeRequest $request){

        $auth = Auth::check();
        if (!$auth) {
            return Response::json(['status' => 0, 'type' => 'warning', 'msg' => 'برای لایک یا دیس لایک ، لطفا ابتدا وارد سایت شوید.']);
        }

        if(auth()->user()->currentScore() < 4000){
            return Response::json(['status' => 0, 'type' => 'warning', 'msg' => 'حداقل تجربه کاربری برای لایک یا دیس‌لایک باید 4000 باشد.']);
        }

        if(auth()->user()->id == $request->likeable()->user_id){
            return Response::json(['status' => 0, 'type' => 'warning', 'msg' => ' شما نمی‌توانید مطلب خودتان را لایک یا دیس‌لایک کنید.']);
        }



        if(auth()->user()->hasLiked($request->likeable())){

        }elseif(auth()->user()->hasDisliked($request->likeable())){
            auth()->user()->unDislike($request->likeable());
        }else{
            auth()->user()->like($request->likeable());
        }


        $count = $request->likeable()->likes()->where('type', 'like')->count() - $request->likeable()->likes()->where('type', 'dislike')->count();
        return Response::json(['status' => 1, 'count' => $count, 'msg' => 'like successfully']);


    }


    public function discussDislike(LikeRequest $request){

        $auth = Auth::check();
        if (!$auth) {
            return Response::json(['status' => 0, 'type' => 'warning', 'msg' => 'برای لایک یا دیس لایک ، لطفا ابتدا وارد سایت شوید.']);
        }

        if(auth()->user()->currentScore() < 4000){
            return Response::json(['status' => 0, 'type' => 'warning', 'msg' => 'حداقل تجربه کاربری برای لایک یا دیس‌لایک باید 4000 باشد.']);
        }

        if(auth()->user()->id == $request->likeable()->user_id){
            return Response::json(['status' => 0, 'type' => 'warning', 'msg' => ' شما نمی‌توانید مطلب خودتان را لایک یا دیس‌لایک کنید.']);
        }



        if(auth()->user()->hasDisliked($request->likeable())){

        }elseif(auth()->user()->hasLiked($request->likeable())){
            auth()->user()->unlike($request->likeable());
        }else{
            auth()->user()->dislike($request->likeable());
        }


        $count = $request->likeable()->likes()->where('type', 'like')->count() - $request->likeable()->likes()->where('type', 'dislike')->count();
        return Response::json(['status' => 1, 'count' => $count, 'msg' => 'like successfully']);

    }


}








// namespace App\Http\Controllers\Home;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Response;
// use Illuminate\Support\Facades\Auth;

// class LikeController extends Controller
// {
//     public function store(Request $request){

//         $auth = Auth::check();
//         if (!$auth) {
//             return Response::json(['status' => 0, 'msg' => 'login first !']);
//         }
//         else{
//             $className = $request->likeable_type;
//             $model = new $className;
//             if($obj = $model::find($request->likeable_id)){
//                 auth()->user()->toggleLike($obj);
//                 $count = $obj->likers()->count();
//                 $like = auth()->user()->hasLiked($obj);
//                 return Response::json(['status' => 1, 'like' => $like, 'count' => $count, 'msg' => 'like successfully']);
//             }

//         }

//     }
// }
