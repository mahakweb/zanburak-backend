<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StatusesTableSeeder extends Seeder
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
                'title' => 'در حال برگزاری',
                'english_title' => 'ongoing',
                'slug' => 'ongoing',
                'description' => 'دوره‌هایی که در حال تولید و برگزاری هستند.',
                'icon' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'تکمیل شده',
                'english_title' => 'completed',
                'slug' => 'completed',
                'description' => 'دوره‌هایی که به پایان رسیده‌اند و کامل هستند.',
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
        ];

        DB::table('statuses')->upsert($rows, ['english_title'], ['title','slug','description','icon','updated_at']);
    }
}


