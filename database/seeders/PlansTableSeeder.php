<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $rows = [
            [
                'title' => 'اشتراک VIP ماهانه',
                'english_title' => 'vip-monthly',
                'period_time' => 30,
                'price' => 199000,
                'icon' => null,
                'status' => 1,
                'popular' => 0,
                'description' => 'دسترسی یک‌ماهه VIP به تمامی دوره‌ها، مسیرهای یادگیری و محتوای اختصاصی.',
                'features' => json_encode([
                    'دسترسی نامحدود به همه دوره‌ها',
                    'دسترسی به مسیرهای یادگیری',
                    'دانلود فایل‌های دوره‌ها',
                    'پشتیبانی معمولی',
                    'گواهینامه پایان دوره'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'اشتراک VIP سه‌ماهه',
                'english_title' => 'vip-quarterly',
                'period_time' => 90,
                'price' => 549000,
                'icon' => null,
                'status' => 1,
                'popular' => 1,
                'description' => 'صرفه‌جویی 8% نسبت به ماهانه به همراه امکانات بیشتر و پشتیبانی سریع‌تر.',
                'features' => json_encode([
                    'همه مزایای اشتراک ماهانه',
                    'پشتیبانی سریع (پاسخ در کمتر از 24 ساعت)',
                    'دسترسی به دوره‌های جدید قبل از انتشار عمومی',
                    'مشاوره رایگان در انتخاب مسیر یادگیری',
                    'شرکت در وبینارهای اختصاصی'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'اشتراک VIP شش‌ماهه',
                'english_title' => 'vip-semiannual',
                'period_time' => 180,
                'price' => 999000,
                'icon' => null,
                'status' => 1,
                'popular' => 0,
                'description' => 'صرفه‌جویی 16% نسبت به ماهانه - بهترین انتخاب برای یادگیری متوسط‌مدت.',
                'features' => json_encode([
                    'همه مزایای اشتراک سه‌ماهه',
                    'پشتیبانی اولویت‌دار',
                    'دسترسی به پروژه‌های عملی اختصاصی',
                    'مشاوره شغلی و راهنمایی برای ورود به بازار کار',
                    'گواهینامه معتبر بین‌المللی'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'اشتراک VIP سالانه',
                'english_title' => 'vip-yearly',
                'period_time' => 365,
                'price' => 1899000,
                'icon' => null,
                'status' => 1,
                'popular' => 0,
                'description' => 'بیشترین صرفه اقتصادی (21% تخفیف) برای یادگیری بلندمدت و تبدیل شدن به یک برنامه‌نویس حرفه‌ای.',
                'features' => json_encode([
                    'همه مزایای اشتراک شش‌ماهه',
                    'پشتیبانی ویژه VIP (پاسخ در کمتر از 12 ساعت)',
                    'دسترسی مادام‌العمر به دوره‌های خریداری شده',
                    'مشاوره اختصاصی با مدرسین',
                    'دعوت به رویدادهای اختصاصی و شبکه‌سازی',
                    'امکان شرکت در پروژه‌های واقعی',
                    'گواهینامه معتبر بین‌المللی',
                    'رزومه‌سازی و کمک در پیدا کردن شغل'
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('plans')->upsert($rows, ['english_title'], ['title','period_time','price','icon','status','popular','description','features','updated_at']);
    }
}


