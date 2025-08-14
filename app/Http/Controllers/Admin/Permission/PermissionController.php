<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.apps.user-management.permissions.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validData = Validator::make($request->all(), [
            'name' => ['required', 'regex:/^[a-zA-z\-]+$/', 'unique:permissions,name'],
            'label' => ['required'],
        ]);
        if(!$validData->passes()){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{
            $create = Permission::create([
                'name' => $request['name'],
                'label' => $request['label']
            ]);

            if($create){
                return response()->json(['status' => 1, 'msg' => 'successfully']);
            }

        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        return view('admin.apps.user-management.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {

        $validData = Validator::make($request->all(), [
            'name' => ['required', 'regex:/^[a-zA-z\-]+$/', 'unique:permissions,name,'.$permission->name.',name'],
            'label' => ['required'],
        ]);
        if(!$validData->passes()){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{
            $update = $permission->update([
                'name' => $request['name'],
                'label' => $request['label']
            ]);

            if($update){
                return response()->json(['status' => 1, 'msg' => 'successfully']);
            }

        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Permission $permission)
    {

        $delete = $permission->delete();
        if($delete){
            return response()->json(['status' => 1, 'msg' => 'successfully']);
        }

    }


    public function detachUser(Request $request, Permission $permission){

        $detach = $permission->users()->detach($request['user_id']);
        if($detach){
            return response()->json(['status' => 1, 'msg' => 'successfully']);
        }

    }
}
