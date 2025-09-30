<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelsTableSeeder extends Seeder
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
                'title' => 'مقدماتی',
                'english_title' => 'beginner',
                'slug' => 'beginner',
                'description' => 'مناسب شروع یادگیری از پایه.',
                'icon' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'متوسط',
                'english_title' => 'intermediate',
                'slug' => 'intermediate',
                'description' => 'برای افرادی با آشنایی اولیه.',
                'icon' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'پیشرفته',
                'english_title' => 'advanced',
                'slug' => 'advanced',
                'description' => 'برای حرفه‌ای‌ها و پروژه‌های جدی.',
                'icon' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('levels')->upsert($rows, ['english_title'], ['title','slug','description','icon','updated_at']);
    }
}


