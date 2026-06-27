<?php



namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class StatusesTableSeeder extends Seeder

{

    /**

     * وضعیت‌های دوره — هر مورد یک رکورد در جدول statuses است (نه ستون جدا).

     * دوره‌ها از طریق status_id به این رکوردها متصل می‌شوند.

     */

    public function run(): void

    {

        $now = now();



        $rows = [

            [

                'title' => 'در حال برگزاری',

                'english_title' => 'ongoing',

                'slug' => 'ongoing',

                'description' => 'دوره‌هایی که در حال تولید و برگزاری هستند.',

                'icon' => null,

                'created_at' => $now,

                'updated_at' => $now,

            ],

            [

                'title' => 'تکمیل ضبط',

                'english_title' => 'completed',

                'slug' => 'completed',

                'description' => 'دوره‌هایی که ضبط تمام جلسات آن‌ها به پایان رسیده است.',

                'icon' => null,

                'created_at' => $now,

                'updated_at' => $now,

            ],

            [

                'title' => 'به‌زودی',

                'english_title' => 'upcoming',

                'slug' => 'upcoming',

                'description' => 'دوره‌هایی که به‌زودی منتشر می‌شوند.',

                'icon' => null,

                'created_at' => $now,

                'updated_at' => $now,

            ],

            [

                'title' => 'پیش‌فروش',

                'english_title' => 'presale',

                'slug' => 'presale',

                'description' => 'دوره‌هایی که در مرحله پیش‌فروش هستند و هنوز محتوای ویدیویی باز نشده است.',

                'icon' => null,

                'created_at' => $now,

                'updated_at' => $now,

            ],

            [

                'title' => 'آرشیو',

                'english_title' => 'archive',

                'slug' => 'archive',

                'description' => 'دوره‌های آرشیوی که دیگر به‌روزرسانی نمی‌شوند اما قابل مشاهده هستند.',

                'icon' => null,

                'created_at' => $now,

                'updated_at' => $now,

            ],

        ];



        DB::table('statuses')->upsert(

            $rows,

            ['english_title'],

            ['title', 'slug', 'description', 'icon', 'updated_at']

        );

    }

}


