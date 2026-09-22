<?php

namespace App\Http\Controllers\Api\Admin\Mission;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\MissionCategory;
use App\Models\UserMission;
use App\Services\Mission\MissionAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MissionController extends Controller
{
    public function __construct(
        protected MissionAdminService $missionAdminService
    ) {}

    public function stats(Request $request)
    {
        $days = min((int) $request->input('days', 30), 365);

        return response()->json([
            'message' => 'Success',
            'stats' => $this->missionAdminService->getStats($days),
        ]);
    }

    public function index(Request $request)
    {
        $wiredIds = $this->missionAdminService->getWiredMissionIds();

        $query = Mission::with('category')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->listener_status === 'wired', fn ($q) => $q->whereIn('id', $wiredIds))
            ->when($request->listener_status === 'unwired', fn ($q) => $q->whereNotIn('id', $wiredIds))
            ->when($request->active_status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->active_status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->status === 'expired', fn ($q) => $q->whereNotNull('expired_at')->where('expired_at', '<', now()));

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'participants' => $query->withCount('users as participants_count')->orderByDesc('participants_count'),
            'completions' => $query->withCount(['users as completed_count' => fn ($q) => $q->whereNotNull('mission_user.completed_at')])->orderByDesc('completed_count'),
            default => $query->latest(),
        };

        $perPage = min((int) $request->input('perPage', 18), 100);
        $page = max((int) $request->input('page', 1), 1);
        $missions = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Success',
            'missions' => $missions->getCollection()->map(
                fn ($m) => $this->missionAdminService->transformMission($m)
            ),
            'pagination' => [
                'total' => $missions->total(),
                'per_page' => $missions->perPage(),
                'current_page' => $missions->currentPage(),
                'last_page' => $missions->lastPage(),
                'from' => $missions->firstItem(),
                'to' => $missions->lastItem(),
            ],
        ]);
    }

    public function show(Mission $mission)
    {
        $mission->load('category');

        return response()->json([
            'message' => 'Success',
            'mission' => $this->missionAdminService->transformMission($mission, true),
        ]);
    }

    public function participants(Mission $mission, Request $request)
    {
        $limit = min((int) $request->input('limit', 50), 200);
        $status = $request->input('status');

        $participants = $this->missionAdminService->getMissionParticipants($mission->id, $limit, $status);

        $total = UserMission::where('mission_id', $mission->id)->count();
        $completed = UserMission::where('mission_id', $mission->id)->whereNotNull('completed_at')->count();

        return response()->json([
            'message' => 'Success',
            'mission' => $this->missionAdminService->transformMission($mission),
            'participants' => $participants,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $total - $completed,
            ],
        ]);
    }

    public function allParticipants(Request $request)
    {
        $result = $this->missionAdminService->getAllParticipants([
            'page' => $request->input('page', 1),
            'per_page' => $request->input('perPage', $request->input('per_page', 30)),
            'mission_id' => $request->input('mission_id') ?: null,
            'status' => $request->input('status') ?: null,
            'search' => $request->input('search') ?: null,
        ]);

        return response()->json(array_merge(['message' => 'Success'], $result));
    }

    public function syncReportProgress()
    {
        $result = $this->missionAdminService->syncApprovedReportMissions();

        return response()->json(array_merge([
            'message' => 'پیشرفت ماموریت گزارش‌ها همگام‌سازی شد.',
        ], $result));
    }

    public function toggleActive(Mission $mission)
    {
        $mission->update(['is_active' => !$mission->is_active]);
        $mission->load('category');

        return response()->json([
            'message' => $mission->is_active ? 'ماموریت فعال شد.' : 'ماموریت غیرفعال شد.',
            'mission' => $this->missionAdminService->transformMission($mission),
        ]);
    }

    public function uploadIcon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:3072'],
            'old_icon' => ['nullable', 'string', 'max:500'],
        ], [
            'icon.required' => 'فایل تصویر الزامی است.',
            'icon.mimes' => 'فرمت تصویر باید JPG، PNG، WEBP یا SVG باشد.',
            'icon.max' => 'حداکثر حجم تصویر ۳ مگابایت است.',
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        try {
            $disk = 'static';
            $folder = 'images/icon/missions/' . date('Y/m/d');
            $filePath = $request->file('icon')->store($folder, $disk);
            $url = Storage::disk($disk)->url($filePath);

            if ($request->filled('old_icon')) {
                $this->deleteIconFile($request->input('old_icon'));
            }

            return response()->json(['message' => 'تصویر با موفقیت آپلود شد.', 'icon' => $url], 200);
        } catch (\Throwable $e) {
            \Log::error('Mission icon upload failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'آپلود تصویر ناموفق بود. اتصال استوریج را بررسی کنید.',
                'errors' => ['icon' => ['آپلود تصویر ناموفق بود.']],
            ], 500);
        }
    }

    protected function deleteIconFile(?string $url): void
    {
        if (!$url || !Str::is('http*://*', $url)) {
            return;
        }

        $disk = 'static';
        $base = rtrim((string) config("filesystems.disks.$disk.url"), '/');

        if ($base === '' || !str_starts_with($url, $base)) {
            return;
        }

        $path = ltrim(substr($url, strlen($base)), '/');

        try {
            if ($path !== '' && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        } catch (\Throwable $e) {
            // ignore deletion errors so the main operation isn't blocked
        }
    }

    public function store(Request $request)
    {
        $validator = $this->validateMission($request);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        $data = $validator->validated();
        $missionId = $data['id'] ?? $this->missionAdminService->generateMissionId($data['title']);

        if (Mission::where('id', $missionId)->exists()) {
            return response()->json(['message' => 'Validation error!', 'errors' => ['id' => ['شناسه ماموریت تکراری است.']]], 422);
        }

        $mission = Mission::create([
            'id' => $missionId,
            'title' => $data['title'],
            'category_id' => $data['category_id'],
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? '',
            'is_active' => $data['is_active'] ?? true,
            'levels' => json_encode($this->missionAdminService->prepareLevelsForStorage($data['levels'])),
            'expired_at' => $data['expired_at'] ?? null,
        ]);

        $mission->load('category');

        return response()->json([
            'message' => 'ماموریت با موفقیت ایجاد شد.',
            'mission' => $this->missionAdminService->transformMission($mission),
        ], 201);
    }

    public function update(Mission $mission, Request $request)
    {
        $validator = $this->validateMission($request, $mission);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        $data = $validator->validated();

        $newIcon = $data['icon'] ?? $mission->icon;
        if ($mission->icon && $newIcon !== $mission->icon) {
            $this->deleteIconFile($mission->icon);
        }

        $mission->update([
            'title' => $data['title'],
            'category_id' => $data['category_id'],
            'icon' => $newIcon,
            'description' => $data['description'] ?? '',
            'is_active' => $data['is_active'] ?? $mission->is_active,
            'levels' => json_encode($this->missionAdminService->prepareLevelsForStorage($data['levels'])),
            'expired_at' => $data['expired_at'] ?? null,
        ]);

        $mission->load('category');

        return response()->json([
            'message' => 'ماموریت با موفقیت به‌روزرسانی شد.',
            'mission' => $this->missionAdminService->transformMission($mission),
        ]);
    }

    public function destroy(Mission $mission)
    {
        $participantsCount = UserMission::where('mission_id', $mission->id)->count();

        if ($participantsCount > 0 && !request()->boolean('force')) {
            return response()->json([
                'message' => "این ماموریت {$participantsCount} شرکت‌کننده دارد. برای حذف، force=true ارسال کنید.",
                'participants_count' => $participantsCount,
            ], 409);
        }

        $this->deleteIconFile($mission->icon);

        UserMission::where('mission_id', $mission->id)->delete();
        $mission->delete();

        return response()->json(['message' => 'ماموریت با موفقیت حذف شد.']);
    }

    public function categories()
    {
        $categories = MissionCategory::withCount('missions')->latest()->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'title' => $cat->title,
                'english_title' => $cat->english_title,
                'slug' => $cat->slug,
                'missions_count' => $cat->missions_count,
                'active_missions_count' => Mission::where('category_id', $cat->id)->where('is_active', true)->count(),
                'created_at' => $cat->created_at,
                'updated_at' => $cat->updated_at,
            ]);

        return response()->json(['message' => 'Success', 'categories' => $categories]);
    }

    public function updateScoreSettings(Request $request, \App\Services\ScoresService $scoresService)
    {
        $validator = Validator::make($request->all(), [
            'conversion_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'min_scores' => ['required', 'integer', 'min:0', 'max:100000000'],
        ]);

        if (!$validator->passes()) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $data = $validator->validated();
        $settings = $scoresService->updateConversionSettings(
            (float) $data['conversion_rate'],
            (int) $data['min_scores'],
            auth('api')->id()
        );

        return response()->json([
            'message' => 'تنظیمات تبدیل امتیاز ذخیره شد.',
            'score_settings' => $settings,
        ]);
    }

    protected function validateMission(Request $request, ?Mission $mission = null): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'id' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('missions', 'id')->ignore($mission?->id, 'id')],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:mission_categories,id'],
            'icon' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'expired_at' => ['nullable', 'date'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.goal' => ['required', 'integer', 'min:1'],
            'levels.*.exp' => ['required', 'integer', 'min:0'],
            'levels.*.requirements' => ['nullable', 'array'],
        ]);
    }
}
