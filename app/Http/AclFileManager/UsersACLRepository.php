<?php

namespace App\Http\AclFileManager;

use Alexusmai\LaravelFileManager\Services\ACLService\ACLRepository;
use Illuminate\Support\Facades\DB;

class UsersACLRepository implements ACLRepository
{
    /**
     * Get user ID
     *
     * @return mixed
     */
    public function getUserID()
    {
        return auth()->user()->id;
    }

    /**
     * Get ACL rules list for user
     *
     * @return array
     */
    public function getRules(): array
    {

        if (auth()->user()->is_superuser) {
            // return [
            //     ['disk' => 'course', 'path' => '*', 'access' => 2],
            //     ['disk' => 'public', 'path' => '*', 'access' => 2],
            //     ['disk' => 'milad', 'path' => '*', 'access' => 2],
            //     ['disk' => 'all-ftp', 'path' => '*', 'access' => 2],
            // ];
            $result = [];

            foreach(config('filesystems.disks') as $item => $value){
                array_push($result, ['disk' => $item, 'path' => '*', 'access' => 2]);
            }

            return $result;
                
        }

        return DB::table('acl_rules')
            ->where('user_id', $this->getUserID())
            ->get(['disk', 'path', 'access'])
            ->map(function ($item) {
                return get_object_vars($item);
            })
            ->all();
        
        // return [
        //     ['disk' => 'disk-name', 'path' => '/', 'access' => 1],                                  // main folder - read
        //     ['disk' => 'disk-name', 'path' => 'users', 'access' => 1],                              // only read
        //     ['disk' => 'disk-name', 'path' => 'users/'. \Auth::user()->name, 'access' => 1],        // only read
        //     ['disk' => 'disk-name', 'path' => 'users/'. \Auth::user()->name .'/*', 'access' => 2],  // read and write
        // ];
    }
}