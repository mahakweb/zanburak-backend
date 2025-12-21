<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // ROLES
        $roles = [
            ['name' => 'administrator', 'label' => 'مدیرکل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'user_admin', 'label' => 'مدیر کل کاربران', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'user_manager', 'label' => 'مدیر کاربران', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'user_viewer', 'label' => 'مشاهده‌کننده کاربران', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'user_support', 'label' => 'پشتیبان کاربران', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'content_admin', 'label' => 'مدیر محتوا', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'course_manager', 'label' => 'مدیر دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'community_manager', 'label' => 'مدیر انجمن/نظرات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'finance_manager', 'label' => 'مدیر مالی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discount_manager', 'label' => 'مدیر تخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'plan_manager', 'label' => 'مدیر پلن', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'support', 'label' => 'پشتیبان', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'moderator', 'label' => 'ناظر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'file_manager', 'label' => 'مدیر فایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'notification_manager', 'label' => 'مدیر اعلان', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat_moderator', 'label' => 'ناظر گفتگو', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing_manager', 'label' => 'مدیر مارکتینگ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo_manager', 'label' => 'مدیر SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'analytics_viewer', 'label' => 'بیننده آمار', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'admin', 'label' => 'مدیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'teacher', 'label' => 'مدرس', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'student', 'label' => 'دانشجو', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'course_viewer_all', 'label' => 'مشاهده‌گر همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'course_manager_all', 'label' => 'مدیر همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'course_manager_own', 'label' => 'مدیر دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'teacher_finance_viewer', 'label' => 'مشاهده مالی مدرس (پرداخت‌های مرتبط)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments_moderator_all', 'label' => 'مدیر نظرات (کامل)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments_moderator_own', 'label' => 'مدیر نظرات (دوره‌های خود)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'catalog_manager', 'label' => 'مدیر کاتالوگ (دسته/سطح/وضعیت/مسیر)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'catalog_viewer', 'label' => 'مشاهده‌گر کاتالوگ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'category_manager', 'label' => 'مدیر دسته‌بندی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'level_manager', 'label' => 'مدیر سطح', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'status_manager', 'label' => 'مدیر وضعیت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'path_manager', 'label' => 'مدیر مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'article_viewer_all', 'label' => 'مشاهده‌گر همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'article_manager_all', 'label' => 'مدیر همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'article_manager_own', 'label' => 'مدیر مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'admin_auditor', 'label' => 'ممیز ادمین (فقط مشاهده)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'content_publisher', 'label' => 'انتشاردهنده محتوا', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'video_operator', 'label' => 'اپراتور ویدیو', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'support_lite', 'label' => 'پشتیبان سبک', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'teacher_assistant', 'label' => 'دستیار مدرس', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discount_analyst', 'label' => 'تحلیلگر تخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments_auditor', 'label' => 'ممیز پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments_operator', 'label' => 'اپراتور پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat_viewer', 'label' => 'مشاهده‌گر چت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'community_viewer', 'label' => 'مشاهده‌گر انجمن/نظرات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'editor_uploader', 'label' => 'آپلودر ادیتور', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing_viewer', 'label' => 'مشاهده‌گر مارکتینگ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing_editor', 'label' => 'ویرایشگر مارکتینگ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo_viewer', 'label' => 'مشاهده‌گر SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo_editor', 'label' => 'ویرایشگر SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'security_admin', 'label' => 'مدیر امنیت/دسترسی‌ها', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('roles')->upsert($roles, ['name'], ['label', 'updated_at']);

        // PERMISSIONS
        $permissions = [
            // Dashboard/Admin
            ['name' => 'dashboard.view', 'label' => 'مشاهده داشبورد ادمین', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'analytics.view', 'label' => 'مشاهده آمار/گزارش‌ها', 'created_at' => $now, 'updated_at' => $now],

            // Users/Security
            ['name' => 'users.view', 'label' => 'مشاهده کاربران', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.create', 'label' => 'ایجاد کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.update', 'label' => 'ویرایش کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.deactivate', 'label' => 'غیرفعال‌سازی کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.reactivate', 'label' => 'فعال‌سازی مجدد کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.reset_password', 'label' => 'ریست پسورد کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.manage_roles', 'label' => 'مدیریت نقش‌های کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.manage_permissions', 'label' => 'مدیریت دسترسی‌های کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.impersonate', 'label' => 'لاگین به‌جای کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.sessions.view', 'label' => 'مشاهده نشست‌های کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.sessions.terminate', 'label' => 'خاتمه نشست‌های کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'admin.search_user', 'label' => 'جستجوی کاربر در ادمین', 'created_at' => $now, 'updated_at' => $now],

            // Courses
            ['name' => 'courses.view', 'label' => 'مشاهده دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.list', 'label' => 'لیست دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.create', 'label' => 'ایجاد دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.update', 'label' => 'ویرایش دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.delete', 'label' => 'حذف دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.publish', 'label' => 'انتشار دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.unpublish', 'label' => 'عدم انتشار دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.assign_user', 'label' => 'اختصاص دوره به کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.reorder_episodes', 'label' => 'مرتب‌سازی قسمت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.view', 'label' => 'مشاهده خلاصه دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.update', 'label' => 'ویرایش خلاصه دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.view', 'label' => 'مشاهده نظرات دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.view.own', 'label' => 'مشاهده دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.view.any', 'label' => 'مشاهده همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.list.any', 'label' => 'لیست همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.update.own', 'label' => 'ویرایش دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.update.any', 'label' => 'ویرایش هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.delete.own', 'label' => 'حذف دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.delete.any', 'label' => 'حذف هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.publish.own', 'label' => 'انتشار/عدم انتشار دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.publish.any', 'label' => 'انتشار/عدم انتشار هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.assign_user.own', 'label' => 'اختصاص دوره‌های خود به کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.assign_user.any', 'label' => 'اختصاص هر دوره‌ای به کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.reorder_episodes.own', 'label' => 'مرتب‌سازی قسمت‌های دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.reorder_episodes.any', 'label' => 'مرتب‌سازی قسمت‌های هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.manage', 'label' => 'مدیریت نظرات دوره', 'created_at' => $now, 'updated_at' => $now],

            // Episodes
            ['name' => 'episodes.create', 'label' => 'ایجاد جلسه/قسمت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.edit', 'label' => 'ویرایش جلسه/قسمت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.delete', 'label' => 'حذف جلسه/قسمت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.upload_file', 'label' => 'آپلود فایل جلسه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.status', 'label' => 'تغییر وضعیت جلسه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.get_for_edit', 'label' => 'دریافت جلسه برای ویرایش', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.reorder', 'label' => 'مرتب‌سازی جلسات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.create.own', 'label' => 'ایجاد جلسه برای دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.create.any', 'label' => 'ایجاد جلسه برای هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.edit.own', 'label' => 'ویرایش جلسه دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.edit.any', 'label' => 'ویرایش جلسه هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.delete.own', 'label' => 'حذف جلسه دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.delete.any', 'label' => 'حذف جلسه هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.reorder.own', 'label' => 'مرتب‌سازی جلسات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.reorder.any', 'label' => 'مرتب‌سازی جلسات هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],

            // Videos
            ['name' => 'videos.upload', 'label' => 'آپلود ویدیو', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.process', 'label' => 'پردازش ویدیو', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.key.view', 'label' => 'دریافت کلید ویدیو', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.playlist.view', 'label' => 'مشاهده پلی‌لیست', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.download.signed', 'label' => 'دانلود امضاشده', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.upload.own', 'label' => 'آپلود ویدیو برای دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.upload.any', 'label' => 'آپلود ویدیو برای هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.process.own', 'label' => 'پردازش ویدیو دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videos.process.any', 'label' => 'پردازش ویدیو هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],

            // Categories / Levels / Statuses
            ['name' => 'categories.view', 'label' => 'مشاهده دسته‌بندی‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'categories.create', 'label' => 'ایجاد دسته‌بندی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'categories.edit', 'label' => 'ویرایش دسته‌بندی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'categories.delete', 'label' => 'حذف دسته‌بندی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'categories.upload_icon', 'label' => 'آپلود آیکون دسته‌بندی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'levels.view', 'label' => 'مشاهده سطح‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'levels.create', 'label' => 'ایجاد سطح', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'levels.update', 'label' => 'ویرایش سطح', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'levels.delete', 'label' => 'حذف سطح', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'statuses.view', 'label' => 'مشاهده وضعیت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'statuses.create', 'label' => 'ایجاد وضعیت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'statuses.update', 'label' => 'ویرایش وضعیت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'statuses.delete', 'label' => 'حذف وضعیت', 'created_at' => $now, 'updated_at' => $now],

            // Paths
            ['name' => 'paths.view', 'label' => 'مشاهده مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.create', 'label' => 'ایجاد مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.edit', 'label' => 'ویرایش مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.update', 'label' => 'به‌روزرسانی مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.delete', 'label' => 'حذف مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.remove_file', 'label' => 'حذف فایل مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.upload.poster', 'label' => 'آپلود پوستر مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.upload.icon', 'label' => 'آپلود آیکون مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.upload.trailer', 'label' => 'آپلود تریلر مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.search.courses', 'label' => 'جستجوی دوره‌ها برای مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.search.paths', 'label' => 'جستجوی مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.courses.attach', 'label' => 'افزودن دوره به مسیر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'paths.courses.detach', 'label' => 'حذف دوره از مسیر', 'created_at' => $now, 'updated_at' => $now],

            // Plans
            ['name' => 'plans.view', 'label' => 'مشاهده پلن‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'plans.create', 'label' => 'ایجاد پلن', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'plans.show', 'label' => 'نمایش پلن', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'plans.update', 'label' => 'ویرایش پلن', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'plans.delete', 'label' => 'حذف پلن', 'created_at' => $now, 'updated_at' => $now],

            // Articles
            ['name' => 'articles.view', 'label' => 'مشاهده مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.list', 'label' => 'لیست مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.create', 'label' => 'ایجاد مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.update', 'label' => 'ویرایش مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.delete', 'label' => 'حذف مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.publish', 'label' => 'انتشار مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.unpublish', 'label' => 'عدم انتشار مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.overview.view', 'label' => 'مشاهده خلاصه مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.comments.view', 'label' => 'مشاهده نظرات مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.view.own', 'label' => 'مشاهده مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.view.any', 'label' => 'مشاهده همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.list.any', 'label' => 'لیست همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.update.own', 'label' => 'ویرایش مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.update.any', 'label' => 'ویرایش هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.delete.own', 'label' => 'حذف مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.delete.any', 'label' => 'حذف هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.publish.own', 'label' => 'انتشار/عدم انتشار مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.publish.any', 'label' => 'انتشار/عدم انتشار هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],

            // Payments
            ['name' => 'payments.view', 'label' => 'مشاهده پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.stats', 'label' => 'آمار پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.export', 'label' => 'خروجی پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.details', 'label' => 'جزئیات پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.update_status', 'label' => 'بروزرسانی وضعیت پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.delete', 'label' => 'حذف پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.create', 'label' => 'ایجاد پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.search', 'label' => 'جستجوی پرداخت/آیتم‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.search.users', 'label' => 'جستجوی کاربران برای پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.search.courses', 'label' => 'جستجوی دوره‌ها برای پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.search.plans', 'label' => 'جستجوی پلن‌ها برای پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.search.paths', 'label' => 'جستجوی مسیرها برای پرداخت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.view.own', 'label' => 'مشاهده پرداخت‌های مرتبط با دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.view.any', 'label' => 'مشاهده همه پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.export.any', 'label' => 'خروجی گرفتن از همه پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.delete.any', 'label' => 'حذف هر پرداختی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.update_status.any', 'label' => 'بروزرسانی وضعیت هر پرداختی', 'created_at' => $now, 'updated_at' => $now],

            // Discounts
            ['name' => 'discounts.view', 'label' => 'مشاهده کدتخفیف‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discounts.show', 'label' => 'نمایش کدتخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discounts.create', 'label' => 'ایجاد کدتخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discounts.update', 'label' => 'ویرایش کدتخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discounts.toggle_status', 'label' => 'تغییر وضعیت کدتخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discounts.delete', 'label' => 'حذف کدتخفیف', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discounts.search_eligibility', 'label' => 'جستجوی صلاحیت تخفیف', 'created_at' => $now, 'updated_at' => $now],

            // Panel/Profile/VIP
            ['name' => 'panel.view', 'label' => 'مشاهده پنل کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'panel.financial', 'label' => 'بخش مالی پنل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'vip.view', 'label' => 'مشاهده VIP', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'vip.purchase', 'label' => 'خرید VIP', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.mobile.info', 'label' => 'دریافت موبایل پروفایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.mobile.send_otp', 'label' => 'ارسال کد موبایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.mobile.verify_otp', 'label' => 'تایید کد موبایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.change_password', 'label' => 'تغییر رمز پروفایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.preferences.view', 'label' => 'مشاهده ترجیحات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.preferences.update', 'label' => 'به‌روزرسانی ترجیحات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.data.view', 'label' => 'دریافت اطلاعات کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.update', 'label' => 'ویرایش پروفایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.upload.avatar', 'label' => 'تغییر عکس پروفایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.upload.cover', 'label' => 'تغییر کاور پروفایل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.tokens.view', 'label' => 'مشاهده توکن‌های دسترسی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.tokens.terminate', 'label' => 'خاتمه یک نشست', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'profile.tokens.terminate_all', 'label' => 'خاتمه همه نشست‌ها', 'created_at' => $now, 'updated_at' => $now],

            // Comments/Community
            ['name' => 'comments.view', 'label' => 'مشاهده نظرات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.moderate', 'label' => 'مدیریت تایید/رد نظر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.reply', 'label' => 'ارسال پاسخ به نظر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.view.own', 'label' => 'مشاهده نظرات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.view.any', 'label' => 'مشاهده نظرات همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.moderate.own', 'label' => 'تایید/رد نظرات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.moderate.any', 'label' => 'تایید/رد نظرات همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.reply.own', 'label' => 'ارسال پاسخ در نظرات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.reply.any', 'label' => 'ارسال پاسخ در نظرات همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.delete.own', 'label' => 'حذف نظر در دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.delete.any', 'label' => 'حذف هر نظر', 'created_at' => $now, 'updated_at' => $now],

            // Comments by entity
            ['name' => 'comments.course.view.own', 'label' => 'مشاهده نظرات دوره‌های خود (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.view.any', 'label' => 'مشاهده نظرات همه دوره‌ها (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.moderate.own', 'label' => 'تایید/رد نظرات دوره‌های خود (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.moderate.any', 'label' => 'تایید/رد نظرات همه دوره‌ها (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.reply.own', 'label' => 'ارسال پاسخ در نظرات دوره‌های خود (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.reply.any', 'label' => 'ارسال پاسخ در نظرات همه دوره‌ها (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.delete.own', 'label' => 'حذف نظر در دوره‌های خود (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.course.delete.any', 'label' => 'حذف هر نظر (دوره)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.view.own', 'label' => 'مشاهده نظرات دوره‌های خود (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.view.any', 'label' => 'مشاهده نظرات همه دوره‌ها (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.moderate.own', 'label' => 'تایید/رد نظرات دوره‌های خود (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.moderate.any', 'label' => 'تایید/رد نظرات همه دوره‌ها (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.reply.own', 'label' => 'ارسال پاسخ در نظرات دوره‌های خود (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.reply.any', 'label' => 'ارسال پاسخ در نظرات همه دوره‌ها (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.delete.own', 'label' => 'حذف نظر در دوره‌های خود (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.episode.delete.any', 'label' => 'حذف هر نظر (جلسه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.view.any', 'label' => 'مشاهده نظرات همه مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.moderate.any', 'label' => 'تایید/رد نظرات همه مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.reply.any', 'label' => 'ارسال پاسخ در نظرات همه مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.delete.any', 'label' => 'حذف هر نظر (مسیر)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.view.own', 'label' => 'مشاهده نظرات مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.view.any', 'label' => 'مشاهده نظرات همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.moderate.own', 'label' => 'تایید/رد نظرات مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.moderate.any', 'label' => 'تایید/رد نظرات همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.reply.own', 'label' => 'ارسال پاسخ در نظرات مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.reply.any', 'label' => 'ارسال پاسخ در نظرات همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.delete.own', 'label' => 'حذف نظر در مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.article.delete.any', 'label' => 'حذف هر نظر (مقاله)', 'created_at' => $now, 'updated_at' => $now],

            // Discuss
            ['name' => 'discuss.list', 'label' => 'لیست پرسش‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.show', 'label' => 'نمایش پرسش/پاسخ‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.create', 'label' => 'ایجاد پرسش', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.answer.create', 'label' => 'ایجاد پاسخ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.update', 'label' => 'ویرایش پرسش/پاسخ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.delete', 'label' => 'حذف پرسش/پاسخ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.like_dislike', 'label' => 'پسند/ناپسند', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'discuss.toggle_pin', 'label' => 'سنجاق پرسش', 'created_at' => $now, 'updated_at' => $now],

            // Interactions
            ['name' => 'bookmark.toggle', 'label' => 'بوکمارک', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'like.toggle', 'label' => 'پسند', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'follow.toggle', 'label' => 'دنبال‌کردن', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'report.send', 'label' => 'ارسال گزارش', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'rating.set', 'label' => 'ثبت امتیاز', 'created_at' => $now, 'updated_at' => $now],

            // Notifications
            ['name' => 'notifications.view', 'label' => 'مشاهده اعلان‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'notifications.details', 'label' => 'جزئیات اعلان', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'notifications.unread', 'label' => 'تعداد خوانده‌نشده', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'notifications.delete', 'label' => 'حذف اعلان', 'created_at' => $now, 'updated_at' => $now],

            // Chat
            ['name' => 'chat.messages.view', 'label' => 'مشاهده پیام‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat.messages.send', 'label' => 'ارسال پیام', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat.messages.edit', 'label' => 'ویرایش پیام', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat.messages.delete', 'label' => 'حذف پیام', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat.messages.mark_read', 'label' => 'خوانده‌شدن پیام', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat.conversations.view', 'label' => 'مشاهده گفتگوها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'chat.contacts.view', 'label' => 'مشاهده مخاطبین', 'created_at' => $now, 'updated_at' => $now],

            // Video Views / Downloads
            ['name' => 'videoviews.get', 'label' => 'دریافت وضعیت تماشا', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'videoviews.set', 'label' => 'ثبت وضعیت تماشا', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episode.download.check', 'label' => 'بررسی مجوز دانلود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episode.download.signed', 'label' => 'دانلود امضاشده', 'created_at' => $now, 'updated_at' => $now],

            // File Manager / Upload
            ['name' => 'files.manage', 'label' => 'مدیریت فایل‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'uploads.image.editor', 'label' => 'آپلود تصویر ادیتور', 'created_at' => $now, 'updated_at' => $now],

            // Security / Routes Mapping
            ['name' => 'security.access.view', 'label' => 'مشاهده دسترسی‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'security.access.manage', 'label' => 'مدیریت دسترسی‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'security.routes.view', 'label' => 'مشاهده مپ روت→پرمیشن', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'security.routes.manage', 'label' => 'مدیریت مپ روت→پرمیشن', 'created_at' => $now, 'updated_at' => $now],

            // Marketing / SEO
            ['name' => 'marketing.manage', 'label' => 'مدیریت مارکتینگ/بنرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing.view', 'label' => 'مشاهده مارکتینگ/بنرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing.update', 'label' => 'ویرایش مارکتینگ/بنرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo.manage', 'label' => 'مدیریت SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo.view', 'label' => 'مشاهده SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo.update', 'label' => 'ویرایش SEO', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('permissions')->upsert($permissions, ['name'], ['label', 'updated_at']);

        // Assign all permissions to administrator role
        $adminRole = DB::table('roles')->where('name', 'administrator')->first();
        if ($adminRole) {
            $permissionIds = DB::table('permissions')->pluck('id');
            $rolePermissions = [];
            foreach ($permissionIds as $permissionId) {
                $rolePermissions[] = [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('permission_role')->upsert($rolePermissions, ['role_id', 'permission_id'], ['updated_at']);
        }

        // Note: Other role-permission mappings from SQL are complex and would require
        // parsing the REGEXP patterns. For now, we've seeded the basic structure.
        // You may want to add more specific role-permission mappings as needed.

        $map = [

            'administrator' => ['*'],

            'admin' => [
                'dashboard.view',
                'analytics.view',
                'users.view',
                'users.create',
                'users.update',
                'security.*',
                'admin.search_user',
            ],

            'admin_auditor' => [
                'dashboard.view',
                'analytics.view',
                'users.view',
                'users.sessions.view',
                'security.access.view',
                'security.routes.view',
            ],

            'security_admin' => [
                'users.view',
                'users.create',
                'users.update',
                'users.deactivate',
                'users.reactivate',
                'users.reset_password',
                'users.manage_roles',
                'users.manage_permissions',
                'users.impersonate',
                'users.sessions.view',
                'users.sessions.terminate',
                'security.access.view',
                'security.access.manage',
                'security.routes.view',
                'security.routes.manage',
            ],

            'user_admin' => [
                'users.*',              
                'users.sessions.*',    
                'admin.search_user',    
            ],

            'user_manager' => [
                'users.view',
                'users.create',
                'users.update',
                'users.deactivate',
                'users.reactivate',
                'users.reset_password',
                'users.sessions.view',
                'users.sessions.terminate',
                'admin.search_user',
            ],

            'user_viewer' => [
                'users.view',
                'users.sessions.view',
                'admin.search_user',
            ],

            'user_support' => [
                'users.view',
                'users.sessions.view',
                'users.sessions.terminate',
                'users.reset_password',
                'admin.search_user',
            ],

            'content_admin' => [
                'articles.*',
                'categories.*',
                'uploads.image.editor',
            ],

            'content_publisher' => [
                'articles.publish',
                'articles.unpublish',
                'articles.publish.own',
            ],

            'course_manager' => [
                'courses.view',
                'courses.list',
                'courses.create',
                'courses.update',
                'courses.delete',
                'courses.publish',
                'courses.unpublish',
                'courses.assign_user',
                'courses.reorder_episodes',
                'courses.overview.view',
                'courses.comments.manage',
                'episodes.*',
                'videos.*',
            ],

            'course_manager_all' => [
                'courses.view.any',
                'courses.list.any',
                'courses.update.any',
                'courses.delete.any',
                'courses.publish.any',
                'courses.assign_user.any',
                'courses.reorder_episodes.any',
                'episodes.create.any',
                'episodes.edit.any',
                'episodes.delete.any',
                'episodes.reorder.any',
                'videos.upload.any',
                'videos.process.any',
            ],

            'course_manager_own' => [
                'courses.view.own',
                'courses.update.own',
                'courses.delete.own',
                'courses.publish.own',
                'courses.assign_user.own',
                'courses.reorder_episodes.own',
                'episodes.create.own',
                'episodes.edit.own',
                'episodes.delete.own',
                'episodes.reorder.own',
                'videos.upload.own',
                'videos.process.own',
            ],

            'course_viewer_all' => [
                'courses.view.any',
                'courses.list.any',
                'courses.overview.view',
                'courses.comments.view',
            ],

            'teacher' => [
                'courses.view.own',
                'courses.overview.view',
                'episodes.create.own',
                'episodes.edit.own',
                'episodes.delete.own',
                'videos.upload.own',
                'videos.process.own',
                'comments.course.view.own',
                'comments.course.reply.own',
            ],

            'teacher_assistant' => [
                'episodes.create.own',
                'episodes.edit.own',
                'videos.upload.own',
                'comments.course.reply.own',
            ],

            'teacher_finance_viewer' => [
                'payments.view.own',
                'payments.stats',
            ],

            'student' => [
                'panel.view',
                'panel.financial',
                'vip.view',
                'vip.purchase',
                'profile.view',
                'profile.update',
                'courses.view',
                'courses.list',
                'videoviews.create',
                'episode.download.original',
                'comments.view',
                'comments.reply',
                'discuss.list',
                'discuss.show',
                'bookmark.toggle',
                'like.toggle',
                'follow.toggle',
                'rating.set',
                'notifications.view',
                'chat.messages.send',
                'chat.messages.view',
            ],

            'community_manager' => [
                'comments.*',
                'discuss.*',
            ],

            'community_viewer' => [
                'comments.view',
                'discuss.list',
                'discuss.show',
            ],

            'comments_moderator_all' => [
                'comments.view.any',
                'comments.moderate.any',
                'comments.reply.any',
                'comments.delete.any',
            ],

            'comments_moderator_own' => [
                'comments.view.own',
                'comments.moderate.own',
                'comments.reply.own',
                'comments.delete.own',
            ],

            'moderator' => [
                'comments.view',
                'comments.moderate',
                'comments.reply',
                'discuss.toggle_pin',
            ],

            'support' => [
                'users.view',
                'payments.view',
                'payments.details',
                'payments.search',
                'chat.messages.view',
                'chat.messages.send',
            ],

            'support_lite' => [
                'users.view',
                'chat.messages.view',
            ],

            'finance_manager' => [
                'payments.*',
                'payments.search',
                'payments.export',
            ],

            'payments_auditor' => [
                'payments.view',
                'payments.stats',
                'payments.export.any',
            ],

            'payments_operator' => [
                'payments.create',
                'payments.update_status',
                'payments.delete.any',
            ],

            'discount_manager' => [
                'discounts.*',
            ],
            
            'plan_manager' => [
                'plans.*',
            ],

            'discount_analyst' => [
                'discounts.view',
                'discounts.show',
                'discounts.search_eligibility',
            ],

            'catalog_manager' => [
                'categories.*',
                'levels.*',
                'statuses.*',
                'paths.*',
            ],

            'catalog_viewer' => [
                'categories.view',
                'levels.view',
                'statuses.view',
                'paths.view',
            ],

            'category_manager' => ['categories.*'],
            'level_manager' => ['levels.*'],
            'status_manager' => ['statuses.*'],

            'path_manager' => [
                'paths.view',
                'paths.create',
                'paths.edit',
                'paths.update',
                'paths.delete',
                'paths.remove_file',
                'paths.upload.poster',
                'paths.upload.icon',
                'paths.upload.trailer',
                'paths.search.courses',
                'paths.search.paths',
                'paths.courses.attach',
                'paths.courses.detach',
            ],

            'article_viewer_all' => [
                'articles.view.any',
                'articles.list.any',
                'articles.overview.view',
                'articles.comments.view',
            ],

            'article_manager_all' => ['articles.*'],

            'article_manager_own' => [
                'articles.view.own',
                'articles.update.own',
                'articles.delete.own',
                'articles.publish.own',
            ],

            'file_manager' => [
                'files.manage',
                'uploads.image.editor',
            ],

            'video_operator' => [
                'videos.upload',
                'videos.process',
                'videos.key.view',
                'videos.download.signed',
            ],

            'notification_manager' => ['notifications.*'],

            'chat_moderator' => [
                'chat.messages.view',
                'chat.messages.edit',
                'chat.messages.delete',
                'chat.conversations.view',
            ],

            'chat_viewer' => [
                'chat.messages.view',
                'chat.conversations.view',
                'chat.contacts.view',
            ],

            'marketing_manager' => [
                'marketing.manage',
                'marketing.update',
                'marketing.view',
            ],

            'marketing_editor' => ['marketing.update'],
            'marketing_viewer' => ['marketing.view'],

            'seo_manager' => [
                'seo.manage',
                'seo.update',
                'seo.view',
            ],

            'seo_editor' => ['seo.update'],
            'seo_viewer' => ['seo.view'],

            'analytics_viewer' => ['analytics.view'],
        ];

        /*
        |--------------------------------------------------------------------------
        | BUILD permission_role INSERT
        |--------------------------------------------------------------------------
        */

        $roles = DB::table('roles')->pluck('id', 'name');
        $permissions = DB::table('permissions')->pluck('id', 'name');

        $now = now();
        $rows = [];

        foreach ($map as $roleName => $permissionNames) {

            if (!isset($roles[$roleName])) {
                continue;
            }

            foreach ($permissionNames as $permName) {

                // administrator with *
                if ($permName === '*') {
                    foreach ($permissions as $pid) {
                        $rows[] = [
                            'role_id' => $roles[$roleName],
                            'permission_id' => $pid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    continue 2;
                }

                // wildcard like articles.*
                if (str_ends_with($permName, '.*')) {
                    $prefix = rtrim($permName, '.*');
                    foreach ($permissions as $name => $pid) {
                        if (str_starts_with($name, $prefix . '.')) {
                            $rows[] = [
                                'role_id' => $roles[$roleName],
                                'permission_id' => $pid,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                    continue;
                }

                if (!isset($permissions[$permName])) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $roles[$roleName],
                    'permission_id' => $permissions[$permName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('permission_role')
            ->upsert($rows, ['role_id', 'permission_id'], ['updated_at']);
    }
}

