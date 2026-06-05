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
        // Allow guests to pass; enforcement is only for authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $currentRouteName = Route::current()->getName();

        if(auth()->user()->isSuperUser() || $this->hasAllowed($currentRouteName)){
            return $next($request);
        }

        abort('403' , 'You are not allowed to access this page');
    }

    protected function hasAllowed($routeName){

        $routePermissions = PermissionRoute::with('permission')
            ->where('route_name', $routeName)
            ->get();

        if ($routePermissions->isEmpty()) {
            return true;
        }

        $user = auth()->user();

        foreach ($routePermissions as $perm) {
            if ($perm->permission && $user->hasPermissionName($perm->permission->name)) {
                return true;
            }
        }

        return false;
    }
}
