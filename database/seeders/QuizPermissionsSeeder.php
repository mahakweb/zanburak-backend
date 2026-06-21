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
            ['name' => 'quizzes.create', 'label' => 'ایجاد آزمون'],
            ['name' => 'quizzes.update', 'label' => 'ویرایش آزمون'],
            ['name' => 'quizzes.delete', 'label' => 'حذف آزمون'],
            ['name' => 'quizzes.reports', 'label' => 'گزارش آزمون'],
            ['name' => 'quizzes.review', 'label' => 'تصحیح دستی آزمون'],
            ['name' => 'quiz_questions.view', 'label' => 'مشاهده بانک سوال'],
            ['name' => 'quiz_questions.create', 'label' => 'ایجاد سوال'],
            ['name' => 'quiz_questions.update', 'label' => 'ویرایش سوال'],
            ['name' => 'quiz_questions.delete', 'label' => 'حذف سوال'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->upsert(
                [['name' => $perm['name'], 'label' => $perm['label'], 'created_at' => $now, 'updated_at' => $now]],
                ['name'],
                ['label', 'updated_at']
            );
        }

        $permNames = array_column($permissions, 'name');
        $permIds = DB::table('permissions')->whereIn('name', $permNames)->pluck('id', 'name');

        $rolePermissionMap = [
            'administrator' => $permNames,
            'course_manager' => $permNames,
            'course_manager_all' => $permNames,
            'teacher' => [
                'quizzes.view',
                'quizzes.create',
                'quizzes.update',
                'quizzes.reports',
                'quizzes.review',
                'quiz_questions.view',
                'quiz_questions.create',
                'quiz_questions.update',
            ],
            'teacher_assistant' => [
                'quizzes.view',
                'quiz_questions.view',
            ],
        ];

        $rows = [];
        foreach ($rolePermissionMap as $roleName => $names) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            foreach ($names as $name) {
                if (! isset($permIds[$name])) {
                    continue;
                }
                $rows[] = [
                    'role_id' => $role->id,
                    'permission_id' => $permIds[$name],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('permission_role')->upsert($rows, ['role_id', 'permission_id'], ['updated_at']);
        }
    }
}
