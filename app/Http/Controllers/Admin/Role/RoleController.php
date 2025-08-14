<?php

namespace App\Http\Controllers\Admin\Role;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();
        return view('admin.apps.user-management.roles.list', compact('roles'));
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
                'name' => ['required', 'regex:/^[a-zA-z\-]+$/', 'unique:roles,name'],
                'label' => ['required'],
            ]);
            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $role = Role::create([
                    'name' => $request['name'],
                    'label' => $request['label']
                ]);

                $request['permissions'] = array_unique($request['permissions']);

                $role->permissions()->attach($request['permissions']);

                return response()->json(['status' => 1, 'msg' => 'success']);

            }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        return view('admin.apps.user-management.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        
            $validData = Validator::make($request->all(), [
                'name' => ['required', 'regex:/^[a-zA-z\-]+$/', 'unique:roles,name,'.$role->name.',name'],
                'label' => ['required'],
            ]);
            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $role->update([
                    'name' => $request['name'],
                    'label' => $request['label']
                ]);

                $request['permissions'] = array_unique($request['permissions']);

                $role->permissions()->sync($request['permissions']);

                return response()->json(['status' => 1, 'msg' => 'success']);

            }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Role $role)
    {
        
            $delete = $role->delete();
            if($delete){
                return response()->json(['status' => 1, 'msg' => 'successfully']);
            }
        
    }


    public function detachUser(Request $request, Role $role){
        
            $detach = $role->users()->detach($request['user_id']);
            if($detach){
                return response()->json(['status' => 1, 'msg' => 'successfully']);
            }
        
    }
}
