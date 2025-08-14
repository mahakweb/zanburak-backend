<?php

namespace App\Http\Middleware;

use App\Models\PermissionRoute;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $currentRouteName = Route::current()->getName();

        if(auth()->user()->isSuperUser() || $this->hasAllowed($currentRouteName)){
            return $next($request);
        }

        abort('403' , 'You are not allowed to access this page');
    }

    protected function hasAllowed($routeName){

        $routePermissions = PermissionRoute::where('route_name', $routeName)->get();

        if(!count($routePermissions)) return true;

        foreach($routePermissions as $perm){
            $permission = \App\Models\Permission::find($perm->permission_id);
            if(auth()->user()->hasPermission($permission)){
                return true;
            }
        }

        return false;
    }
}
