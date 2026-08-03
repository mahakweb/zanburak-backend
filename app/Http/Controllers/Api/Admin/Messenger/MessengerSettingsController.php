<?php

namespace App\Http\Controllers\Api\Admin\Messenger;

use App\Http\Controllers\Controller;
use App\Models\MessengerSetting;
use App\Models\User;
use App\Services\Messenger\MessengerSystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessengerSettingsController extends Controller
{
    public function __construct(
        protected MessengerSystemConfig $config
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'message' => 'Success',
            'data' => $this->config->adminPayload(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $schema = MessengerSystemConfig::schema();
        $rules = [];
        foreach ($schema as $key => $meta) {
            $rules[$key] = match ($meta['type']) {
                'boolean' => 'sometimes|boolean',
                'integer' => 'sometimes|integer',
                'float' => 'sometimes|numeric',
                default => 'sometimes|nullable|string|max:2000',
            };
        }

        $data = $request->validate($rules);
        $settings = $this->config->update($data, $request->user()?->id);

        return response()->json([
            'message' => 'تنظیمات پیام‌رسان ذخیره شد.',
            'data' => [
                'settings' => $settings,
                'schema' => $schema,
                'sections' => $this->config->adminPayload()['sections'],
            ],
        ]);
    }

    public function reset(): JsonResponse
    {
        $defaults = $this->config->defaults();
        $settings = $this->config->update($defaults, request()->user()?->id);

        return response()->json([
            'message' => 'تنظیمات به حالت پیش‌فرض بازگشت.',
            'data' => [
                'settings' => $settings,
                'schema' => MessengerSystemConfig::schema(),
                'sections' => $this->config->adminPayload()['sections'],
            ],
        ]);
    }

    /**
     * Search users and show messenger access override.
     */
    public function users(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = User::query()->select(['id', 'username', 'email', 'first_name', 'last_name', 'created_at']);

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($b) use ($like) {
                $b->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like);
            });
        }

        $users = $query->orderByDesc('id')->limit(30)->get();
        $ids = $users->pluck('id');
        $overrides = MessengerSetting::query()
            ->whereIn('user_id', $ids)
            ->pluck('access_enabled', 'user_id');

        $default = $this->config->bool('users_default_access');

        $data = $users->map(function (User $user) use ($overrides, $default) {
            $override = $overrides->has($user->id) ? $overrides[$user->id] : null;

            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                'access_enabled' => $override,
                'effective_access' => $override === null ? $default : (bool) $override,
                'inherits_default' => $override === null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'data' => $data,
            'default_access' => $default,
        ]);
    }

    public function updateUserAccess(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            // null = inherit global default
            'access_enabled' => 'present|nullable|boolean',
        ]);

        $user = User::query()->findOrFail($userId);
        $this->config->setUserAccess($user, $data['access_enabled']);

        return response()->json([
            'message' => 'دسترسی کاربر به‌روزرسانی شد.',
            'data' => [
                'user_id' => $user->id,
                'access_enabled' => $data['access_enabled'],
                'effective_access' => $data['access_enabled'] === null
                    ? $this->config->bool('users_default_access')
                    : (bool) $data['access_enabled'],
            ],
        ]);
    }
}
