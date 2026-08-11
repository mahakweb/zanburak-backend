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
            QuizPermissionsSeeder::class,
            CertificatePermissionsSeeder::class,
            TagPermissionsSeeder::class,
            RoutePermissionsSeeder::class,
            MailInboxAccountsSeeder::class,
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
            MessengerFaqSeeder::class,
            MissionsSeeder::class,
            
            // مرحله 4: داده‌های وابسته به چندین جدول
            CoursesSectionsEpisodesSeeder::class, // نیاز به Users, Levels, Statuses, Categories دارد
            QuestionsAndAnswersSeeder::class, // نیاز به Users و QuestionCategories دارد
            TagsTableSeeder::class, // تگ‌های محبوب (بعد از پرسش‌ها و دوره‌ها برای اتصال تگ)

            // مرحله ۷: مقالات (نیاز به Users و ArticleCategories دارد)
            ArticlesTableSeeder::class,

            // مرحله 5: سیستم آزمون (نیاز به Courses, Episodes, Users دارد)
            QuizSeeder::class,

            // مرحله 6: قالب‌های گواهینامه
            CertificateTemplateSeeder::class,

            // مرحله 8: داده‌های نمونه برای تست content scoping (چند مدرس / پرداخت / آمار)
            ContentScopeDemoSeeder::class,
        ]);
    }
}
