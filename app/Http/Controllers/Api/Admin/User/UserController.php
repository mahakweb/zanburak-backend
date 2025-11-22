<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Comment;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Wallet;
use Carbon\Carbon;
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

    /**
     * Get financial summary for a specific user (wallet balance + active subscription + history).
     */
    public function financialSummary(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $walletBalance = (int) $username->wallet_balance;
        $activePlan = $username->activeVipPlan();

        $subscriptionHistory = $username->plans()
            ->withPivot(['payment_id', 'price', 'description', 'purchase_type', 'expired_at', 'created_at'])
            ->orderByPivot('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'english_title' => $plan->english_title,
                    'icon' => $plan->icon,
                    'price' => $plan->pivot->price,
                    'description' => $plan->pivot->description,
                    'purchase_type' => $plan->pivot->purchase_type,
                    'expired_at' => $plan->pivot->expired_at,
                    'started_at' => $plan->pivot->created_at,
                    'payment_id' => $plan->pivot->payment_id,
                ];
            })->values();

        return response()->json([
            'message' => 'Success',
            'wallet_balance' => $walletBalance,
            'active_plan' => $activePlan ? [
                'id' => $activePlan->id,
                'title' => $activePlan->title,
                'english_title' => $activePlan->english_title,
                'icon' => $activePlan->icon,
                'price' => $activePlan->price,
                'period_time' => $activePlan->period_time,
                'features' => $activePlan->features,
                'expired_at' => optional($activePlan->pivot)->expired_at,
                'started_at' => optional($activePlan->pivot)->created_at,
            ] : null,
            'subscription_history' => $subscriptionHistory,
        ]);
    }

    /**
     * Get payments list (online transactions) for a specific user.
     * Response structure is similar to PanelController::financial to ease frontend reuse.
     */
    public function financialPayments(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $filter = $request->input('filter', 'all'); // deposit | failed | all
        $type = $request->input('type', 'all');     // course | path | vip | wallet | all
        $sort = $request->input('sort', 'newest');  // newest | oldest

        $query = $username->payments()
            ->select('id', 'uuid', 'payment_method', 'tracking_number', 'reference_id', 'amount', 'driver', 'discount_amount', 'discount_code', 'status', 'paid_at', 'expired_at', 'created_at', 'updated_at', 'description')
            ->with(['attempts', 'items.payable']);

        // Status filter
        $query = match ($filter) {
            'deposit' => $query->where('status', 1),
            'failed'  => $query->where('status', 0),
            default   => $query,
        };

        // Type filter (course, path, vip, wallet)
        if ($type !== 'all') {
            $modelMap = [
                'course' => \App\Models\Course::class,
                'path'   => \App\Models\Path::class,
                'vip'    => \App\Models\Plan::class,
            ];

            if ($type === 'wallet') {
                // کیف پول: description شامل "کیف پول" یا items خالی باشد
                $query->where(function ($q) {
                    $q->where('description', 'like', '%کیف پول%')
                        ->orWhereDoesntHave('items');
                });
            } elseif (isset($modelMap[$type])) {
                $query->whereHas('items', function ($q) use ($modelMap, $type) {
                    $q->where('payable_type', $modelMap[$type]);
                });
            }
        }

        // Sorting
        $sortOrder = $sort === 'oldest' ? 'asc' : 'desc';
        $query->orderBy('created_at', $sortOrder);

        // Pagination (simple manual pagination like PanelController)
        $perPage = (int) $request->input('perPage', 10);
        $currentPage = (int) $request->input('page', 1);

        $total = $query->count();
        $lastPage = (int) ceil($total / ($perPage ?: 1));
        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function (Payment $payment) {
            $isWallet = (str_contains($payment->description ?? '', 'کیف پول') || $payment->items->count() === 0);

            return [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'payment_method' => $payment->payment_method,
                'tracking_number' => $payment->tracking_number,
                'reference_id' => $payment->reference_id,
                'driver' => $payment->driver,
                'amount' => $payment->amount,
                'discount_amount' => $payment->discount_amount,
                'discount_code' => $payment->discount_code,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
                'expired_at' => $payment->expired_at,
                'is_paid' => $payment->isPaid(),
                'can_retry' => $payment->canRetry(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
                'is_wallet' => $isWallet,
                'attempts' => $payment->attempts()->latest()->get(),
                'items' => $payment->items->map(function ($item) {
                    $base = [
                        'id' => $item->id,
                        'payable_type' => class_basename($item->payable_type),
                        'payable_id' => $item->payable_id,
                        'price' => $item->price,
                        'discount_amount' => $item->discount_amount,
                        'discount_code' => $item->discount_code,
                        'final_price' => $item->final_price,
                    ];

                    if ($item->relationLoaded('payable') && $item->payable) {
                        $payable = $item->payable;

                        if ($payable instanceof \App\Models\Course) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'price' => $payable->price,
                            ];
                        } elseif ($payable instanceof \App\Models\Path) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'icon' => $payable->icon,
                                'short_description' => $payable->short_description,
                            ];
                        } elseif ($payable instanceof \App\Models\Plan) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'icon' => $payable->icon,
                                'price' => $payable->price,
                                'period_time' => $payable->period_time,
                                'features' => $payable->features,
                            ];
                        }
                    }

                    return $base;
                }),
            ];
        })->values();

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'type' => $type,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage,
            ],
        ]);
    }

    /**
     * Get wallet transactions for a specific user.
     */
    public function walletTransactions(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $sort = $request->input('sort', 'newest'); // newest | oldest
        $sortOrder = $sort === 'oldest' ? 'asc' : 'desc';

        $query = $username->wallets()
            ->select('id', 'uuid', 'description', 'amount', 'after_balance', 'type', 'tracking_number', 'payment_id', 'reference_id', 'created_at', 'updated_at')
            ->orderBy('created_at', $sortOrder);

        $perPage = (int) $request->input('perPage', 10);
        $currentPage = (int) $request->input('page', 1);

        $total = $query->count();
        $lastPage = (int) ceil($total / ($perPage ?: 1));
        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function (Wallet $wallet) {
            return [
                'id' => $wallet->id,
                'uuid' => $wallet->uuid,
                'description' => $wallet->description,
                'amount' => $wallet->amount,
                'after_balance' => $wallet->after_balance,
                'type' => $wallet->type,
                'tracking_number' => $wallet->tracking_number,
                'payment_id' => $wallet->payment_id,
                'reference_id' => $wallet->reference_id,
                'created_at' => $wallet->created_at,
                'updated_at' => $wallet->updated_at,
            ];
        })->values();

        return response()->json([
            'message' => 'Success',
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage,
            ],
        ]);
    }

    /**
     * Assign a VIP subscription plan to the given user manually by admin.
     */
    public function assignPlan(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'plan_id' => ['required', 'exists:plans,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'expired_at' => ['required', 'date', 'after:now'],
            'description' => ['nullable', 'string', 'max:1000'],
            'purchase_type' => ['nullable', 'string', 'max:50'],
        ]);

        if (!$validator->passes()) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $data = $validator->validated();

        $plan = Plan::findOrFail($data['plan_id']);
        $expiredAt = Carbon::parse($data['expired_at']);

        $price = array_key_exists('price', $data) && $data['price'] !== null
            ? $data['price']
            : $plan->price;

        $purchaseType = $data['purchase_type'] ?? 'manual';
        $description = $data['description'] ?? 'ثبت اشتراک به صورت دستی توسط ادمین';

        $username->plans()->attach($plan->id, [
            'price' => $price,
            'description' => $description,
            'purchase_type' => $purchaseType,
            'expired_at' => $expiredAt,
        ]);

        // Refresh user relations
        $username->load('plans');

        // Reuse financial summary-style response
        $activePlan = $username->activeVipPlan();
        $subscriptionHistory = $username->plans()
            ->withPivot(['payment_id', 'price', 'description', 'purchase_type', 'expired_at', 'created_at'])
            ->orderByPivot('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function (Plan $p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'english_title' => $p->english_title,
                    'icon' => $p->icon,
                    'price' => $p->pivot->price,
                    'description' => $p->pivot->description,
                    'purchase_type' => $p->pivot->purchase_type,
                    'expired_at' => $p->pivot->expired_at,
                    'started_at' => $p->pivot->created_at,
                    'payment_id' => $p->pivot->payment_id,
                ];
            })->values();

        return response()->json([
            'message' => 'Subscription assigned successfully',
            'active_plan' => $activePlan ? [
                'id' => $activePlan->id,
                'title' => $activePlan->title,
                'english_title' => $activePlan->english_title,
                'icon' => $activePlan->icon,
                'price' => $activePlan->price,
                'period_time' => $activePlan->period_time,
                'features' => $activePlan->features,
                'expired_at' => optional($activePlan->pivot)->expired_at,
                'started_at' => optional($activePlan->pivot)->created_at,
            ] : null,
            'subscription_history' => $subscriptionHistory,
        ], 200);
    }

    /**
     * Get courses list for a specific user.
     */
    public function courses(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $search = $request->input('search');
        $type = $request->input('type', 'all');     // free | cash | cash-vip | all
        $publish = $request->input('publish', 'all'); // published | draft | all
        $sort = $request->input('sort', 'newest');  // newest | oldest
        $perPage = (int) $request->input('perPage', 20);
        $currentPage = (int) $request->input('page', 1);

        $query = $username->courses()
            ->with(['teacher:id,first_name,last_name,username,profile_pic', 'status:id,title,english_title', 'category:id,title,english_title,slug', 'section.episode'])
            ->withCount('section as section_count');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('english_title', 'LIKE', "%{$search}%")
                    ->orWhere('short_description', 'LIKE', "%{$search}%");
            });
        }

        // Type filter
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Publish filter
        if ($publish === 'published') {
            $query->where('publish', 1);
        } elseif ($publish === 'draft') {
            $query->where('publish', 0);
        }

        // Sorting
        $sortOrder = $sort === 'oldest' ? 'asc' : 'desc';
        $query->orderBy('course_user.created_at', $sortOrder);

        // Pagination
        $total = $query->count();
        $lastPage = (int) ceil($total / ($perPage ?: 1));
        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function ($course) {
            // Count episodes through sections
            $episodeCount = 0;
            if ($course->relationLoaded('section')) {
                foreach ($course->section as $section) {
                    if ($section->relationLoaded('episode')) {
                        $episodeCount += $section->episode->count();
                    }
                }
            }
            
            return [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'short_description' => $course->short_description,
                'poster' => $course->poster,
                'type' => $course->type,
                'price' => $course->price,
                'total_time' => $course->totalTime(),
                'publish' => $course->publish,
                'section_count' => $course->section_count,
                'episode_count' => $episodeCount,
                'status' => $course->status,
                'categories' => $course->category,
                'teacher' => $course->teacher,
                'purchase_price' => $course->pivot->price,
                'payment_id' => $course->pivot->payment_id,
                'completed_at' => $course->pivot->completed_at,
                'purchased_at' => $course->pivot->created_at,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
            ];
        })->values();

        return response()->json([
            'message' => 'Success',
            'courses' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage,
            ],
        ]);
    }

    /**
     * Get comments list for a specific user (for admin panel).
     */
    public function comments(Request $request, $username)
    {
        $loginUser = auth('api')->user();

        $filter = $request->input('filter', 'all'); // all | course | episode | path
        $commentable_type = match ($filter) {
            'course' => \App\Models\Course::class,
            'episode' => \App\Models\Episode::class,
            'path' => \App\Models\Path::class,
            default => null, // 'all' or any other value means show all types
        };

        $sort = $request->input('sort', 'newest'); // newest | oldest
        $sortOrder = $sort === 'oldest' ? 'asc' : 'desc';

        $status = $request->input('status', 'all'); // all | published | unpublished
        $statusFilter = match ($status) {
            'published' => 1,
            'unpublished' => 0,
            default => null,
        };

        $viewMode = $request->input('viewMode', 'table'); // table | grid
        $withChildren = $request->input('with') === 'children' || $viewMode === 'grid';

        $commentsPerPage = (int) $request->input('perPage', 10);
        $currentPage = (int) $request->input('page', 1);

        if ($viewMode === 'grid' || $withChildren) {
            // Grid view: Get only parent comments with their children
            $commentsQuery = $username->comments()
                ->where('parent_id', 0)
                ->with(['commentable', 'user']);

            // Only filter by type if a specific type is selected
            if ($commentable_type !== null) {
                $commentsQuery->where('commentable_type', $commentable_type);
            }

            if (!is_null($statusFilter)) {
                $commentsQuery->where('approved', $statusFilter);
            }

            $commentsQuery->orderBy('created_at', $sortOrder);

            $total = $commentsQuery->count();
            $lastPage = (int) ceil($total / ($commentsPerPage ?: 1));
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

            $paginatedComments = $commentsQuery->skip(($currentPage - 1) * $commentsPerPage)
                ->take($commentsPerPage)
                ->get();

            $result = $paginatedComments->map(function (Comment $item) use ($statusFilter, $sortOrder, $username) {
                $commentable = $item->commentable;
                $commentable_type = $item->commentable_type;

                $base = [
                    'id' => $item->id,
                    'comment' => $item->comment,
                    'approved' => $item->approved,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'parent_id' => $item->parent_id,
                    'type' => class_basename($commentable_type),
                ];

                // Load children - only from this user
                $childrenQuery = $item->childs()
                    ->where('user_id', $username->id);
                if (!is_null($statusFilter)) {
                    $childrenQuery->where('approved', $statusFilter);
                }
                $children = $childrenQuery->orderBy('created_at', $sortOrder)
                    ->with(['user'])
                    ->get();

                $base['children'] = $children->map(function (Comment $child) {
                    return [
                        'id' => $child->id,
                        'comment' => $child->comment,
                        'approved' => $child->approved,
                        'created_at' => $child->created_at,
                        'updated_at' => $child->updated_at,
                        'parent_id' => $child->parent_id,
                        'user' => $child->user
                            ? $child->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ];
                })->values();

                // Commentable info
                $base['commentable'] = match ($commentable_type) {
                    \App\Models\Course::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'teacher' => $commentable->teacher
                            ? $commentable->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ] : null,
                    \App\Models\Episode::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'course' => $commentable->section && $commentable->section->course
                            ? array_merge(
                                $commentable->section->course->only('id', 'title', 'english_title', 'slug', 'poster'),
                                [
                                    'teacher' => $commentable->section->course->teacher
                                        ? $commentable->section->course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                                        : null,
                                ]
                            )
                            : null,
                    ] : null,
                    \App\Models\Path::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'icon' => $commentable->icon,
                    ] : null,
                    default => null,
                };

                return $base;
            })->values();
        } else {
            // Table view: Get all comments flat (including replies) sorted by time
            $commentsQuery = $username->comments()
                ->with(['commentable', 'parent.user']);

            // Only filter by type if a specific type is selected
            if ($commentable_type !== null) {
                $commentsQuery->where('commentable_type', $commentable_type);
            }

            if (!is_null($statusFilter)) {
                $commentsQuery->where('approved', $statusFilter);
            }

            $commentsQuery->orderBy('created_at', $sortOrder);

            $total = $commentsQuery->count();
            $lastPage = (int) ceil($total / ($commentsPerPage ?: 1));
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

            $paginatedComments = $commentsQuery->skip(($currentPage - 1) * $commentsPerPage)
                ->take($commentsPerPage)
                ->get();

            $result = $paginatedComments->map(function (Comment $item) {
                $commentable = $item->commentable;
                $commentable_type = $item->commentable_type;

                $base = [
                    'id' => $item->id,
                    'comment' => $item->comment,
                    'approved' => $item->approved,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'parent_id' => $item->parent_id,
                    'type' => class_basename($commentable_type),
                ];

                // Parent comment (if this is a reply)
                $base['parent'] = null;
                if ($item->relationLoaded('parent') && $item->parent) {
                    $base['parent'] = [
                        'id' => $item->parent->id,
                        'comment' => $item->parent->comment,
                        'approved' => $item->parent->approved,
                        'created_at' => $item->parent->created_at,
                        'user' => $item->parent->user
                            ? $item->parent->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ];
                }

                // Commentable info
                $base['commentable'] = match ($commentable_type) {
                    \App\Models\Course::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'teacher' => $commentable->teacher
                            ? $commentable->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ] : null,
                    \App\Models\Episode::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'course' => $commentable->section && $commentable->section->course
                            ? array_merge(
                                $commentable->section->course->only('id', 'title', 'english_title', 'slug', 'poster'),
                                [
                                    'teacher' => $commentable->section->course->teacher
                                        ? $commentable->section->course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                                        : null,
                                ]
                            )
                            : null,
                    ] : null,
                    \App\Models\Path::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'icon' => $commentable->icon,
                    ] : null,
                    default => null,
                };

                return $base;
            })->values();
        }

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'status' => $status,
            'sort' => $sort,
            'comments' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $commentsPerPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage,
            ],
        ]);
    }

    /**
     * Assign course to user
     */
    public function assignCourse(Request $request, $username)
    {
        $courseId = $request->input('course_id');
        $course = Course::find($courseId);
        
        if (!$course) {
            return response()->json(['message' => 'Error!, course not found.'], 404);
        }

        if ($username->courses()->where('courses.id', $course->id)->exists()) {
            return response()->json([
                'message' => 'Error! this course already assigned to this user.'
            ], 409);
        }

        $username->courses()->attach([
            [
                'course_id' => $course->id,
                'payment_id' => null,
                'price' => 0
            ]
        ]);

        $result = [
            'id' => $course->id,
            'title' => $course->title,
            'english_title' => $course->english_title,
            'slug' => $course->slug,
            'poster' => $course->poster,
            'type' => $course->type,
            'publish' => $course->publish,
            'short_description' => $course->short_description,
            'purchase_price' => 0,
            'purchased_at' => now(),
            'completed_at' => null,
            'total_time' => $course->totalTime(),
        ];

        return response()->json(['message' => 'Success', 'result' => $result], 200);
    }

    /**
     * Remove course from user
     */
    public function removeCourse(Request $request, $username)
    {
        $courseId = $request->input('course_id');
        $course = Course::find($courseId);
        
        if (!$course) {
            return response()->json(['message' => 'Error!, course not found.'], 404);
        }

        if (!$username->courses()->where('courses.id', $course->id)->exists()) {
            return response()->json([
                'message' => 'Error! this course is not assigned to this user.'
            ], 404);
        }

        $username->courses()->detach($course->id);

        return response()->json(['message' => 'Success, Course has been removed from user.'], 200);
    }
}
