<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class BookmarkController extends Controller
{
    public function store(Request $request){
        $auth = Auth::check();
        if (!$auth) {
            return Response::json(['status' => 0, 'msg' => 'login first !']);
        }
        else{
            $className = $request->bookmarkable_type;
            $model = new $className;
            if($obj = $model::find($request->bookmarkable_id)){
                auth()->user()->toggleBookmark($obj);
                $count = $obj->bookmarkersCount();
                $bookmark = auth()->user()->hasBookmarked($obj);
                return Response::json(['status' => 1, 'bookmark' => $bookmark, 'count' => $count, 'msg' => 'bookmark successfully']);
            }

        }
    }
}
