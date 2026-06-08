<?php

namespace App\Http\Controllers\Api\Admin\Permission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{

    public function permissions(Request $request)
    {
        $query = Permission::query()->withCount(['roles', 'users']);

        // Search functionality
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('label', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        switch ($request->input('sort', 'newest')) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'most_users':
                $query->orderBy('users_count', 'desc');
                break;
            case 'most_roles':
                $query->orderBy('roles_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Pagination
        $perPage = (int) $request->input('perPage', 15);
        $permissions = $query->paginate($perPage);

        $data = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => $permission->label,
                'roles_count' => $permission->roles_count,
                'users_count' => $permission->users_count,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'permissions' => $data,
            'pagination' => [
                'total' => $permissions->total(),
                'per_page' => $permissions->perPage(),
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'prev_page' => $permissions->currentPage() > 1 ? $permissions->currentPage() - 1 : null,
                'next_page' => $permissions->hasMorePages() ? $permissions->currentPage() + 1 : null,
            ],
        ], 200);
    }

    public function details(Permission $permission)
    {
        if (!$permission) {
            return response()->json(['message' => 'Error! permission not found.'], 404);
        }

        $permission->load([
            'roles:id,name,label',
            'users:id,first_name,last_name,username,profile_pic',
        ]);

        return response()->json([
            'message' => 'Success',
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => $permission->label,
                'roles_count' => $permission->roles->count(),
                'users_count' => $permission->users->count(),
                'roles' => $permission->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'label' => $role->label,
                    ];
                })->values(),
                'users' => $permission->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'username' => $user->username,
                        'profile_pic' => $user->profile_pic,
                    ];
                })->values(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:3', 'max:255', 'unique:permissions,name'],
            'label' => ['required', 'min:3', 'max:255'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();

            $permission = Permission::create($validData);

            return response()->json(['message' => "Success, permission created successfully.", 'permission' => $permission], 200);
        }
    }

    public function update(Permission $permission, Request $request)
    {
        $user = auth('api')->user();

        if (!$permission) {
            return response()->json(['message' => 'Error! permission not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:3', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'label' => ['required', 'min:3', 'max:255'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $permission->update($validData);

            return response()->json(['message' => "permission updated successfully", 'permission' => $permission], 200);
        }
    }

    public function delete(Permission $permission, Request $request)
    {
        if (!$permission) {
            return response()->json([
                'message' => 'Not found any permission for delete',
            ], 404);
        }

        // Check if permission is assigned to any role or user
        if ($permission->roles()->exists() || $permission->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this permission because it is still assigned to one or more roles or users.'
            ], 409); // Conflict
        }

        $permission->delete();

        return response()->json([
            'message' => 'Success, permission deleted successfully',
        ], 200);
    }
}

