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
        $query = Permission::query();

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
        $permissions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => $permission->label,
                'roles_count' => $permission->roles()->count(),
                'users_count' => $permission->users()->count(),
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

