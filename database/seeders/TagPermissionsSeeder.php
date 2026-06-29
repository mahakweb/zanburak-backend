<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $permissions = [
            ['name' => 'tags.view', 'label' => 'مشاهده تگ‌ها'],
            ['name' => 'tags.create', 'label' => 'ایجاد تگ'],
            ['name' => 'tags.update', 'label' => 'ویرایش تگ'],
            ['name' => 'tags.delete', 'label' => 'حذف تگ'],
            ['name' => 'tags.merge', 'label' => 'ادغام تگ‌ها'],
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
            'admin' => $permNames,
            'content_admin' => $permNames,
            'community_manager' => $permNames,
            'community_viewer' => ['tags.view'],
            'moderator' => ['tags.view', 'tags.update'],
        ];

        $rows = [];
        foreach ($rolePermissionMap as $roleName => $names) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if (!$role) {
                continue;
            }

            foreach ($names as $name) {
                if (!isset($permIds[$name])) {
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
            DB::table('permission_role')->upsert(
                $rows,
                ['role_id', 'permission_id'],
                ['updated_at']
            );
        }
    }
}
