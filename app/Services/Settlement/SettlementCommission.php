<?php

namespace App\Services\Settlement;

use App\Services\SiteSettingService;

class SettlementCommission
{
    public const SITE_KEY = 'settlement.site_percent';

    public const TEACHER_KEY = 'settlement.teacher_percent';

    public function rates(): array
    {
        $settings = app(SiteSettingService::class);
        $site = (float) $settings->get(self::SITE_KEY, config('settlement.site_percent', 0));
        $teacher = (float) $settings->get(self::TEACHER_KEY, config('settlement.teacher_percent', 100));

        return $this->normalize($site, $teacher);
    }

    public function normalize(float $sitePercent, float $teacherPercent): array
    {
        $site = max(0, min(100, round($sitePercent, 2)));
        $teacher = max(0, min(100, round($teacherPercent, 2)));

        if (abs(($site + $teacher) - 100) > 0.001) {
            $teacher = round(100 - $site, 2);
        }

        return [
            'site_percent' => $site,
            'teacher_percent' => $teacher,
        ];
    }

    public function update(float $sitePercent, float $teacherPercent, ?int $updatedBy = null): array
    {
        $rates = $this->normalize($sitePercent, $teacherPercent);

        app(SiteSettingService::class)->putMany([
            self::SITE_KEY => [
                'value' => $rates['site_percent'],
                'type' => 'float',
                'group' => 'settlement',
            ],
            self::TEACHER_KEY => [
                'value' => $rates['teacher_percent'],
                'type' => 'float',
                'group' => 'settlement',
            ],
        ], $updatedBy);

        return $rates;
    }

    /**
     * @return array{
     *   gross_amount: int,
     *   site_percent: float,
     *   teacher_percent: float,
     *   platform_amount: int,
     *   teacher_amount: int
     * }
     */
    public function split(int $grossAmount, ?array $rates = null): array
    {
        $gross = max(0, $grossAmount);
        $rates ??= $this->rates();
        $sitePercent = (float) $rates['site_percent'];
        $teacherPercent = (float) $rates['teacher_percent'];

        if ($gross === 0 || $teacherPercent >= 100) {
            return [
                'gross_amount' => $gross,
                'site_percent' => $sitePercent,
                'teacher_percent' => $teacherPercent,
                'platform_amount' => 0,
                'teacher_amount' => $gross,
            ];
        }

        if ($teacherPercent <= 0) {
            return [
                'gross_amount' => $gross,
                'site_percent' => $sitePercent,
                'teacher_percent' => $teacherPercent,
                'platform_amount' => $gross,
                'teacher_amount' => 0,
            ];
        }

        $teacherAmount = (int) round($gross * $teacherPercent / 100);
        $platformAmount = $gross - $teacherAmount;

        return [
            'gross_amount' => $gross,
            'site_percent' => $sitePercent,
            'teacher_percent' => $teacherPercent,
            'platform_amount' => max(0, $platformAmount),
            'teacher_amount' => max(0, $teacherAmount),
        ];
    }

    public function explain(?array $split = null): array
    {
        $split ??= $this->split(0);

        return [
            'title' => 'نحوه محاسبه سهم تسویه',
            'lines' => [
                'مبلغ پایه همان مبلغ نهایی فروش بعد از تخفیف است.',
                'کارمزد درگاه پرداخت از خریدار گرفته می‌شود و جزو سهم مدرس یا سایت نیست.',
                'از مبلغ پایه، '.$split['site_percent'].'٪ سهم سایت و '.$split['teacher_percent'].'٪ سهم مدرس/کاربر است.',
                'مبلغ واریزی همان سهم مدرس/کاربر است؛ مابقی به عنوان سهم پلتفرم کسر می‌شود.',
            ],
        ];
    }
}
