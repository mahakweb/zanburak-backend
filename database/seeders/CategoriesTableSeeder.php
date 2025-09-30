<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $parents = [
            [
                'id' => 1,
                'title' => 'برنامه‌نویسی',
                'english_title' => 'programming',
                'slug' => 'programming',
                'parent_id' => null,
                'description' => 'یادگیری زبان‌ها و مفاهیم برنامه‌نویسی.',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'title' => 'طراحی وب',
                'english_title' => 'web-design',
                'slug' => 'web-design',
                'parent_id' => null,
                'description' => 'طراحی رابط و تجربه کاربری برای وب.',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $children = [
            [
                'title' => 'لاراول',
                'english_title' => 'laravel',
                'slug' => 'laravel',
                'parent_id' => 1,
                'description' => 'چارچوب محبوب PHP برای توسعه وب.',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'جاوااسکریپت',
                'english_title' => 'javascript',
                'slug' => 'javascript',
                'parent_id' => 1,
                'description' => 'زبان اسکریپتی برای توسعه فرانت‌اند و بک‌اند.',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'React',
                'english_title' => 'react',
                'slug' => 'react',
                'parent_id' => 2,
                'description' => 'کتابخانه‌ای برای ساخت UIهای تعاملی.',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('categories')->upsert($parents, ['id'], ['title','english_title','slug','parent_id','description','icon','status','updated_at']);
        DB::table('categories')->upsert($children, ['slug'], ['title','parent_id','description','icon','status','updated_at']);
    }
}


