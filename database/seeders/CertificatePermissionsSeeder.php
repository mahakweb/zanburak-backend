<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CertificatePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $permissions = [
            ['name' => 'certificates.view', 'label' => 'مشاهده گواهینامه‌ها'],
            ['name' => 'certificates.create', 'label' => 'صدور گواهینامه'],
            ['name' => 'certificates.update', 'label' => 'ویرایش گواهینامه'],
            ['name' => 'certificates.delete', 'label' => 'حذف/ابطال گواهینامه'],
            ['name' => 'certificates.templates.view', 'label' => 'مشاهده قالب گواهینامه'],
            ['name' => 'certificates.templates.create', 'label' => 'ایجاد قالب گواهینامه'],
            ['name' => 'certificates.templates.update', 'label' => 'ویرایش قالب گواهینامه'],
            ['name' => 'certificates.templates.delete', 'label' => 'حذف قالب گواهینامه'],
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
                'certificates.view',
                'certificates.templates.view',
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
