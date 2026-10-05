<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsAndRolesSeeder extends Seeder
{
    /**
     * PostgreSQL rejects upsert batches that contain duplicate conflict keys.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $uniqueKeys
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateRows(array $rows, array $uniqueKeys): array
    {
        $seen = [];
        $deduped = [];

        foreach ($rows as $row) {
            $key = implode('|', array_map(fn (string $column) => (string) $row[$column], $uniqueKeys));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $row;
        }

        return $deduped;
    }

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
            ['name' => 'messenger_manager', 'label' => 'مدیر پیام‌رسان', 'created_at' => $now, 'updated_at' => $now],
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
            ['name' => 'dashboard.view.own', 'label' => 'مشاهده داشبورد محدود به محتوای خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'dashboard.view.any', 'label' => 'مشاهده داشبورد کامل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'analytics.view', 'label' => 'مشاهده آمار/گزارش‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'analytics.view.own', 'label' => 'مشاهده آمار/گزارش‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'analytics.view.any', 'label' => 'مشاهده همه آمار/گزارش‌ها', 'created_at' => $now, 'updated_at' => $now],

            // Users/Security
            ['name' => 'users.view', 'label' => 'مشاهده کاربران', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.create', 'label' => 'ایجاد کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.update', 'label' => 'ویرایش کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'users.delete', 'label' => 'حذف کاربر', 'created_at' => $now, 'updated_at' => $now],
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
            ['name' => 'courses.list.own', 'label' => 'لیست دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.create', 'label' => 'ایجاد دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.update', 'label' => 'ویرایش دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.delete', 'label' => 'حذف دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.publish', 'label' => 'انتشار دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.unpublish', 'label' => 'عدم انتشار دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.assign_user', 'label' => 'اختصاص دوره به کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.reorder_episodes', 'label' => 'مرتب‌سازی قسمت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.view', 'label' => 'مشاهده خلاصه دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.view.own', 'label' => 'مشاهده خلاصه دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.view.any', 'label' => 'مشاهده خلاصه هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.update', 'label' => 'ویرایش خلاصه دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.update.own', 'label' => 'ویرایش خلاصه دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.overview.update.any', 'label' => 'ویرایش خلاصه هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.view', 'label' => 'مشاهده نظرات دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.view.own', 'label' => 'مشاهده نظرات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.view.any', 'label' => 'مشاهده نظرات همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.view.own', 'label' => 'مشاهده دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.view.any', 'label' => 'مشاهده همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.list.any', 'label' => 'لیست همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.update.own', 'label' => 'ویرایش دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.update.any', 'label' => 'ویرایش هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.delete.own', 'label' => 'حذف دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.delete.any', 'label' => 'حذف هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.publish.own', 'label' => 'انتشار/عدم انتشار دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.publish.any', 'label' => 'انتشار/عدم انتشار هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.unpublish.own', 'label' => 'عدم انتشار دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.unpublish.any', 'label' => 'عدم انتشار هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.assign_user.own', 'label' => 'اختصاص دوره‌های خود به کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.assign_user.any', 'label' => 'اختصاص هر دوره‌ای به کاربر', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.reorder_episodes.own', 'label' => 'مرتب‌سازی قسمت‌های دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.reorder_episodes.any', 'label' => 'مرتب‌سازی قسمت‌های هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.manage', 'label' => 'مدیریت نظرات دوره', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.manage.own', 'label' => 'مدیریت نظرات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'courses.comments.manage.any', 'label' => 'مدیریت نظرات همه دوره‌ها', 'created_at' => $now, 'updated_at' => $now],

            // Sections (فصل‌های دوره)
            ['name' => 'sections.view', 'label' => 'مشاهده فصل‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.view.own', 'label' => 'مشاهده فصل‌های دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.view.any', 'label' => 'مشاهده فصل‌های هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.create', 'label' => 'ایجاد فصل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.create.own', 'label' => 'ایجاد فصل برای دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.create.any', 'label' => 'ایجاد فصل برای هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.update', 'label' => 'ویرایش فصل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.update.own', 'label' => 'ویرایش فصل دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.update.any', 'label' => 'ویرایش فصل هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.delete', 'label' => 'حذف فصل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.delete.own', 'label' => 'حذف فصل دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sections.delete.any', 'label' => 'حذف فصل هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],

            // Episodes
            ['name' => 'episodes.view', 'label' => 'مشاهده جلسه/قسمت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.view.own', 'label' => 'مشاهده جلسات دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.view.any', 'label' => 'مشاهده جلسات هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
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
            ['name' => 'episodes.upload_file.own', 'label' => 'آپلود فایل جلسه دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.upload_file.any', 'label' => 'آپلود فایل جلسه هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.status.own', 'label' => 'تغییر وضعیت جلسه دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'episodes.status.any', 'label' => 'تغییر وضعیت جلسه هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],

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

            // Quizzes (LMS)
            ['name' => 'quizzes.view', 'label' => 'مشاهده آزمون‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.view.own', 'label' => 'مشاهده آزمون‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.view.any', 'label' => 'مشاهده همه آزمون‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.create', 'label' => 'ایجاد آزمون', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.create.own', 'label' => 'ایجاد آزمون برای دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.create.any', 'label' => 'ایجاد آزمون برای هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.update', 'label' => 'ویرایش آزمون', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.update.own', 'label' => 'ویرایش آزمون‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.update.any', 'label' => 'ویرایش هر آزمونی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.delete', 'label' => 'حذف آزمون', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.delete.own', 'label' => 'حذف آزمون‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.delete.any', 'label' => 'حذف هر آزمونی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.reports', 'label' => 'گزارش آزمون', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.reports.own', 'label' => 'گزارش آزمون‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.reports.any', 'label' => 'گزارش همه آزمون‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.review', 'label' => 'تصحیح دستی آزمون', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.review.own', 'label' => 'تصحیح دستی آزمون‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quizzes.review.any', 'label' => 'تصحیح دستی همه آزمون‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.view', 'label' => 'مشاهده بانک سوال', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.view.own', 'label' => 'مشاهده بانک سوال (خود)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.view.any', 'label' => 'مشاهده همه بانک سوال', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.create', 'label' => 'ایجاد سوال', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.create.own', 'label' => 'ایجاد سوال (خود)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.create.any', 'label' => 'ایجاد سوال برای همه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.update', 'label' => 'ویرایش سوال', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.update.own', 'label' => 'ویرایش سوال (خود)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.update.any', 'label' => 'ویرایش هر سوالی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.delete', 'label' => 'حذف سوال', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.delete.own', 'label' => 'حذف سوال (خود)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'quiz_questions.delete.any', 'label' => 'حذف هر سوالی', 'created_at' => $now, 'updated_at' => $now],

            // Certificates (LMS)
            ['name' => 'certificates.view', 'label' => 'مشاهده گواهینامه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.view.own', 'label' => 'مشاهده گواهینامه‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.view.any', 'label' => 'مشاهده همه گواهینامه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.create', 'label' => 'صدور گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.create.own', 'label' => 'صدور گواهینامه برای دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.create.any', 'label' => 'صدور گواهینامه برای هر دوره‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.update', 'label' => 'ویرایش گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.update.own', 'label' => 'ویرایش گواهینامه‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.update.any', 'label' => 'ویرایش هر گواهینامه‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.delete', 'label' => 'حذف/ابطال گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.delete.own', 'label' => 'حذف/ابطال گواهینامه‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.delete.any', 'label' => 'حذف/ابطال هر گواهینامه‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.export', 'label' => 'خروجی گواهینامه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.export.own', 'label' => 'خروجی گواهینامه‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.export.any', 'label' => 'خروجی همه گواهینامه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.view', 'label' => 'مشاهده قالب گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.view.any', 'label' => 'مشاهده همه قالب‌های گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.create', 'label' => 'ایجاد قالب گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.create.any', 'label' => 'ایجاد قالب گواهینامه (همه)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.update', 'label' => 'ویرایش قالب گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.update.any', 'label' => 'ویرایش هر قالب گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.delete', 'label' => 'حذف قالب گواهینامه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'certificates.templates.delete.any', 'label' => 'حذف هر قالب گواهینامه', 'created_at' => $now, 'updated_at' => $now],

            // Missions (gamification)
            ['name' => 'missions.view', 'label' => 'مشاهده ماموریت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'missions.create', 'label' => 'ایجاد ماموریت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'missions.update', 'label' => 'ویرایش ماموریت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'missions.delete', 'label' => 'حذف ماموریت', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'mission-categories.manage', 'label' => 'مدیریت دسته‌بندی ماموریت‌ها', 'created_at' => $now, 'updated_at' => $now],

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
            ['name' => 'articles.list.own', 'label' => 'لیست مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.create', 'label' => 'ایجاد مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.update', 'label' => 'ویرایش مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.delete', 'label' => 'حذف مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.publish', 'label' => 'انتشار مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.unpublish', 'label' => 'عدم انتشار مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.overview.view', 'label' => 'مشاهده خلاصه مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.overview.view.own', 'label' => 'مشاهده خلاصه مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.overview.view.any', 'label' => 'مشاهده خلاصه همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.comments.view', 'label' => 'مشاهده نظرات مقاله', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.comments.view.own', 'label' => 'مشاهده نظرات مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.comments.view.any', 'label' => 'مشاهده نظرات همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.view.own', 'label' => 'مشاهده مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.view.any', 'label' => 'مشاهده همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.list.any', 'label' => 'لیست همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.update.own', 'label' => 'ویرایش مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.update.any', 'label' => 'ویرایش هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.delete.own', 'label' => 'حذف مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.delete.any', 'label' => 'حذف هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.publish.own', 'label' => 'انتشار/عدم انتشار مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.publish.any', 'label' => 'انتشار/عدم انتشار هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.unpublish.own', 'label' => 'عدم انتشار مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.unpublish.any', 'label' => 'عدم انتشار هر مقاله‌ای', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.stats.view', 'label' => 'مشاهده آمار مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.stats.view.own', 'label' => 'مشاهده آمار مقالات خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.stats.view.any', 'label' => 'مشاهده آمار همه مقالات', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'articles.categories.manage', 'label' => 'مدیریت دسته‌بندی مقالات', 'created_at' => $now, 'updated_at' => $now],

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
            ['name' => 'payments.stats.own', 'label' => 'آمار پرداخت‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.stats.any', 'label' => 'آمار همه پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.export.own', 'label' => 'خروجی پرداخت‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.export.any', 'label' => 'خروجی گرفتن از همه پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.details.own', 'label' => 'جزئیات پرداخت‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.details.any', 'label' => 'جزئیات همه پرداخت‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.create.own', 'label' => 'ایجاد پرداخت برای دوره‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.create.any', 'label' => 'ایجاد هر پرداختی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.delete.own', 'label' => 'حذف پرداخت‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.delete.any', 'label' => 'حذف هر پرداختی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.update_status.own', 'label' => 'بروزرسانی وضعیت پرداخت‌های مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'payments.update_status.any', 'label' => 'بروزرسانی وضعیت هر پرداختی', 'created_at' => $now, 'updated_at' => $now],

            ['name' => 'settlements.view', 'label' => 'مشاهده تسویه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.view.any', 'label' => 'مشاهده همه تسویه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.view.own', 'label' => 'مشاهده تسویه‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.create', 'label' => 'ایجاد تسویه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.update', 'label' => 'ویرایش اطلاعات تسویه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.change_status', 'label' => 'تغییر وضعیت تسویه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.upload_receipt', 'label' => 'آپلود و جایگزینی رسید تسویه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.view_receipt', 'label' => 'مشاهده رسید تسویه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.view_receipt.any', 'label' => 'مشاهده رسید همه تسویه‌ها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.view_receipt.own', 'label' => 'مشاهده رسید تسویه‌های خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.delete_receipt', 'label' => 'حذف رسید تسویه', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'settlements.view_all_teachers', 'label' => 'مشاهده وضعیت مالی همه مدرسین', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'bank_accounts.manage', 'label' => 'مدیریت حساب، شبا و کارت بانکی خود', 'created_at' => $now, 'updated_at' => $now],

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
            ['name' => 'comments.path.view.own', 'label' => 'مشاهده نظرات مسیرهای مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.moderate.any', 'label' => 'تایید/رد نظرات همه مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.moderate.own', 'label' => 'تایید/رد نظرات مسیرهای مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.reply.any', 'label' => 'ارسال پاسخ در نظرات همه مسیرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.reply.own', 'label' => 'ارسال پاسخ در نظرات مسیرهای مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.delete.any', 'label' => 'حذف هر نظر (مسیر)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'comments.path.delete.own', 'label' => 'حذف نظر در مسیرهای مرتبط با خود', 'created_at' => $now, 'updated_at' => $now],
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

            // Messenger admin settings
            ['name' => 'messenger.settings.view', 'label' => 'مشاهده تنظیمات پیام‌رسان', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'messenger.settings.update', 'label' => 'ویرایش تنظیمات پیام‌رسان', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'messenger.users.manage', 'label' => 'مدیریت دسترسی کاربران به پیام‌رسان', 'created_at' => $now, 'updated_at' => $now],

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

            // System resources
            ['name' => 'system.resources.view', 'label' => 'مشاهده منابع سیستم', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'system.logs.view', 'label' => 'مشاهده لاگ‌های Laravel', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'system.logs.download', 'label' => 'دانلود فایل لاگ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'system.logs.clear', 'label' => 'پاک‌سازی فایل لاگ', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'system.logs.delete', 'label' => 'حذف فایل لاگ روزانه', 'created_at' => $now, 'updated_at' => $now],

            // Marketing / SEO
            ['name' => 'marketing.manage', 'label' => 'مدیریت مارکتینگ/بنرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing.view', 'label' => 'مشاهده مارکتینگ/بنرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'marketing.update', 'label' => 'ویرایش مارکتینگ/بنرها', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo.manage', 'label' => 'مدیریت SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo.view', 'label' => 'مشاهده SEO', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seo.update', 'label' => 'ویرایش SEO', 'created_at' => $now, 'updated_at' => $now],

            // Support inbox (site mailboxes)
            ['name' => 'support.inbox.view', 'label' => 'مشاهده صندوق ایمیل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'support.inbox.reply', 'label' => 'پاسخ به ایمیل‌های دریافتی', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'support.inbox.send', 'label' => 'ارسال ایمیل جدید', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'support.inbox.delete', 'label' => 'حذف ایمیل', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'support.inbox.move', 'label' => 'انتقال ایمیل بین پوشه‌ها', 'created_at' => $now, 'updated_at' => $now],
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
            DB::table('permission_role')->upsert(
                $this->deduplicateRows($rolePermissions, ['role_id', 'permission_id']),
                ['role_id', 'permission_id'],
                ['updated_at']
            );
        }

        // Note: Other role-permission mappings from SQL are complex and would require
        // parsing the REGEXP patterns. For now, we've seeded the basic structure.
        // You may want to add more specific role-permission mappings as needed.

        $map = [

            'administrator' => ['*'],

            'admin' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view',
                'analytics.view.any',
                'users.view',
                'users.create',
                'users.update',
                'security.*',
                'admin.search_user',
                'messenger.settings.view',
                'messenger.settings.update',
                'messenger.users.manage',
            ],

            'admin_auditor' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view',
                'analytics.view.any',
                'users.view',
                'users.sessions.view',
                'security.access.view',
                'security.routes.view',
                'system.resources.view',
                'system.logs.view',
                'messenger.settings.view',
            ],

            'security_admin' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
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
                'system.resources.view',
                'system.logs.view',
                'system.logs.download',
                'system.logs.clear',
                'system.logs.delete',
                'messenger.settings.view',
                'messenger.settings.update',
                'messenger.users.manage',
            ],

            'user_admin' => [
                'dashboard.view',
                'users.*',
                'users.sessions.*',
                'admin.search_user', 
            ],

            'user_manager' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.deactivate',
                'users.reactivate',
                'users.reset_password',
                'users.sessions.view',
                'users.sessions.terminate',
                'admin.search_user',
            ],

            'user_viewer' => [
                'dashboard.view',
                'users.view',
                'users.sessions.view',
                'admin.search_user',
            ],

            'user_support' => [
                'dashboard.view',
                'users.view',
                'users.sessions.view',
                'users.sessions.terminate',
                'users.reset_password',
                'admin.search_user',
                'support.inbox.view',
                'support.inbox.reply',
                'support.inbox.send',
                'support.inbox.delete',
                'support.inbox.move',
            ],

            'content_admin' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'articles.view.any',
                'articles.list.any',
                'articles.create',
                'articles.update.any',
                'articles.delete.any',
                'articles.publish.any',
                'articles.unpublish.any',
                'articles.overview.view.any',
                'articles.comments.view.any',
                'articles.stats.view.any',
                'articles.categories.manage',
                'admin.search_user',
                'categories.*',
                'uploads.image.editor',
                'comments.view.any',
                'comments.article.view.any',
                'comments.article.moderate.any',
                'comments.article.reply.any',
                'comments.article.delete.any',
            ],

            'content_publisher' => [
                'courses.publish.own',
                'courses.publish.any',
                'courses.unpublish.own',
                'courses.unpublish.any',
                'articles.publish.own',
                'articles.publish.any',
                'articles.unpublish.own',
                'articles.unpublish.any',
            ],

            'course_manager' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'courses.view.any',
                'courses.list.any',
                'courses.create',
                'courses.update.any',
                'courses.delete.any',
                'courses.publish.any',
                'courses.unpublish.any',
                'courses.assign_user.any',
                'courses.reorder_episodes.any',
                'courses.overview.view.any',
                'courses.overview.update.any',
                'courses.comments.view.any',
                'courses.comments.manage.any',
                'sections.view.any',
                'sections.create.any',
                'sections.update.any',
                'sections.delete.any',
                'episodes.view.any',
                'episodes.create.any',
                'episodes.edit.any',
                'episodes.delete.any',
                'episodes.reorder.any',
                'episodes.upload_file.any',
                'episodes.status.any',
                'episodes.get_for_edit',
                'videos.upload.any',
                'videos.process.any',
                'certificates.view.any',
                'certificates.create.any',
                'certificates.update.any',
                'certificates.delete.any',
                'certificates.export.any',
                'certificates.templates.view',
                'certificates.templates.view.any',
                'certificates.templates.create.any',
                'certificates.templates.update.any',
                'certificates.templates.delete.any',
                'quizzes.view.any',
                'quizzes.create.any',
                'quizzes.update.any',
                'quizzes.delete.any',
                'quizzes.reports.any',
                'quizzes.review.any',
                'quiz_questions.view.any',
                'quiz_questions.create.any',
                'quiz_questions.update.any',
                'quiz_questions.delete.any',
                'comments.view.any',
                'comments.moderate.any',
                'comments.reply.any',
                'comments.delete.any',
                'comments.course.view.any',
                'comments.course.moderate.any',
                'comments.course.reply.any',
                'comments.course.delete.any',
                'comments.episode.view.any',
                'comments.episode.moderate.any',
                'comments.episode.reply.any',
                'comments.episode.delete.any',
            ],

            'course_manager_all' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'courses.view.any',
                'courses.list.any',
                'courses.create',
                'courses.overview.view.any',
                'courses.comments.view.any',
                'courses.update.any',
                'courses.delete.any',
                'courses.publish.any',
                'courses.assign_user.any',
                'courses.reorder_episodes.any',
                'sections.create.any',
                'sections.update.any',
                'sections.delete.any',
                'episodes.view.any',
                'episodes.create.any',
                'episodes.edit.any',
                'episodes.delete.any',
                'episodes.reorder.any',
                'episodes.upload_file.any',
                'episodes.status.any',
                'episodes.get_for_edit',
                'videos.upload.any',
                'videos.process.any',
                'certificates.view.any',
                'certificates.create.any',
                'certificates.update.any',
                'certificates.delete.any',
                'quizzes.view.any',
                'quizzes.create.any',
                'quizzes.update.any',
                'quizzes.delete.any',
                'quizzes.reports.any',
                'quizzes.review.any',
                'quiz_questions.view.any',
                'quiz_questions.create.any',
                'quiz_questions.update.any',
                'quiz_questions.delete.any',
                'comments.view.any',
                'comments.course.view.any',
                'comments.episode.view.any',
            ],

            'course_manager_own' => [
                'dashboard.view.own',
                'analytics.view.own',
                'courses.view.own',
                'courses.list.own',
                'courses.create',
                'courses.overview.view.own',
                'courses.overview.update.own',
                'courses.comments.view.own',
                'courses.comments.manage.own',
                'courses.update.own',
                'courses.delete.own',
                'courses.publish.own',
                'courses.unpublish.own',
                'courses.assign_user.own',
                'courses.reorder_episodes.own',
                'sections.view.own',
                'sections.create.own',
                'sections.update.own',
                'sections.delete.own',
                'episodes.view.own',
                'episodes.create.own',
                'episodes.edit.own',
                'episodes.delete.own',
                'episodes.reorder.own',
                'episodes.upload_file.own',
                'episodes.status.own',
                'episodes.get_for_edit',
                'videos.upload.own',
                'videos.process.own',
                'certificates.view.own',
                'certificates.create.own',
                'certificates.update.own',
                'certificates.delete.own',
                'certificates.export.own',
                'certificates.templates.view',
                'quizzes.view.own',
                'quizzes.create.own',
                'quizzes.update.own',
                'quizzes.delete.own',
                'quizzes.reports.own',
                'quizzes.review.own',
                'quiz_questions.view.own',
                'quiz_questions.create.own',
                'quiz_questions.update.own',
                'quiz_questions.delete.own',
                'comments.view.own',
                'comments.moderate.own',
                'comments.reply.own',
                'comments.delete.own',
                'comments.course.view.own',
                'comments.course.moderate.own',
                'comments.course.reply.own',
                'comments.course.delete.own',
                'comments.episode.view.own',
                'comments.episode.moderate.own',
                'comments.episode.reply.own',
                'comments.episode.delete.own',
                'payments.view.own',
                'payments.stats.own',
                'payments.details.own',
                'payments.search',
            ],

            'course_viewer_all' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'courses.view.any',
                'courses.list.any',
                'courses.overview.view.any',
                'courses.comments.view.any',
                'certificates.view.any',
                'quizzes.view.any',
                'comments.view.any',
                'comments.course.view.any',
                'comments.episode.view.any',
            ],

            'teacher' => [
                'dashboard.view.own',
                'analytics.view.own',
                'courses.view.own',
                'courses.list.own',
                'courses.create',
                'courses.update.own',
                'courses.delete.own',
                'courses.publish.own',
                'courses.unpublish.own',
                'courses.assign_user.own',
                'courses.overview.view.own',
                'courses.overview.update.own',
                'courses.comments.view.own',
                'courses.comments.manage.own',
                'courses.reorder_episodes.own',
                'sections.view.own',
                'sections.create.own',
                'sections.update.own',
                'sections.delete.own',
                'episodes.view.own',
                'episodes.create.own',
                'episodes.edit.own',
                'episodes.delete.own',
                'episodes.reorder.own',
                'episodes.upload_file.own',
                'episodes.status.own',
                'episodes.get_for_edit',
                'videos.upload.own',
                'videos.process.own',
                'comments.view.own',
                'comments.moderate.own',
                'comments.reply.own',
                'comments.delete.own',
                'comments.course.view.own',
                'comments.course.moderate.own',
                'comments.course.reply.own',
                'comments.course.delete.own',
                'comments.episode.view.own',
                'comments.episode.moderate.own',
                'comments.episode.reply.own',
                'comments.episode.delete.own',
                'certificates.view.own',
                'certificates.create.own',
                'certificates.update.own',
                'certificates.delete.own',
                'certificates.export.own',
                'certificates.templates.view',
                'quizzes.view.own',
                'quizzes.create.own',
                'quizzes.update.own',
                'quizzes.delete.own',
                'quizzes.reports.own',
                'quizzes.review.own',
                'quiz_questions.view.own',
                'quiz_questions.create.own',
                'quiz_questions.update.own',
                'quiz_questions.delete.own',
                'payments.view.own',
                'payments.stats.own',
                'payments.details.own',
                'payments.export.own',
                'payments.search',
                'settlements.view.own',
                'settlements.view_receipt.own',
                'bank_accounts.manage',
            ],

            'teacher_assistant' => [
                'dashboard.view.own',
                'courses.view.own',
                'courses.list.own',
                'courses.overview.view.own',
                'sections.update.own',
                'episodes.create.own',
                'episodes.edit.own',
                'episodes.upload_file.own',
                'episodes.get_for_edit',
                'videos.upload.own',
                'comments.course.view.own',
                'comments.course.reply.own',
                'comments.episode.view.own',
                'comments.episode.reply.own',
            ],

            'teacher_finance_viewer' => [
                'dashboard.view.own',
                'analytics.view.own',
                'payments.view.own',
                'payments.stats.own',
                'payments.details.own',
                'payments.export.own',
                'payments.search',
                'settlements.view.own',
                'settlements.view_receipt.own',
                'bank_accounts.manage',
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
                'dashboard.view',
                'dashboard.view.any',
                'comments.view.any',
                'comments.moderate.any',
                'comments.reply.any',
                'comments.delete.any',
                'comments.course.view.any',
                'comments.course.moderate.any',
                'comments.course.reply.any',
                'comments.course.delete.any',
                'comments.episode.view.any',
                'comments.episode.moderate.any',
                'comments.episode.reply.any',
                'comments.episode.delete.any',
                'comments.article.view.any',
                'comments.article.moderate.any',
                'comments.article.reply.any',
                'comments.article.delete.any',
                'comments.path.view.any',
                'comments.path.moderate.any',
                'comments.path.reply.any',
                'comments.path.delete.any',
                'discuss.list',
                'discuss.show',
                'discuss.create',
                'discuss.update',
                'discuss.delete',
                'discuss.answer.create',
                'discuss.like_dislike',
                'discuss.toggle_pin',
            ],

            'community_viewer' => [
                'dashboard.view',
                'dashboard.view.any',
                'comments.view.any',
                'comments.course.view.any',
                'comments.episode.view.any',
                'comments.article.view.any',
                'discuss.list',
                'discuss.show',
            ],

            'comments_moderator_all' => [
                'dashboard.view',
                'dashboard.view.any',
                'comments.view.any',
                'comments.moderate.any',
                'comments.reply.any',
                'comments.delete.any',
                'comments.course.view.any',
                'comments.course.moderate.any',
                'comments.course.reply.any',
                'comments.course.delete.any',
                'comments.episode.view.any',
                'comments.episode.moderate.any',
                'comments.episode.reply.any',
                'comments.episode.delete.any',
                'comments.article.view.any',
                'comments.article.moderate.any',
                'comments.article.reply.any',
                'comments.article.delete.any',
                'comments.path.view.any',
                'comments.path.moderate.any',
                'comments.path.reply.any',
                'comments.path.delete.any',
            ],

            'comments_moderator_own' => [
                'dashboard.view',
                'dashboard.view.own',
                'comments.view.own',
                'comments.moderate.own',
                'comments.reply.own',
                'comments.delete.own',
                'comments.course.view.own',
                'comments.course.moderate.own',
                'comments.course.reply.own',
                'comments.course.delete.own',
                'comments.episode.view.own',
                'comments.episode.moderate.own',
                'comments.episode.reply.own',
                'comments.episode.delete.own',
                'comments.article.view.own',
                'comments.article.moderate.own',
                'comments.article.reply.own',
                'comments.article.delete.own',
            ],

            'moderator' => [
                'dashboard.view',
                'dashboard.view.any',
                'comments.view.any',
                'comments.moderate.any',
                'comments.reply.any',
                'discuss.toggle_pin',
            ],

            'support' => [
                'dashboard.view',
                'dashboard.view.any',
                'users.view',
                'payments.view.any',
                'payments.details.any',
                'payments.stats.any',
                'payments.search',
                'settlements.view',
                'settlements.view.any',
                'bank_accounts.manage',
                'chat.messages.view',
                'chat.messages.send',
            ],

            'support_lite' => [
                'dashboard.view',
                'users.view',
                'chat.messages.view',
            ],

            'finance_manager' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'payments.view.any',
                'payments.stats.any',
                'payments.details.any',
                'payments.export.any',
                'payments.create.any',
                'payments.update_status.any',
                'payments.delete.any',
                'payments.search',
                'settlements.view',
                'settlements.view.any',
                'settlements.create',
                'settlements.update',
                'settlements.change_status',
                'settlements.upload_receipt',
                'settlements.view_receipt',
                'settlements.view_receipt.any',
                'settlements.delete_receipt',
                'settlements.view_all_teachers',
                'bank_accounts.manage',
            ],

            'payments_auditor' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'payments.view.any',
                'payments.stats.any',
                'payments.details.any',
                'payments.export.any',
                'payments.search',
                'settlements.view',
                'settlements.view.any',
                'settlements.view_receipt',
                'settlements.view_receipt.any',
                'settlements.view_all_teachers',
                'bank_accounts.manage',
            ],

            'payments_operator' => [
                'dashboard.view',
                'dashboard.view.any',
                'payments.view.any',
                'payments.create.any',
                'payments.update_status.any',
                'payments.delete.any',
                'payments.search',
                'settlements.view',
                'settlements.view.any',
                'settlements.create',
                'settlements.update',
                'settlements.change_status',
                'settlements.upload_receipt',
                'settlements.view_receipt',
                'settlements.view_receipt.any',
                'settlements.view_all_teachers',
                'bank_accounts.manage',
            ],

            'category_manager' => [
                'dashboard.view',
                'categories.*',
            ],
            'level_manager' => [
                'dashboard.view',
                'levels.*',
            ],
            'status_manager' => [
                'dashboard.view',
                'statuses.*',
            ],

            'path_manager' => [
                'dashboard.view',
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
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'articles.view.any',
                'articles.list.any',
                'articles.overview.view.any',
                'articles.comments.view.any',
                'articles.stats.view',
                'comments.article.view.any',
            ],

            'article_manager_all' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view.any',
                'articles.view.any',
                'articles.list.any',
                'articles.create',
                'articles.update.any',
                'articles.delete.any',
                'articles.publish.any',
                'articles.unpublish.any',
                'articles.overview.view.any',
                'articles.comments.view.any',
                'articles.stats.view.any',
                'articles.categories.manage',
                'admin.search_user',
                'comments.article.view.any',
                'comments.article.moderate.any',
                'comments.article.reply.any',
                'comments.article.delete.any',
            ],

            'article_manager_own' => [
                'dashboard.view',
                'dashboard.view.own',
                'analytics.view.own',
                'articles.view.own',
                'articles.list.own',
                'articles.create',
                'articles.update.own',
                'articles.delete.own',
                'articles.publish.own',
                'articles.unpublish.own',
                'articles.overview.view.own',
                'articles.comments.view.own',
                'articles.stats.view.own',
                'uploads.image.editor',
                'comments.view.own',
                'comments.article.view.own',
                'comments.article.moderate.own',
                'comments.article.reply.own',
                'comments.article.delete.own',
            ],

            'file_manager' => [
                'dashboard.view',
                'files.manage',
                'uploads.image.editor',
            ],

            'video_operator' => [
                'dashboard.view',
                'videos.upload',
                'videos.process',
                'videos.upload.any',
                'videos.process.any',
                'videos.key.view',
                'videos.download.signed',
            ],

            'notification_manager' => [
                'dashboard.view',
                'notifications.*',
            ],

            'chat_moderator' => [
                'dashboard.view',
                'chat.messages.view',
                'chat.messages.edit',
                'chat.messages.delete',
                'chat.conversations.view',
                'messenger.settings.view',
            ],

            'messenger_manager' => [
                'dashboard.view',
                'chat.messages.view',
                'chat.messages.edit',
                'chat.messages.delete',
                'chat.conversations.view',
                'chat.contacts.view',
                'messenger.settings.view',
                'messenger.settings.update',
                'messenger.users.manage',
            ],

            'chat_viewer' => [
                'dashboard.view',
                'chat.messages.view',
                'chat.conversations.view',
                'chat.contacts.view',
            ],

            'marketing_manager' => [
                'dashboard.view',
                'marketing.manage',
                'marketing.update',
                'marketing.view',
                'support.inbox.view',
                'support.inbox.reply',
                'support.inbox.send',
                'support.inbox.delete',
                'support.inbox.move',
            ],

            'marketing_editor' => [
                'dashboard.view',
                'marketing.update',
            ],
            'marketing_viewer' => [
                'dashboard.view',
                'marketing.view',
            ],

            'seo_manager' => [
                'dashboard.view',
                'seo.manage',
                'seo.update',
                'seo.view',
            ],

            'seo_editor' => [
                'dashboard.view',
                'seo.update',
            ],
            'seo_viewer' => [
                'dashboard.view',
                'seo.view',
            ],

            'analytics_viewer' => [
                'dashboard.view',
                'dashboard.view.any',
                'analytics.view',
                'analytics.view.any',
            ],

            'editor_uploader' => [
                'dashboard.view',
                'uploads.image.editor',
                'files.manage',
            ],

            'discount_manager' => [
                'dashboard.view',
                'discounts.*',
            ],
            
            'plan_manager' => [
                'dashboard.view',
                'plans.*',
            ],

            'discount_analyst' => [
                'dashboard.view',
                'discounts.view',
                'discounts.show',
                'discounts.search_eligibility',
            ],

            'catalog_manager' => [
                'dashboard.view',
                'categories.*',
                'levels.*',
                'statuses.*',
                'paths.*',
                'missions.*',
                'mission-categories.manage',
            ],

            'mission_manager' => [
                'dashboard.view',
                'missions.*',
                'mission-categories.manage',
            ],

            'catalog_viewer' => [
                'dashboard.view',
                'categories.view',
                'levels.view',
                'statuses.view',
                'paths.view',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | BUILD permission_role INSERT
        |--------------------------------------------------------------------------
        */

        $roles = DB::table('roles')->pluck('id', 'name');
        $permissions = DB::table('permissions')->pluck('id', 'name');

        foreach (array_keys($map) as $roleName) {
            if (isset($roles[$roleName])) {
                DB::table('permission_role')->where('role_id', $roles[$roleName])->delete();
            }
        }

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
            ->upsert(
                $this->deduplicateRows($rows, ['role_id', 'permission_id']),
                ['role_id', 'permission_id'],
                ['updated_at']
            );

        $this->removeObsoletePermissions([
            'courses.create.own',
            'courses.create.any',
            'articles.create.own',
            'articles.create.any',
            'bank_accounts.view_users',
            'bank_accounts.update_users',
            'bank_accounts.delete_users',
        ]);

        $this->exportPermissionsCatalog();
    }

    /**
     * @param  string[]  $names
     */
    private function removeObsoletePermissions(array $names): void
    {
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permission_routes')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function exportPermissionsCatalog(): void
    {
        $perms = DB::table('permissions')->orderBy('name')->get(['name', 'label']);
        $grouped = [];

        foreach ($perms as $perm) {
            $root = explode('.', $perm->name)[0];
            $grouped[$root][] = $perm;
        }

        $contentScopedRoots = [
            'dashboard', 'analytics', 'courses', 'sections', 'episodes', 'videos',
            'certificates', 'quizzes', 'quiz_questions', 'articles', 'comments', 'payments',
        ];

        $baseOnlyCreate = ['courses.create', 'articles.create'];

        $lines = [
            '# فهرست دسترسی‌ها',
            '',
            'این فایل پس از اجرای `PermissionsAndRolesSeeder` به‌روز می‌شود.',
            '',
            '## قوانین اسکوپ',
            '',
            '- **ایجاد دوره/مقاله:** فقط پرمیشن پایه (`courses.create`, `articles.create`) — بدون `.own` / `.any`',
            '- **سایر عملیات محتوایی:** سه‌تایی `base` + `.own` + `.any` (در صورت نیاز)',
            '- **مدرس:** فقط `.own` برای مشاهده/ویرایش/حذف + `courses.create` / بدون دسترسی سراسری',
            '',
        ];

        ksort($grouped);

        foreach ($grouped as $root => $items) {
            $lines[] = '## '.$root;
            $lines[] = '';
            $lines[] = '| پرمیشن | برچسب | own | any |';
            $lines[] = '|--------|-------|-----|-----|';

            $names = collect($items)->pluck('name');
            $bases = $names->filter(function ($name) {
                return ! str_ends_with($name, '.own') && ! str_ends_with($name, '.any');
            })->sort()->values();

            foreach ($bases as $base) {
                if (! $names->contains($base)) {
                    continue;
                }
                $label = collect($items)->firstWhere('name', $base)?->label ?? '';
                $hasOwn = $names->contains($base.'.own') ? '✓' : '—';
                $hasAny = $names->contains($base.'.any') ? '✓' : '—';

                if (in_array($base, $baseOnlyCreate, true)) {
                    $hasOwn = '—';
                    $hasAny = '—';
                }

                $lines[] = sprintf('| `%s` | %s | %s | %s |', $base, $label, $hasOwn, $hasAny);
            }

            $lines[] = '';
        }

        file_put_contents(database_path('permissions-catalog.md'), implode(PHP_EOL, $lines).PHP_EOL);
    }
}

