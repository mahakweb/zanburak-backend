<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'permission_id',
        'route_name'
    ];

    public function permission(){
        return $this->belongsTo(Permission::class);
    }

    public static function isUsedPermissionByRoute($id, $routeName){
        $permissions = PermissionRoute::where('route_name', $routeName)->pluck('permission_id')->toArray();
        return !! (in_array($id, $permissions));
    }


}
