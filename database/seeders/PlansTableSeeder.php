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
                'title' => 'اشتراک ماهانه',
                'english_title' => 'monthly',
                'period_time' => 30,
                'price' => 199000,
                'icon' => null,
                'status' => 1,
                'popular' => 0,
                'description' => 'دسترسی یک‌ماهه به تمامی دوره‌ها.',
                'features' => json_encode(['دسترسی به همه دوره‌ها','پشتیبانی معمولی']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'اشتراک سه‌ماهه',
                'english_title' => 'quarterly',
                'period_time' => 90,
                'price' => 549000,
                'icon' => null,
                'status' => 1,
                'popular' => 1,
                'description' => 'صرفه‌جویی نسبت به ماهانه به همراه امکانات بیشتر.',
                'features' => json_encode(['همه مزایای ماهانه','پشتیبانی سریع']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'اشتراک سالانه',
                'english_title' => 'yearly',
                'period_time' => 365,
                'price' => 1899000,
                'icon' => null,
                'status' => 1,
                'popular' => 0,
                'description' => 'بیشترین صرفه اقتصادی برای یادگیری بلندمدت.',
                'features' => json_encode(['پشتیبانی ویژه','دعوت به رویدادهای اختصاصی']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('plans')->upsert($rows, ['english_title'], ['title','period_time','price','icon','status','popular','description','features','updated_at']);
    }
}


