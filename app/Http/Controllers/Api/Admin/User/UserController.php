<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Rules\JalalianBirthDateParts;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Ip2location\IP2LocationLaravel\Facade\IP2LocationLaravel;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function searchUser(Request $request)
    {
        $user = auth('api')->user();
        $searchKey = $request->input('key');
        $limit = $request->input('limit', 20);

        if (!$searchKey) {
            return response()->json([
                'message' => 'No search key provided',
                'result' => [],
            ], 200);
        }


        $users = User::query()
            ->where('first_name', 'LIKE', "%{$searchKey}%")
            ->orWhere('last_name', 'LIKE', "%{$searchKey}%")
            ->orWhere('username', 'LIKE', "%{$searchKey}%")
            ->orWhere('email', 'LIKE', "%{$searchKey}%")
            ->limit(value: $limit)
            ->get(['id', 'first_name', 'last_name', 'username', 'email', 'profile_pic', 'cover_pic']);

        return response()->json([
            'message' => 'Success',
            'result' => $users,
        ], 200);
    }

    public function users(Request $request)
    {
        $query = User::query()
            ->status($request->input('status'))                // all | active | inactive
            ->role($request->input('role'))
            ->subscription($request->input('subscription'))    // all | vip | normal
            ->search($request->input('search'))
            ->sort($request->input('sort', 'newest'));        // newest | oldest

        $perPage = (int) $request->input('perPage', 10);
        $users = $query->paginate($perPage);

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'profile_pic' => $user->profile_pic,
                'cover_pic' => $user->cover_pic,
                'role' => $user->is_superuser ? 'superuser' : ($user->is_staff ? 'staff' : 'user'),
                'info' => $user->info,
                'status' => $user->active ? 'active' : 'inactive',

                'subscription' => $user->hasVip() ? 'vip' : 'normal',
                'active_plan' => $user->activeVipPlan() ? $user->activeVipPlan() : null,

                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'users' => $data,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'prev_page' => $users->currentPage() > 1 ? $users->currentPage() - 1 : null,
                'next_page' => $users->hasMorePages() ? $users->currentPage() + 1 : null,
            ],
        ]);
    }

    public function base($username)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }
        $loginUser = auth('api')->user();
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic', 'last_seen');
        $user['info'] = $username->info->only('job', 'website', 'github', 'twitter', 'linkedin', 'telegram', 'instagram');
        return response()->json(['message' => 'Success', 'user' => $user]);
    }

    public function details($username)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }
        $loginUser = auth('api')->user();
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'email', 'email_verified_at', 'profile_pic', 'mobile', 'mobile_verified_at', 'cover_pic', 'active', 'created_at', 'last_seen');
        $user['info'] = $username->info->only('about', 'job', 'birth_date', 'website', 'github', 'twitter', 'linkedin', 'telegram', 'instagram');
        $user['last_login'] = $username->logins()->latest()->first();
        $user['providers'] = $username->providers;
        return response()->json(['message' => 'Success', 'user' => $user]);
    }

    public function toggleActive(Request $request, $username)
    {
        $username->active = !$username->active;
        $username->save();
        return response()->json(['message' => 'Success', 'active' => $username->active]);
    }

    public function removeProvider(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'provider_id' => ['required', 'exists:user_providers,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $username->providers()->where('id', $validatedData['provider_id'])->delete();
            return response()->json(['message' => 'Success, Provider for the user was removed.']);
        }
    }

    public function updateSocial(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'telegram' => ['nullable', 'string', 'max:50', 'min:3'],
            'instagram' => ['nullable', 'string', 'max:50', 'min:3'],
            'github' => ['nullable', 'string', 'max:50', 'min:3'],
            'linkedin' => ['nullable', 'string', 'max:50', 'min:3'],
            'twitter' => ['nullable', 'string', 'max:50', 'min:3'],
            'website' => ['nullable', 'url', 'max:100'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $updatedSocial = $username->info()->update($validatedData);
            return response()->json(['message' => 'Success', 'social' => $username->info]);
        }

    }

    public function updateCommunications(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'max:255', 'email', 'required_if:verifyEmail,true', Rule::unique('users', 'email')->ignore($username->id)],
            'verifyEmail' => ['boolean'],
            'mobile' => ['nullable', 'required_if:verifyMobile,true', 'regex:/(09)[0-9]{9}/', 'digits:11', Rule::unique('users', 'mobile')->ignore($username->id)],
            'verifyMobile' => ['boolean'],
            'username' => ['required', 'max:255', 'string', Rule::unique('users', 'username')->ignore($username->id)],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $updateCommunication = $username->update([
                'email' => $validatedData['email'],
                'email_verified_at' => $validatedData['verifyEmail'] ? now() : null,
                'mobile' => $validatedData['mobile'],
                'mobile_verified_at' => $validatedData['verifyMobile'] ? now() : null,
                'username' => $validatedData['username'],
            ]);

            return response()->json(['message' => 'Success', 'communication' => $validatedData]);
        }
    }

    public function updateInfo(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'min:3', 'max:255'],
            'last_name' => ['required', 'string', 'min:3', 'max:255'],
            'job' => ['nullable', 'string', 'min:3', 'max:255'],
            'birth_date' => ['nullable', 'string', 'max:10', new JalalianBirthDateParts()],
            'about' => ['nullable', 'max:1000'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();

            if ($validatedData['birth_date']) {
                $validatedData['birth_date'] = Jalalian::fromFormat('Y/m/d', $validatedData['birth_date'])->toCarbon()->format('Y-m-d');
            }

            $updateUser = $username->update([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
            ]);
            $updateUserInfo = $username->info()->update([
                'job' => $validatedData['job'],
                'about' => $validatedData['about'],
                'birth_date' => $validatedData['birth_date'],
            ]);

            return response()->json(['message' => 'Success', 'info' => $validatedData]);
        }
    }

    public function updatePassword(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'confirmed'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();

            $username->forceFill([
                'password' => Hash::make($validatedData['password']),
            ])->save();
            event(new PasswordReset($username));

            return response()->json(['message' => 'Success']);
        }
    }

    public function security(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'email', 'profile_pic', 'is_superuser', 'is_staff');
        $user['last_login'] = $username->logins()->latest()->first();
        $user['logins'] = $username->logins->sortByDesc('id')->values()->all();
        $user['roles'] = $username->roles;
        $user['permissions'] = $username->permissions;
        // $access_tokens = $username->tokens()->select('id', 'name', 'last_used_at', 'ip', 'created_at', 'updated_at')->get();
        $access_tokens = $username->tokens()->select('id', 'name', 'last_used_at', 'ip', 'created_at', 'updated_at')->get();
        foreach ($access_tokens as $accessToken) {
            $accessToken['ipInfo'] = collect(IP2LocationLaravel::get($accessToken['ip']))->only(['countryName', 'countryCode', 'cityName', 'regionName']);
        }
        $user['sessions'] = $access_tokens;
        return response()->json(['message' => 'Success', 'user' => $user]);
    }

    public function allAccess()
    {
        $loginUser = auth('api')->user();
        $access = [
            'roles' => Role::all(),
            'permissions' => Permission::all(),
        ];
        return response()->json(['message' => 'Success', 'access' => $access]);
    }

    public function removeLoginRecord(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:user_logins,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $username->logins()->where('id', $validatedData['id'])->delete();
            return response()->json(['message' => 'Success, Login record for the user was removed.']);
        }
    }

    public function clearLoginHistory(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $username->logins()->delete();
        return response()->json(['message' => 'Success, Login history for the user was cleared.']);
    }

    public function addPermission(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $username->permissions()->attach($validatedData['permissions']);
            return response()->json(['message' => 'Success, Permissions have been registered for the user.']);
        }
    }

    public function addRole(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $username->roles()->attach($validatedData['roles']);
            return response()->json(['message' => 'Success, Roles have been registered for the user.']);
        }
    }

    public function removePermission(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'permission' => ['required', 'exists:permissions,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $username->permissions()->detach($validatedData['permission']);
            return response()->json(['message' => 'Success, Permission for the user was removed.']);
        }
    }

    public function removeRole(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'exists:roles,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $username->roles()->detach($validatedData['role']);
            return response()->json(['message' => 'Success, Role for the user was removed.']);
        }
    }

    public function toggleSuperUser(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        // $validator = Validator::make($request->all(), [
        //     'is_superuser' => ['required', 'boolean'],
        // ]);

        // if (!$validator->passes()) {
        //     return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        // } else {
        //     $validatedData = $validator->validated();
        $username->is_superuser = !$username->is_superuser;
        $username->save();
        return response()->json(['message' => 'Success', 'is_superuser' => $username->is_superuser]);
        // }

    }



}
