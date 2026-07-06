<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $permissions = [
            ['name' => 'quizzes.view', 'label' => 'مشاهده آزمون‌ها'],
            ['name' => 'quizzes.view.own', 'label' => 'مشاهده آزمون‌های مرتبط با خود'],
            ['name' => 'quizzes.view.any', 'label' => 'مشاهده همه آزمون‌ها'],
            ['name' => 'quizzes.create', 'label' => 'ایجاد آزمون'],
            ['name' => 'quizzes.create.own', 'label' => 'ایجاد آزمون برای دوره‌های خود'],
            ['name' => 'quizzes.create.any', 'label' => 'ایجاد آزمون برای هر دوره‌ای'],
            ['name' => 'quizzes.update', 'label' => 'ویرایش آزمون'],
            ['name' => 'quizzes.update.own', 'label' => 'ویرایش آزمون‌های مرتبط با خود'],
            ['name' => 'quizzes.update.any', 'label' => 'ویرایش هر آزمونی'],
            ['name' => 'quizzes.delete', 'label' => 'حذف آزمون'],
            ['name' => 'quizzes.delete.own', 'label' => 'حذف آزمون‌های مرتبط با خود'],
            ['name' => 'quizzes.delete.any', 'label' => 'حذف هر آزمونی'],
            ['name' => 'quizzes.reports', 'label' => 'گزارش آزمون'],
            ['name' => 'quizzes.reports.own', 'label' => 'گزارش آزمون‌های مرتبط با خود'],
            ['name' => 'quizzes.reports.any', 'label' => 'گزارش همه آزمون‌ها'],
            ['name' => 'quizzes.review', 'label' => 'تصحیح دستی آزمون'],
            ['name' => 'quizzes.review.own', 'label' => 'تصحیح دستی آزمون‌های مرتبط با خود'],
            ['name' => 'quizzes.review.any', 'label' => 'تصحیح دستی همه آزمون‌ها'],
            ['name' => 'quiz_questions.view', 'label' => 'مشاهده بانک سوال'],
            ['name' => 'quiz_questions.view.own', 'label' => 'مشاهده بانک سوال (خود)'],
            ['name' => 'quiz_questions.view.any', 'label' => 'مشاهده همه بانک سوال'],
            ['name' => 'quiz_questions.create', 'label' => 'ایجاد سوال'],
            ['name' => 'quiz_questions.create.own', 'label' => 'ایجاد سوال (خود)'],
            ['name' => 'quiz_questions.create.any', 'label' => 'ایجاد سوال برای همه'],
            ['name' => 'quiz_questions.update', 'label' => 'ویرایش سوال'],
            ['name' => 'quiz_questions.update.own', 'label' => 'ویرایش سوال (خود)'],
            ['name' => 'quiz_questions.update.any', 'label' => 'ویرایش هر سوالی'],
            ['name' => 'quiz_questions.delete', 'label' => 'حذف سوال'],
            ['name' => 'quiz_questions.delete.own', 'label' => 'حذف سوال (خود)'],
            ['name' => 'quiz_questions.delete.any', 'label' => 'حذف هر سوالی'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->upsert(
                [['name' => $perm['name'], 'label' => $perm['label'], 'created_at' => $now, 'updated_at' => $now]],
                ['name'],
                ['label', 'updated_at']
            );
        }
    }
}
