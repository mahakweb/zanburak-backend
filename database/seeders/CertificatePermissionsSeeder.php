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
            ['name' => 'certificates.view.own', 'label' => 'مشاهده گواهینامه‌های مرتبط با خود'],
            ['name' => 'certificates.view.any', 'label' => 'مشاهده همه گواهینامه‌ها'],
            ['name' => 'certificates.create', 'label' => 'صدور گواهینامه'],
            ['name' => 'certificates.create.own', 'label' => 'صدور گواهینامه برای دوره‌های خود'],
            ['name' => 'certificates.create.any', 'label' => 'صدور گواهینامه برای هر دوره‌ای'],
            ['name' => 'certificates.update', 'label' => 'ویرایش گواهینامه'],
            ['name' => 'certificates.update.own', 'label' => 'ویرایش گواهینامه‌های مرتبط با خود'],
            ['name' => 'certificates.update.any', 'label' => 'ویرایش هر گواهینامه‌ای'],
            ['name' => 'certificates.delete', 'label' => 'حذف/ابطال گواهینامه'],
            ['name' => 'certificates.delete.own', 'label' => 'حذف/ابطال گواهینامه‌های مرتبط با خود'],
            ['name' => 'certificates.delete.any', 'label' => 'حذف/ابطال هر گواهینامه‌ای'],
            ['name' => 'certificates.export', 'label' => 'خروجی گواهینامه‌ها'],
            ['name' => 'certificates.export.own', 'label' => 'خروجی گواهینامه‌های مرتبط با خود'],
            ['name' => 'certificates.export.any', 'label' => 'خروجی همه گواهینامه‌ها'],
            ['name' => 'certificates.templates.view', 'label' => 'مشاهده قالب گواهینامه'],
            ['name' => 'certificates.templates.view.any', 'label' => 'مشاهده همه قالب‌های گواهینامه'],
            ['name' => 'certificates.templates.create', 'label' => 'ایجاد قالب گواهینامه'],
            ['name' => 'certificates.templates.create.any', 'label' => 'ایجاد قالب گواهینامه (همه)'],
            ['name' => 'certificates.templates.update', 'label' => 'ویرایش قالب گواهینامه'],
            ['name' => 'certificates.templates.update.any', 'label' => 'ویرایش هر قالب گواهینامه'],
            ['name' => 'certificates.templates.delete', 'label' => 'حذف قالب گواهینامه'],
            ['name' => 'certificates.templates.delete.any', 'label' => 'حذف هر قالب گواهینامه'],
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
