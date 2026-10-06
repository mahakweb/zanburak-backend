<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settlement\SettlementCommission;
use Illuminate\Http\Request;

class SettlementSettingsController extends Controller
{
    public function __construct(private SettlementCommission $commission)
    {
    }

    public function show(Request $request)
    {
        $this->assertSuperUser($request);
        $rates = $this->commission->rates();
        $sample = $this->commission->split(1000000, $rates);

        return response()->json([
            'message' => 'Success',
            'settings' => $rates,
            'explain' => $this->commission->explain($sample),
            'sample' => $sample,
        ]);
    }

    public function update(Request $request)
    {
        $this->assertSuperUser($request);

        $data = $request->validate([
            'site_percent' => 'required|numeric|min:0|max:100',
            'teacher_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $site = (float) $data['site_percent'];
        $teacher = array_key_exists('teacher_percent', $data) && $data['teacher_percent'] !== null
            ? (float) $data['teacher_percent']
            : round(100 - $site, 2);

        if (abs(($site + $teacher) - 100) > 0.01) {
            return response()->json([
                'message' => 'مجموع درصد سایت و مدرس باید دقیقاً ۱۰۰ باشد.',
            ], 422);
        }

        $rates = $this->commission->update($site, $teacher, $request->user()?->id);
        $sample = $this->commission->split(1000000, $rates);

        return response()->json([
            'message' => 'تنظیمات سهم تسویه ذخیره شد.',
            'settings' => $rates,
            'explain' => $this->commission->explain($sample),
            'sample' => $sample,
        ]);
    }

    private function assertSuperUser(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperUser()) {
            abort(403, 'فقط مدیرکل می‌تواند تنظیمات سهم تسویه را مدیریت کند.');
        }
    }
}
