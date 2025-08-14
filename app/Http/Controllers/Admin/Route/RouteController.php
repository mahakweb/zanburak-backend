<?php

namespace App\Http\Controllers\Admin\Route;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use App\Models\PermissionRoute;

class RouteController extends Controller
{
    public function index(){

        $routes = Route::getRoutes();
        return view('admin.apps.user-management.routes.list', compact('routes'));
    }

    public function syncPermission(Request $request){
        $validator = Validator::make($request->all(), [
            'permission' => ['nullable', 'array'],
            'route_name' => ['required']
        ]);

        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{

            $validData = $validator->validated();

            PermissionRoute::where('route_name', $validData['route_name'])->delete();

            if(!empty($validData['permission'])){
                foreach($validData['permission'] as $permission){
                    PermissionRoute::create([
                        'route_name' => $validData['route_name'],
                        'permission_id' => $permission
                    ]);
                }
            }

            return response()->json(['status' => 1, 'msg' => 'successfully sunc permission for this route']);
        }



    }
}
