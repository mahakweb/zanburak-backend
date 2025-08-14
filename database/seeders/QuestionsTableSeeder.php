<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Ybazli\Faker\Facades\Faker as PersianFaker;

class QuestionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        for ($i = 0 ; $i < 30 ; $i++){
            DB::table('questions')->insert([
                'user_id' => 1,
                'category_id' => 1,
                'subject'  => PersianFaker::word(10),
                'slug' => $faker->slug(6),
                'question' => PersianFaker::paragraph(2),
            ]);
        }
    }
}
