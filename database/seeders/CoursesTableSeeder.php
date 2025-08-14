<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Ybazli\Faker\Facades\Faker as PersianFaker;

class CoursesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        for ($i = 0 ; $i < 10 ; $i++){
            DB::table('courses')->insert([
                'teacher_id' => 1,
                'title'  => PersianFaker::word(10),
                'english_title'  => $faker->sentence(5),
                'slug' => $faker->slug(6),
                'description' => PersianFaker::paragraph(5),
                'total_time' => rand(30,600),
                'start_date' => now(),
                'price' => rand(100000, 1500000),
                'publish' => rand(0,1),
                'status_id' => rand(1,2),
                'level_id'=> rand(1,2),
                'trailer' => 'Laravel-Socket-io.mp4',
                'poster' => '/assets/images/paths/craft_200x168.png'
            ]);
        }
    }
}
