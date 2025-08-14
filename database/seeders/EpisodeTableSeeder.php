<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Ybazli\Faker\Facades\Faker as PersianFaker;

class EpisodeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        for ($i = 0 ; $i < 9 ; $i++){
            DB::table('episodes')->insert([
                'section_id' => 2,
                'title'  => PersianFaker::word(20),
                'english_title'  => $faker->sentence(6),
                'slug' => $faker->slug(6),
                'description' => PersianFaker::paragraph(5),
                'total_time' => rand(30,600),
                'publish_date' => now(),
                'publish' => rand(0,1),
                'lock' => rand(0,1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
