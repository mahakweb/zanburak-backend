<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // مرحله 1: پایه‌ای ترین داده‌ها (بدون وابستگی)
            PermissionsAndRolesSeeder::class,
            LevelsTableSeeder::class,
            StatusesTableSeeder::class,
            CategoriesAndQuestionCategoriesSeeder::class,
            
            // مرحله 2: داده‌های وابسته به مرحله 1
            UsersAndInfosSeeder::class, // نیاز به Roles دارد
            
            // مرحله 3: داده‌های مستقل
            PathsSeeder::class,
            PlansTableSeeder::class,
            EventGroupsAndEventsSeeder::class,
            FaqCategoriesAndFaqsSeeder::class,
            
            // مرحله 4: داده‌های وابسته به چندین جدول
            CoursesSectionsEpisodesSeeder::class, // نیاز به Users, Levels, Statuses, Categories دارد
            QuestionsAndAnswersSeeder::class, // نیاز به Users و QuestionCategories دارد
        ]);
    }
}
