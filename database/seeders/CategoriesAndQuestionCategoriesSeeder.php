<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesAndQuestionCategoriesSeeder extends Seeder
{
    /**
     * Explicit-ID upserts do not advance PostgreSQL serial sequences.
     */
    private function syncPostgresSequence(string $table, string $column = 'id'): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', '{$column}'), COALESCE((SELECT MAX({$column}) FROM {$table}), 1), true)"
        );
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // Categories (دسته‌بندی‌های اصلی دوره‌ها)
        $categories = [
            // والدین
            [
                'id' => 1,
                'title' => 'برنامه‌نویسی وب',
                'english_title' => 'web-programming',
                'slug' => 'web-programming',
                'parent_id' => null,
                'description' => 'یادگیری برنامه‌نویسی برای وب شامل فرانت‌اند و بک‌اند',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'title' => 'برنامه‌نویسی موبایل',
                'english_title' => 'mobile-programming',
                'slug' => 'mobile-programming',
                'parent_id' => null,
                'description' => 'توسعه اپلیکیشن‌های موبایل برای iOS و Android',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'title' => 'برنامه‌نویسی دسکتاپ',
                'english_title' => 'desktop-programming',
                'slug' => 'desktop-programming',
                'parent_id' => null,
                'description' => 'توسعه نرم‌افزارهای دسکتاپ',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'title' => 'علوم داده و هوش مصنوعی',
                'english_title' => 'data-science-ai',
                'slug' => 'data-science-ai',
                'parent_id' => null,
                'description' => 'یادگیری علوم داده، ماشین لرنینگ و هوش مصنوعی',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // فرزندان دسته‌بندی وب
        $webChildren = [
            [
                'title' => 'فرانت‌اند',
                'english_title' => 'frontend',
                'slug' => 'frontend',
                'parent_id' => 1,
                'description' => 'یادگیری HTML, CSS, JavaScript و فریمورک‌های فرانت‌اند',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'بک‌اند',
                'english_title' => 'backend',
                'slug' => 'backend',
                'parent_id' => 1,
                'description' => 'یادگیری سرور، دیتابیس و API',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'React',
                'english_title' => 'react',
                'slug' => 'react',
                'parent_id' => 1,
                'description' => 'کتابخانه محبوب جاوااسکریپت برای ساخت رابط کاربری',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Vue.js',
                'english_title' => 'vuejs',
                'slug' => 'vuejs',
                'parent_id' => 1,
                'description' => 'فریمورک پیشرفته جاوااسکریپت برای ساخت رابط کاربری',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Laravel',
                'english_title' => 'laravel',
                'slug' => 'laravel',
                'parent_id' => 1,
                'description' => 'فریمورک قدرتمند PHP برای توسعه وب',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Node.js',
                'english_title' => 'nodejs',
                'slug' => 'nodejs',
                'parent_id' => 1,
                'description' => 'اجرای جاوااسکریپت در سمت سرور',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // فرزندان دسته‌بندی موبایل
        $mobileChildren = [
            [
                'title' => 'React Native',
                'english_title' => 'react-native',
                'slug' => 'react-native',
                'parent_id' => 2,
                'description' => 'توسعه اپلیکیشن موبایل با React',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Flutter',
                'english_title' => 'flutter',
                'slug' => 'flutter',
                'parent_id' => 2,
                'description' => 'فریمورک گوگل برای توسعه اپلیکیشن‌های موبایل',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Android',
                'english_title' => 'android',
                'slug' => 'android',
                'parent_id' => 2,
                'description' => 'توسعه اپلیکیشن Android با Kotlin و Java',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'iOS',
                'english_title' => 'ios',
                'slug' => 'ios',
                'parent_id' => 2,
                'description' => 'توسعه اپلیکیشن iOS با Swift',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // فرزندان علوم داده
        $dataScienceChildren = [
            [
                'title' => 'Python',
                'english_title' => 'python',
                'slug' => 'python',
                'parent_id' => 4,
                'description' => 'زبان برنامه‌نویسی محبوب برای علوم داده',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Machine Learning',
                'english_title' => 'machine-learning',
                'slug' => 'machine-learning',
                'parent_id' => 4,
                'description' => 'یادگیری ماشین و الگوریتم‌های آن',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Deep Learning',
                'english_title' => 'deep-learning',
                'slug' => 'deep-learning',
                'parent_id' => 4,
                'description' => 'یادگیری عمیق و شبکه‌های عصبی',
                'icon' => null,
                'status' => 1,
                'assignment_type' => 'manual',
                'match_type' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert categories
        DB::table('categories')->upsert($categories, ['id'], ['title', 'english_title', 'slug', 'parent_id', 'description', 'icon', 'status', 'assignment_type', 'match_type', 'updated_at']);
        $this->syncPostgresSequence('categories');
        DB::table('categories')->upsert($webChildren, ['slug'], ['title', 'parent_id', 'description', 'icon', 'status', 'assignment_type', 'match_type', 'updated_at']);
        DB::table('categories')->upsert($mobileChildren, ['slug'], ['title', 'parent_id', 'description', 'icon', 'status', 'assignment_type', 'match_type', 'updated_at']);
        DB::table('categories')->upsert($dataScienceChildren, ['slug'], ['title', 'parent_id', 'description', 'icon', 'status', 'assignment_type', 'match_type', 'updated_at']);

        // Question Categories (دسته‌بندی‌های پرسش‌ها)
        $questionCategories = [
            // والدین
            [
                'id' => 1,
                'title' => 'برنامه‌نویسی عمومی',
                'english_title' => 'general-programming',
                'slug' => 'general-programming',
                'parent_id' => null,
                'description' => 'سوالات عمومی در مورد برنامه‌نویسی',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'title' => 'مشکلات کدنویسی',
                'english_title' => 'coding-issues',
                'slug' => 'coding-issues',
                'parent_id' => null,
                'description' => 'سوالات مربوط به رفع باگ و مشکلات کدنویسی',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'title' => 'ابزارها و تکنولوژی',
                'english_title' => 'tools-technology',
                'slug' => 'tools-technology',
                'parent_id' => null,
                'description' => 'سوالات در مورد ابزارها و تکنولوژی‌های برنامه‌نویسی',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'title' => 'شغل و حرفه',
                'english_title' => 'career',
                'slug' => 'career',
                'parent_id' => null,
                'description' => 'سوالات مربوط به شغل و حرفه برنامه‌نویسی',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // فرزندان دسته‌بندی پرسش‌ها
        $questionChildren = [
            [
                'title' => 'الگوریتم و ساختار داده',
                'english_title' => 'algorithms-data-structures',
                'slug' => 'algorithms-data-structures',
                'parent_id' => 1,
                'description' => 'سوالات مربوط به الگوریتم‌ها و ساختار داده',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'بهینه‌سازی کد',
                'english_title' => 'code-optimization',
                'slug' => 'code-optimization',
                'parent_id' => 1,
                'description' => 'سوالات مربوط به بهینه‌سازی و بهبود عملکرد کد',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'رفع خطا',
                'english_title' => 'debugging',
                'slug' => 'debugging',
                'parent_id' => 2,
                'description' => 'سوالات مربوط به رفع خطا و دیباگ',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مشکلات نصب و راه‌اندازی',
                'english_title' => 'installation-setup',
                'slug' => 'installation-setup',
                'parent_id' => 2,
                'description' => 'سوالات مربوط به نصب و راه‌اندازی ابزارها',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'IDE و ویرایشگرها',
                'english_title' => 'ide-editors',
                'slug' => 'ide-editors',
                'parent_id' => 3,
                'description' => 'سوالات مربوط به IDE و ویرایشگرهای کد',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Git و Version Control',
                'english_title' => 'git-version-control',
                'slug' => 'git-version-control',
                'parent_id' => 3,
                'description' => 'سوالات مربوط به Git و کنترل نسخه',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مصاحبه شغلی',
                'english_title' => 'job-interview',
                'slug' => 'job-interview',
                'parent_id' => 4,
                'description' => 'سوالات مربوط به مصاحبه‌های شغلی',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'رزومه و پورتفولیو',
                'english_title' => 'resume-portfolio',
                'slug' => 'resume-portfolio',
                'parent_id' => 4,
                'description' => 'سوالات مربوط به ساخت رزومه و پورتفولیو',
                'icon' => null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert question categories
        DB::table('question_categories')->upsert($questionCategories, ['id'], ['title', 'english_title', 'slug', 'parent_id', 'description', 'icon', 'status', 'updated_at']);
        $this->syncPostgresSequence('question_categories');
        DB::table('question_categories')->upsert($questionChildren, ['slug'], ['title', 'parent_id', 'description', 'icon', 'status', 'updated_at']);
    }
}

