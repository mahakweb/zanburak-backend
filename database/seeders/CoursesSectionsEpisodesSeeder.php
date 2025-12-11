<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesSectionsEpisodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // دریافت ID های مورد نیاز
        $teacherId = DB::table('users')->where('username', 'teacher')->value('id');
        $beginnerLevelId = DB::table('levels')->where('english_title', 'beginner')->value('id');
        $intermediateLevelId = DB::table('levels')->where('english_title', 'intermediate')->value('id');
        $advancedLevelId = DB::table('levels')->where('english_title', 'advanced')->value('id');
        $completedStatusId = DB::table('statuses')->where('english_title', 'completed')->value('id');
        $ongoingStatusId = DB::table('statuses')->where('english_title', 'ongoing')->value('id');

        // دریافت دسته‌بندی‌ها
        $reactCategoryId = DB::table('categories')->where('slug', 'react')->value('id');
        $laravelCategoryId = DB::table('categories')->where('slug', 'laravel')->value('id');
        $javascriptCategoryId = DB::table('categories')->where('slug', 'javascript')->value('id');
        $nodejsCategoryId = DB::table('categories')->where('slug', 'nodejs')->value('id');
        $frontendCategoryId = DB::table('categories')->where('slug', 'frontend')->value('id');
        $backendCategoryId = DB::table('categories')->where('slug', 'backend')->value('id');

        // اگر teacher وجود نداشت، از اولین کاربر استفاده کن
        if (!$teacherId) {
            $teacherId = DB::table('users')->where('is_staff', true)->value('id') ?? 1;
        }

        // دوره 1: آموزش React از صفر
        $course1Id = DB::table('courses')->insertGetId([
            'teacher_id' => $teacherId,
            'title' => 'آموزش کامل React از صفر تا صد',
            'english_title' => 'complete-react-course-from-zero',
            'slug' => 'complete-react-course-from-zero',
            'short_description' => 'یادگیری React از مبانی تا ساخت پروژه‌های حرفه‌ای',
            'description' => 'در این دوره جامع، شما React را از صفر یاد می‌گیرید. از نصب و راه‌اندازی تا ساخت کامپوننت‌های پیچیده و مدیریت state با Redux. این دوره شامل پروژه‌های عملی و تمرین‌های متنوع است.',
            'total_time' => '1800',
            'start_date' => $now,
            'price' => 499000,
            'publish' => true,
            'status_id' => $completedStatusId,
            'level_id' => $beginnerLevelId,
            'type' => 'cash',
            'poster' => 'https://static.zanburak.ir/poster/no-image.png',
            'trailer' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // اتصال دوره به دسته‌بندی React
        if ($reactCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $reactCategoryId,
                'course_id' => $course1Id,
            ]);
        }
        if ($frontendCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $frontendCategoryId,
                'course_id' => $course1Id,
            ]);
        }

        // سکشن‌های دوره React
        $section1_1Id = DB::table('sections')->insertGetId([
            'course_id' => $course1Id,
            'title' => 'مقدمات و نصب',
            'english_title' => 'introduction-and-setup',
            'slug' => 'introduction-and-setup',
            'description' => 'آشنایی با React و راه‌اندازی محیط توسعه',
            'total_time' => '120',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $section1_2Id = DB::table('sections')->insertGetId([
            'course_id' => $course1Id,
            'title' => 'کامپوننت‌ها و Props',
            'english_title' => 'components-and-props',
            'slug' => 'components-and-props',
            'description' => 'یادگیری ساخت و استفاده از کامپوننت‌ها',
            'total_time' => '180',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $section1_3Id = DB::table('sections')->insertGetId([
            'course_id' => $course1Id,
            'title' => 'State و Hooks',
            'english_title' => 'state-and-hooks',
            'slug' => 'state-and-hooks',
            'description' => 'مدیریت state با useState و useEffect',
            'total_time' => '240',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // اپیزودهای سکشن اول
        $episodes1_1 = [
            ['section_id' => $section1_1Id, 'order' => 1, 'title' => 'معرفی React', 'english_title' => 'react-introduction', 'slug' => 'react-introduction', 'description' => 'آشنایی با React و تاریخچه آن', 'total_time' => '15', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_1Id, 'order' => 2, 'title' => 'نصب Node.js و npm', 'english_title' => 'install-nodejs-npm', 'slug' => 'install-nodejs-npm', 'description' => 'راه‌اندازی محیط توسعه', 'total_time' => '20', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_1Id, 'order' => 3, 'title' => 'ایجاد پروژه با Create React App', 'english_title' => 'create-react-app', 'slug' => 'create-react-app', 'description' => 'ساخت اولین پروژه React', 'total_time' => '25', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_1Id, 'order' => 4, 'title' => 'ساختار پروژه React', 'english_title' => 'react-project-structure', 'slug' => 'react-project-structure', 'description' => 'بررسی فایل‌ها و پوشه‌های پروژه', 'total_time' => '30', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        // اپیزودهای سکشن دوم
        $episodes1_2 = [
            ['section_id' => $section1_2Id, 'order' => 1, 'title' => 'ساخت اولین کامپوننت', 'english_title' => 'create-first-component', 'slug' => 'create-first-component', 'description' => 'نوشتن اولین کامپوننت React', 'total_time' => '20', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_2Id, 'order' => 2, 'title' => 'استفاده از Props', 'english_title' => 'using-props', 'slug' => 'using-props', 'description' => 'ارسال داده به کامپوننت‌ها', 'total_time' => '30', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_2Id, 'order' => 3, 'title' => 'کامپوننت‌های تابعی و کلاسی', 'english_title' => 'functional-class-components', 'slug' => 'functional-class-components', 'description' => 'مقایسه کامپوننت‌های تابعی و کلاسی', 'total_time' => '35', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        // اپیزودهای سکشن سوم
        $episodes1_3 = [
            ['section_id' => $section1_3Id, 'order' => 1, 'title' => 'useState Hook', 'english_title' => 'usestate-hook', 'slug' => 'usestate-hook', 'description' => 'مدیریت state با useState', 'total_time' => '40', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_3Id, 'order' => 2, 'title' => 'useEffect Hook', 'english_title' => 'useeffect-hook', 'slug' => 'useeffect-hook', 'description' => 'انجام side effects با useEffect', 'total_time' => '45', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section1_3Id, 'order' => 3, 'title' => 'Hooks های دیگر', 'english_title' => 'other-hooks', 'slug' => 'other-hooks', 'description' => 'useContext, useReducer و سایر Hooks', 'total_time' => '50', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        foreach (array_merge($episodes1_1, $episodes1_2, $episodes1_3) as $episode) {
            $episode['created_at'] = $now;
            $episode['updated_at'] = $now;
            DB::table('episodes')->insert($episode);
        }

        // دوره 2: آموزش Laravel
        $course2Id = DB::table('courses')->insertGetId([
            'teacher_id' => $teacherId,
            'title' => 'آموزش Laravel - از مبتدی تا حرفه‌ای',
            'english_title' => 'laravel-course-beginner-to-advanced',
            'slug' => 'laravel-course-beginner-to-advanced',
            'short_description' => 'یادگیری Laravel برای ساخت اپلیکیشن‌های وب حرفه‌ای',
            'description' => 'در این دوره Laravel را به صورت کامل یاد می‌گیرید. از نصب و راه‌اندازی تا ساخت API، Authentication، و استفاده از Eloquent ORM. این دوره شامل پروژه‌های عملی است.',
            'total_time' => '2400',
            'start_date' => $now,
            'price' => 699000,
            'publish' => true,
            'status_id' => $completedStatusId,
            'level_id' => $intermediateLevelId,
            'type' => 'cash',
            'poster' => 'https://static.zanburak.ir/poster/no-image.png',
            'trailer' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($laravelCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $laravelCategoryId,
                'course_id' => $course2Id,
            ]);
        }
        if ($backendCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $backendCategoryId,
                'course_id' => $course2Id,
            ]);
        }

        // سکشن‌های دوره Laravel
        $section2_1Id = DB::table('sections')->insertGetId([
            'course_id' => $course2Id,
            'title' => 'مقدمات Laravel',
            'english_title' => 'laravel-basics',
            'slug' => 'laravel-basics',
            'description' => 'آشنایی با Laravel و معماری MVC',
            'total_time' => '180',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $section2_2Id = DB::table('sections')->insertGetId([
            'course_id' => $course2Id,
            'title' => 'Eloquent ORM',
            'english_title' => 'eloquent-orm',
            'slug' => 'eloquent-orm',
            'description' => 'کار با دیتابیس با Eloquent',
            'total_time' => '240',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $section2_3Id = DB::table('sections')->insertGetId([
            'course_id' => $course2Id,
            'title' => 'Authentication و Authorization',
            'english_title' => 'authentication-authorization',
            'slug' => 'authentication-authorization',
            'description' => 'پیاده‌سازی سیستم احراز هویت',
            'total_time' => '200',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // اپیزودهای دوره Laravel
        $episodes2_1 = [
            ['section_id' => $section2_1Id, 'order' => 1, 'title' => 'نصب Laravel', 'english_title' => 'install-laravel', 'slug' => 'install-laravel', 'description' => 'راه‌اندازی Laravel با Composer', 'total_time' => '25', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section2_1Id, 'order' => 2, 'title' => 'ساختار پروژه Laravel', 'english_title' => 'laravel-project-structure', 'slug' => 'laravel-project-structure', 'description' => 'بررسی پوشه‌ها و فایل‌های Laravel', 'total_time' => '30', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section2_1Id, 'order' => 3, 'title' => 'Routing در Laravel', 'english_title' => 'laravel-routing', 'slug' => 'laravel-routing', 'description' => 'تعریف Route ها و Controller ها', 'total_time' => '35', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        $episodes2_2 = [
            ['section_id' => $section2_2Id, 'order' => 1, 'title' => 'مقدمات Eloquent', 'english_title' => 'eloquent-basics', 'slug' => 'eloquent-basics', 'description' => 'آشنایی با Eloquent ORM', 'total_time' => '40', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section2_2Id, 'order' => 2, 'title' => 'Relationships', 'english_title' => 'eloquent-relationships', 'slug' => 'eloquent-relationships', 'description' => 'تعریف روابط بین Model ها', 'total_time' => '50', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        $episodes2_3 = [
            ['section_id' => $section2_3Id, 'order' => 1, 'title' => 'Laravel Breeze', 'english_title' => 'laravel-breeze', 'slug' => 'laravel-breeze', 'description' => 'استفاده از Laravel Breeze برای Authentication', 'total_time' => '45', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section2_3Id, 'order' => 2, 'title' => 'Policies و Gates', 'english_title' => 'policies-gates', 'slug' => 'policies-gates', 'description' => 'مدیریت دسترسی‌ها با Policies', 'total_time' => '50', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        foreach (array_merge($episodes2_1, $episodes2_2, $episodes2_3) as $episode) {
            $episode['created_at'] = $now;
            $episode['updated_at'] = $now;
            DB::table('episodes')->insert($episode);
        }

        // دوره 3: JavaScript پیشرفته
        $course3Id = DB::table('courses')->insertGetId([
            'teacher_id' => $teacherId,
            'title' => 'JavaScript پیشرفته و ES6+',
            'english_title' => 'advanced-javascript-es6',
            'slug' => 'advanced-javascript-es6',
            'short_description' => 'یادگیری ویژگی‌های پیشرفته JavaScript و ES6',
            'description' => 'در این دوره ویژگی‌های پیشرفته JavaScript را یاد می‌گیرید. از ES6 تا Async/Await، Promises، و الگوهای پیشرفته برنامه‌نویسی.',
            'total_time' => '1500',
            'start_date' => $now,
            'price' => 399000,
            'publish' => true,
            'status_id' => $ongoingStatusId,
            'level_id' => $intermediateLevelId,
            'type' => 'cash',
            'poster' => 'https://static.zanburak.ir/poster/no-image.png',
            'trailer' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($javascriptCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $javascriptCategoryId,
                'course_id' => $course3Id,
            ]);
        }

        // سکشن‌های دوره JavaScript
        $section3_1Id = DB::table('sections')->insertGetId([
            'course_id' => $course3Id,
            'title' => 'ES6 Features',
            'english_title' => 'es6-features',
            'slug' => 'es6-features',
            'description' => 'ویژگی‌های جدید ES6',
            'total_time' => '200',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $section3_2Id = DB::table('sections')->insertGetId([
            'course_id' => $course3Id,
            'title' => 'Async Programming',
            'english_title' => 'async-programming',
            'slug' => 'async-programming',
            'description' => 'برنامه‌نویسی ناهمگام با Promises و Async/Await',
            'total_time' => '180',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // اپیزودهای دوره JavaScript
        $episodes3_1 = [
            ['section_id' => $section3_1Id, 'order' => 1, 'title' => 'Arrow Functions', 'english_title' => 'arrow-functions', 'slug' => 'arrow-functions', 'description' => 'استفاده از Arrow Functions', 'total_time' => '20', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section3_1Id, 'order' => 2, 'title' => 'Destructuring', 'english_title' => 'destructuring', 'slug' => 'destructuring', 'description' => 'Destructuring در JavaScript', 'total_time' => '25', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section3_1Id, 'order' => 3, 'title' => 'Template Literals', 'english_title' => 'template-literals', 'slug' => 'template-literals', 'description' => 'استفاده از Template Literals', 'total_time' => '15', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        $episodes3_2 = [
            ['section_id' => $section3_2Id, 'order' => 1, 'title' => 'Promises', 'english_title' => 'promises', 'slug' => 'promises', 'description' => 'کار با Promises', 'total_time' => '40', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section3_2Id, 'order' => 2, 'title' => 'Async/Await', 'english_title' => 'async-await', 'slug' => 'async-await', 'description' => 'استفاده از Async/Await', 'total_time' => '35', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        foreach (array_merge($episodes3_1, $episodes3_2) as $episode) {
            $episode['created_at'] = $now;
            $episode['updated_at'] = $now;
            DB::table('episodes')->insert($episode);
        }

        // دوره 4: Node.js
        $course4Id = DB::table('courses')->insertGetId([
            'teacher_id' => $teacherId,
            'title' => 'توسعه Backend با Node.js',
            'english_title' => 'backend-development-nodejs',
            'slug' => 'backend-development-nodejs',
            'short_description' => 'ساخت API و سرور با Node.js و Express',
            'description' => 'یادگیری ساخت Backend با Node.js، Express، و MongoDB. این دوره شامل ساخت RESTful API و WebSocket است.',
            'total_time' => '2000',
            'start_date' => $now,
            'price' => 599000,
            'publish' => true,
            'status_id' => $completedStatusId,
            'level_id' => $advancedLevelId,
            'type' => 'cash-vip',
            'poster' => 'https://static.zanburak.ir/poster/no-image.png',
            'trailer' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($nodejsCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $nodejsCategoryId,
                'course_id' => $course4Id,
            ]);
        }
        if ($backendCategoryId) {
            DB::table('category_course')->insert([
                'category_id' => $backendCategoryId,
                'course_id' => $course4Id,
            ]);
        }

        // سکشن‌های دوره Node.js
        $section4_1Id = DB::table('sections')->insertGetId([
            'course_id' => $course4Id,
            'title' => 'مقدمات Node.js',
            'english_title' => 'nodejs-basics',
            'slug' => 'nodejs-basics',
            'description' => 'آشنایی با Node.js و npm',
            'total_time' => '150',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $section4_2Id = DB::table('sections')->insertGetId([
            'course_id' => $course4Id,
            'title' => 'Express.js',
            'english_title' => 'expressjs',
            'slug' => 'expressjs',
            'description' => 'ساخت API با Express',
            'total_time' => '250',
            'publish' => true,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // اپیزودهای دوره Node.js
        $episodes4_1 = [
            ['section_id' => $section4_1Id, 'order' => 1, 'title' => 'نصب Node.js', 'english_title' => 'install-nodejs', 'slug' => 'install-nodejs', 'description' => 'راه‌اندازی Node.js', 'total_time' => '20', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section4_1Id, 'order' => 2, 'title' => 'ماژول‌ها در Node.js', 'english_title' => 'nodejs-modules', 'slug' => 'nodejs-modules', 'description' => 'کار با ماژول‌ها', 'total_time' => '30', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        $episodes4_2 = [
            ['section_id' => $section4_2Id, 'order' => 1, 'title' => 'نصب Express', 'english_title' => 'install-express', 'slug' => 'install-express', 'description' => 'راه‌اندازی Express', 'total_time' => '25', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section4_2Id, 'order' => 2, 'title' => 'Routing در Express', 'english_title' => 'express-routing', 'slug' => 'express-routing', 'description' => 'تعریف Route ها', 'total_time' => '35', 'publish' => true, 'lock' => false, 'publish_date' => $now],
            ['section_id' => $section4_2Id, 'order' => 3, 'title' => 'Middleware', 'english_title' => 'express-middleware', 'slug' => 'express-middleware', 'description' => 'استفاده از Middleware', 'total_time' => '40', 'publish' => true, 'lock' => false, 'publish_date' => $now],
        ];

        foreach (array_merge($episodes4_1, $episodes4_2) as $episode) {
            $episode['created_at'] = $now;
            $episode['updated_at'] = $now;
            DB::table('episodes')->insert($episode);
        }
    }
}

