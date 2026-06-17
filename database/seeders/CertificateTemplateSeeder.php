<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Support\Certificate\CertificateConstants;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        CertificateTemplate::firstOrCreate(
            ['slug' => 'default-classic'],
            [
                'name' => 'قالب کلاسیک',
                'description' => 'قالب پیش‌فرض گواهینامه با حاشیه طلایی',
                'orientation' => 'landscape',
                'layout' => CertificateConstants::DEFAULT_LAYOUT,
                'settings' => CertificateConstants::DEFAULT_SETTINGS,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        CertificateTemplate::firstOrCreate(
            ['slug' => 'modern-minimal'],
            [
                'name' => 'قالب مدرن',
                'description' => 'طراحی مینیمال با تاکید بر نام دانشجو',
                'orientation' => 'landscape',
                'layout' => array_merge(CertificateConstants::DEFAULT_LAYOUT, [
                    'student_name' => ['x' => 50, 'y' => 45, 'font_size' => 42, 'align' => 'center', 'color' => '#111827'],
                ]),
                'settings' => array_merge(CertificateConstants::DEFAULT_SETTINGS, [
                    'primary_color' => '#6366f1',
                ]),
                'is_default' => false,
                'is_active' => true,
            ]
        );
    }
}
