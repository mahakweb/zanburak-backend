<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLogin;
use App\Rules\JalalianBirthDateParts;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
                'status' => (!$user->active || ($user->deactivated_until && $user->deactivated_until > now())) ? 'inactive' : 'active',

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
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'email', 'email_verified_at', 'profile_pic', 'mobile', 'mobile_verified_at', 'cover_pic', 'active', 'deactivation_reason', 'deactivated_until', 'deactivated_by', 'created_at', 'last_seen');
        $user['deactivated_by'] = $user['deactivated_by'] ? $username->deactivatedBy->only('id', 'first_name', 'last_name', 'username', 'profile_pic') : null;
        $user['info'] = $username->info->only('about', 'job', 'birth_date', 'website', 'github', 'twitter', 'linkedin', 'telegram', 'instagram');
        $user['last_login'] = $username->logins()->latest()->first();
        $user['providers'] = $username->providers;
        return response()->json(['message' => 'Success', 'user' => $user]);
    }

    public function toggleActive(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $isDeactivated = !$username->active || ($username->deactivated_until && $username->deactivated_until > now());

        if (!$isDeactivated) {

            $validator = Validator::make($request->all(), [
                'deactivation_reason' => 'required|string',
                'deactivated_until' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error!',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            $validData = $validator->validated();

            $username->active = $validData['deactivated_until'] ? true : false;
            $username->deactivation_reason = $validData['deactivation_reason'];
            $username->deactivated_until = $validData['deactivated_until']
                ? now()->addHours($validData['deactivated_until'])
                : null;
            $username->deactivated_by = $loginUser->id;
            $username->save();

            $this->terminateAllSession($request, $username);

            return response()->json([
                'message' => 'User deactivated successfully',
                'active' => $username->active,
                'deactivation_reason' => $username->deactivation_reason,
                'deactivated_until' => $username->deactivated_until,
                'deactivated_by' => $username->deactivatedBy
                    ? $username->deactivatedBy->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                    : null,
            ]);
        }

        $username->active = true;
        $username->deactivation_reason = null;
        $username->deactivated_until = null;
        $username->deactivated_by = null;
        $username->save();

        return response()->json([
            'message' => 'User activated successfully',
            'active' => $username->active,
            'deactivation_reason' => null,
            'deactivated_until' => null,
            'deactivated_by' => null,
        ]);
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
        $access_tokens = $username->tokens()->select('id', 'name', 'last_used_at', 'ip', 'login_type', 'created_at', 'updated_at')->orderBy('id', 'desc')->get();
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

    public function terminateSession(Request $request, $username)
    {
        $loginUser = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:personal_access_tokens,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validatedData = $validator->validated();
            $login = UserLogin::where('token_id', $validatedData['id'])->whereNull('logged_out_at')->latest()->first();
            if ($login) {
                $login->update(['logged_out_at' => now()]);
            }
            $username->tokens()->where('id', $validatedData['id'])->delete();
            return response()->json(['message' => 'Success, Session for the user was removed.']);
        }
    }
    public function terminateAllSession(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        UserLogin::whereIn(
            'token_id',
            $username->tokens()->pluck('id')
        )
            ->whereNull('logged_out_at')
            ->update(['logged_out_at' => now()]);

        $username->tokens()->delete();

        return response()->json([
            'message' => 'Success, All sessions for the user were removed.'
        ]);
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

    public function create(Request $request)
    {
        $loginUser = auth('api')->user();
        
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/(09)[0-9]{9}/', 'digits:11', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'profile_pic' => ['nullable', 'string', 'url', 'max:500'],
            'cover_pic' => ['nullable', 'string', 'url', 'max:500'],
            'job' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'telegram' => ['nullable', 'string', 'max:50', 'min:3'],
            'instagram' => ['nullable', 'string', 'max:50', 'min:3'],
            'twitter' => ['nullable', 'string', 'max:50', 'min:3'],
            'linkedin' => ['nullable', 'string', 'max:50', 'min:3'],
            'github' => ['nullable', 'string', 'max:50', 'min:3'],
            'website' => ['nullable', 'url', 'max:100'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Create user
            $user = User::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'username' => $validatedData['username'],
                'email' => $validatedData['email'],
                'mobile' => $validatedData['phone'] ?? null,
                'password' => Hash::make($validatedData['password']),
                'active' => $validatedData['status'] === 'active',
                'profile_pic' => $validatedData['profile_pic'] ?? null,
                'cover_pic' => $validatedData['cover_pic'] ?? null,
                'is_staff' => false,
                'is_superuser' => false,
            ]);

            // Create user info
            $user->info()->create([
                'job' => $validatedData['job'] ?? null,
                'about' => $validatedData['bio'] ?? null,
                'telegram' => $validatedData['telegram'] ?? null,
                'instagram' => $validatedData['instagram'] ?? null,
                'twitter' => $validatedData['twitter'] ?? null,
                'linkedin' => $validatedData['linkedin'] ?? null,
                'github' => $validatedData['github'] ?? null,
                'website' => $validatedData['website'] ?? null,
            ]);

            // Attach roles if provided
            if (!empty($validatedData['roles'])) {
                $user->roles()->attach($validatedData['roles']);
            }

            // Attach permissions if provided
            if (!empty($validatedData['permissions'])) {
                $user->permissions()->attach($validatedData['permissions']);
            }

            // Load relationships for response
            $user->load(['roles', 'permissions', 'info']);

            return response()->json([
                'message' => 'کاربر جدید با موفقیت ایجاد شد.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'profile_pic' => $user->profile_pic,
                    'cover_pic' => $user->cover_pic,
                    'active' => $user->active,
                    'status' => $user->active ? 'active' : 'inactive',
                    'roles' => $user->roles,
                    'permissions' => $user->permissions,
                    'info' => $user->info,
                    'created_at' => $user->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در ایجاد کاربر!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadImage(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // max 10 MB
            'type' => ['required', 'in:profile_pic,cover_pic'], // نوع عکس: پروفایل یا کاور
        ]);

        if (!$validator->passes()) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $validatedData = $validator->validated();
            $file = $request->file('file');
            $type = $validatedData['type']; // profile_pic or cover_pic

            // ساخت مسیر ذخیره‌سازی بر اساس نوع عکس
            $folder = $type === 'profile_pic' ? 'users/profile' : 'users/cover';
            $folderPath = $folder . '/' . date('Y/m/d');
            $path = Storage::disk('static')->put($folderPath, $file);
            
            // ساخت URL کامل از config
            $storageBaseUrl = config('filesystems.disks.static.url', 'https://static.zanburak.ir');
            $storageUrl = rtrim($storageBaseUrl, '/') . '/' . ltrim($path, '/');

            // ذخیره URL در user
            $user->update([$type => $storageUrl]);

            return response()->json([
                'message' => 'تصویر با موفقیت آپلود شد.',
                'fileUrl' => $storageUrl,
                'path' => $path,
                'type' => $type,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'کاربر مورد نظر یافت نشد!',
                'errors' => ['user' => ['کاربر با شناسه داده شده وجود ندارد.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در آپلود تصویر!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
