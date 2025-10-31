<?php

namespace App\Http\Controllers\Api\Admin\Role;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{

    public function roles(Request $request)
    {
        $query = Role::with('permissions');

        // Search functionality
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('label', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = (int) $request->input('perPage', 15);
        $roles = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'permissions_count' => $role->permissions()->count(),
                'users_count' => $role->users()->count(),
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'label' => $permission->label,
                    ];
                }),
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'roles' => $data,
            'pagination' => [
                'total' => $roles->total(),
                'per_page' => $roles->perPage(),
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'prev_page' => $roles->currentPage() > 1 ? $roles->currentPage() - 1 : null,
                'next_page' => $roles->hasMorePages() ? $roles->currentPage() + 1 : null,
            ],
        ], 200);
    }

    public function allPermissions(Request $request)
    {
        $permissions = Permission::orderBy('name')->get(['id', 'name', 'label']);
        
        return response()->json([
            'message' => 'Success',
            'permissions' => $permissions,
        ], 200);
    }

    public function allRoles(Request $request)
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'label']);
        
        return response()->json([
            'message' => 'Success',
            'roles' => $roles,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:3', 'max:255', 'unique:roles,name'],
            'label' => ['required', 'min:3', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();

            $permissions = $validData['permissions'] ?? [];
            unset($validData['permissions']);

            $role = Role::create($validData);
            
            // Attach permissions if provided
            if (!empty($permissions)) {
                $role->permissions()->attach($permissions);
            }

            $role->load('permissions');

            return response()->json([
                'message' => "Success, role created successfully.", 
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'label' => $permission->label,
                        ];
                    }),
                ]
            ], 200);
        }
    }

    public function update(Role $role, Request $request)
    {
        $user = auth('api')->user();

        if (!$role) {
            return response()->json(['message' => 'Error! role not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'min:3', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'label' => ['required', 'min:3', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();

            $permissions = $validData['permissions'] ?? [];
            unset($validData['permissions']);

            $role->update($validData);

            // Sync permissions (replace all existing permissions with the new ones)
            $role->permissions()->sync($permissions);

            $role->load('permissions');

            return response()->json([
                'message' => "role updated successfully", 
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'label' => $permission->label,
                        ];
                    }),
                ]
            ], 200);
        }
    }

    public function delete(Role $role, Request $request)
    {
        if (!$role) {
            return response()->json([
                'message' => 'Not found any role for delete',
            ], 404);
        }

        // Check if role is assigned to any user
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this role because it is still assigned to one or more users.'
            ], 409); // Conflict
        }

        // Detach all permissions before deleting
        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Success, role deleted successfully',
        ], 200);
    }
}

