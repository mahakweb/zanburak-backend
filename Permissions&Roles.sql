START TRANSACTION;

-- ROLES (مدیریت و عملیاتی)
INSERT IGNORE INTO roles (name, label, created_at, updated_at) VALUES
('administrator','مدیرکل',NOW(),NOW()),
('content_admin','مدیر محتوا',NOW(),NOW()),
('course_manager','مدیر دوره',NOW(),NOW()),
('community_manager','مدیر انجمن/نظرات',NOW(),NOW()),
('finance_manager','مدیر مالی',NOW(),NOW()),
('discount_manager','مدیر تخفیف',NOW(),NOW()),
('plan_manager','مدیر پلن',NOW(),NOW()),
('support','پشتیبان',NOW(),NOW()),
('moderator','ناظر',NOW(),NOW()),
('file_manager','مدیر فایل',NOW(),NOW()),
('notification_manager','مدیر اعلان',NOW(),NOW()),
('chat_moderator','ناظر گفتگو',NOW(),NOW()),
('marketing_manager','مدیر مارکتینگ',NOW(),NOW()),
('seo_manager','مدیر SEO',NOW(),NOW()),
('analytics_viewer','بیننده آمار',NOW(),NOW()),
('admin','مدیر',NOW(),NOW()),
('teacher','مدرس',NOW(),NOW()),
('student','دانشجو',NOW(),NOW()),
('course_viewer_all','مشاهده‌گر همه دوره‌ها',NOW(),NOW()),
('course_manager_all','مدیر همه دوره‌ها',NOW(),NOW()),
('course_manager_own','مدیر دوره‌های خود',NOW(),NOW()),
('teacher_finance_viewer','مشاهده مالی مدرس (پرداخت‌های مرتبط)',NOW(),NOW()),
('comments_moderator_all','مدیر نظرات (کامل)',NOW(),NOW()),
('comments_moderator_own','مدیر نظرات (دوره‌های خود)',NOW(),NOW()),
('catalog_manager','مدیر کاتالوگ (دسته/سطح/وضعیت/مسیر)',NOW(),NOW()),
('catalog_viewer','مشاهده‌گر کاتالوگ',NOW(),NOW()),
('category_manager','مدیر دسته‌بندی',NOW(),NOW()),
('level_manager','مدیر سطح',NOW(),NOW()),
('status_manager','مدیر وضعیت',NOW(),NOW()),
('path_manager','مدیر مسیر',NOW(),NOW()),
('article_viewer_all','مشاهده‌گر همه مقالات',NOW(),NOW()),
('article_manager_all','مدیر همه مقالات',NOW(),NOW()),
('article_manager_own','مدیر مقالات خود',NOW(),NOW());

-- Additional roles (specialized)
INSERT IGNORE INTO roles (name, label, created_at, updated_at) VALUES
('admin_auditor','ممیز ادمین (فقط مشاهده)',NOW(),NOW()),
('content_publisher','انتشاردهنده محتوا',NOW(),NOW()),
('video_operator','اپراتور ویدیو',NOW(),NOW()),
('support_lite','پشتیبان سبک',NOW(),NOW()),
('teacher_assistant','دستیار مدرس',NOW(),NOW()),
('discount_analyst','تحلیلگر تخفیف',NOW(),NOW()),
('payments_auditor','ممیز پرداخت',NOW(),NOW()),
('payments_operator','اپراتور پرداخت',NOW(),NOW()),
('chat_viewer','مشاهده‌گر چت',NOW(),NOW()),
('community_viewer','مشاهده‌گر انجمن/نظرات',NOW(),NOW()),
('editor_uploader','آپلودر ادیتور',NOW(),NOW()),
('marketing_viewer','مشاهده‌گر مارکتینگ',NOW(),NOW()),
('marketing_editor','ویرایشگر مارکتینگ',NOW(),NOW()),
('seo_viewer','مشاهده‌گر SEO',NOW(),NOW()),
('seo_editor','ویرایشگر SEO',NOW(),NOW()),
('security_admin','مدیر امنیت/دسترسی‌ها',NOW(),NOW());

-- PERMISSIONS
INSERT IGNORE INTO permissions (name,label,created_at,updated_at) VALUES
-- Dashboard/Admin
('dashboard.view','مشاهده داشبورد ادمین',NOW(),NOW()),
('analytics.view','مشاهده آمار/گزارش‌ها',NOW(),NOW()),

-- Users/Security
('users.view','مشاهده کاربران',NOW(),NOW()),
('users.create','ایجاد کاربر',NOW(),NOW()),
('users.update','ویرایش کاربر',NOW(),NOW()),
('users.deactivate','غیرفعال‌سازی کاربر',NOW(),NOW()),
('users.reactivate','فعال‌سازی مجدد کاربر',NOW(),NOW()),
('users.reset_password','ریست پسورد کاربر',NOW(),NOW()),
('users.manage_roles','مدیریت نقش‌های کاربر',NOW(),NOW()),
('users.manage_permissions','مدیریت دسترسی‌های کاربر',NOW(),NOW()),
('users.impersonate','لاگین به‌جای کاربر',NOW(),NOW()),
('users.sessions.view','مشاهده نشست‌های کاربر',NOW(),NOW()),
('users.sessions.terminate','خاتمه نشست‌های کاربر',NOW(),NOW()),
('admin.search_user','جستجوی کاربر در ادمین',NOW(),NOW()),

-- Courses
('courses.view','مشاهده دوره‌ها',NOW(),NOW()),
('courses.list','لیست دوره‌ها',NOW(),NOW()),
('courses.create','ایجاد دوره',NOW(),NOW()),
('courses.update','ویرایش دوره',NOW(),NOW()),
('courses.delete','حذف دوره',NOW(),NOW()),
('courses.publish','انتشار دوره',NOW(),NOW()),
('courses.unpublish','عدم انتشار دوره',NOW(),NOW()),
('courses.assign_user','اختصاص دوره به کاربر',NOW(),NOW()),
('courses.reorder_episodes','مرتب‌سازی قسمت‌ها',NOW(),NOW()),
('courses.overview.view','مشاهده خلاصه دوره',NOW(),NOW()),
('courses.overview.update','ویرایش خلاصه دوره',NOW(),NOW()),
('courses.comments.view','مشاهده نظرات دوره',NOW(),NOW()),
-- Courses (granular - own vs any)
('courses.view.own','مشاهده دوره‌های خود',NOW(),NOW()),
('courses.view.any','مشاهده همه دوره‌ها',NOW(),NOW()),
('courses.list.any','لیست همه دوره‌ها',NOW(),NOW()),
('courses.update.own','ویرایش دوره‌های خود',NOW(),NOW()),
('courses.update.any','ویرایش هر دوره‌ای',NOW(),NOW()),
('courses.delete.own','حذف دوره‌های خود',NOW(),NOW()),
('courses.delete.any','حذف هر دوره‌ای',NOW(),NOW()),
('courses.publish.own','انتشار/عدم انتشار دوره‌های خود',NOW(),NOW()),
('courses.publish.any','انتشار/عدم انتشار هر دوره‌ای',NOW(),NOW()),
('courses.assign_user.own','اختصاص دوره‌های خود به کاربر',NOW(),NOW()),
('courses.assign_user.any','اختصاص هر دوره‌ای به کاربر',NOW(),NOW()),
('courses.reorder_episodes.own','مرتب‌سازی قسمت‌های دوره‌های خود',NOW(),NOW()),
('courses.reorder_episodes.any','مرتب‌سازی قسمت‌های هر دوره‌ای',NOW(),NOW()),
('courses.comments.manage','مدیریت نظرات دوره',NOW(),NOW()),

-- Episodes
('episodes.create','ایجاد جلسه/قسمت',NOW(),NOW()),
('episodes.edit','ویرایش جلسه/قسمت',NOW(),NOW()),
('episodes.delete','حذف جلسه/قسمت',NOW(),NOW()),
('episodes.upload_file','آپلود فایل جلسه',NOW(),NOW()),
('episodes.status','تغییر وضعیت جلسه',NOW(),NOW()),
('episodes.get_for_edit','دریافت جلسه برای ویرایش',NOW(),NOW()),
('episodes.reorder','مرتب‌سازی جلسات',NOW(),NOW()),
-- Episodes (granular - own vs any)
('episodes.create.own','ایجاد جلسه برای دوره‌های خود',NOW(),NOW()),
('episodes.create.any','ایجاد جلسه برای هر دوره‌ای',NOW(),NOW()),
('episodes.edit.own','ویرایش جلسه دوره‌های خود',NOW(),NOW()),
('episodes.edit.any','ویرایش جلسه هر دوره‌ای',NOW(),NOW()),
('episodes.delete.own','حذف جلسه دوره‌های خود',NOW(),NOW()),
('episodes.delete.any','حذف جلسه هر دوره‌ای',NOW(),NOW()),
('episodes.reorder.own','مرتب‌سازی جلسات دوره‌های خود',NOW(),NOW()),
('episodes.reorder.any','مرتب‌سازی جلسات هر دوره‌ای',NOW(),NOW()),

-- Videos
('videos.upload','آپلود ویدیو',NOW(),NOW()),
('videos.process','پردازش ویدیو',NOW(),NOW()),
('videos.key.view','دریافت کلید ویدیو',NOW(),NOW()),
('videos.playlist.view','مشاهده پلی‌لیست',NOW(),NOW()),
('videos.download.signed','دانلود امضاشده',NOW(),NOW()),
-- Videos (granular - own vs any)
('videos.upload.own','آپلود ویدیو برای دوره‌های خود',NOW(),NOW()),
('videos.upload.any','آپلود ویدیو برای هر دوره‌ای',NOW(),NOW()),
('videos.process.own','پردازش ویدیو دوره‌های خود',NOW(),NOW()),
('videos.process.any','پردازش ویدیو هر دوره‌ای',NOW(),NOW()),

-- Categories / Levels / Statuses
('categories.view','مشاهده دسته‌بندی‌ها',NOW(),NOW()),
('categories.create','ایجاد دسته‌بندی',NOW(),NOW()),
('categories.edit','ویرایش دسته‌بندی',NOW(),NOW()),
('categories.delete','حذف دسته‌بندی',NOW(),NOW()),
('categories.upload_icon','آپلود آیکون دسته‌بندی',NOW(),NOW()),
('levels.view','مشاهده سطح‌ها',NOW(),NOW()),
('levels.create','ایجاد سطح',NOW(),NOW()),
('levels.update','ویرایش سطح',NOW(),NOW()),
('levels.delete','حذف سطح',NOW(),NOW()),
('statuses.view','مشاهده وضعیت‌ها',NOW(),NOW()),
('statuses.create','ایجاد وضعیت',NOW(),NOW()),
('statuses.update','ویرایش وضعیت',NOW(),NOW()),
('statuses.delete','حذف وضعیت',NOW(),NOW()),

-- Paths (Learning Tracks)
('paths.view','مشاهده مسیرها',NOW(),NOW()),
('paths.create','ایجاد مسیر',NOW(),NOW()),
('paths.edit','ویرایش مسیر',NOW(),NOW()),
('paths.update','به‌روزرسانی مسیر',NOW(),NOW()),
('paths.delete','حذف مسیر',NOW(),NOW()),
('paths.remove_file','حذف فایل مسیر',NOW(),NOW()),
('paths.upload.poster','آپلود پوستر مسیر',NOW(),NOW()),
('paths.upload.icon','آپلود آیکون مسیر',NOW(),NOW()),
('paths.upload.trailer','آپلود تریلر مسیر',NOW(),NOW()),
('paths.search.courses','جستجوی دوره‌ها برای مسیر',NOW(),NOW()),
('paths.search.paths','جستجوی مسیرها',NOW(),NOW()),
('paths.courses.attach','افزودن دوره به مسیر',NOW(),NOW()),
('paths.courses.detach','حذف دوره از مسیر',NOW(),NOW()),

-- Projects (Admin)
('projects.view','مشاهده پروژه‌ها',NOW(),NOW()),
('projects.show','نمایش پروژه',NOW(),NOW()),
('projects.update','ویرایش پروژه',NOW(),NOW()),
('projects.delete','حذف پروژه',NOW(),NOW()),

-- Plans
('plans.view','مشاهده پلن‌ها',NOW(),NOW()),
('plans.create','ایجاد پلن',NOW(),NOW()),
('plans.show','نمایش پلن',NOW(),NOW()),
('plans.update','ویرایش پلن',NOW(),NOW()),
('plans.delete','حذف پلن',NOW(),NOW()),

-- Articles (Blog/Knowledge)
('articles.view','مشاهده مقالات',NOW(),NOW()),
('articles.list','لیست مقالات',NOW(),NOW()),
('articles.create','ایجاد مقاله',NOW(),NOW()),
('articles.update','ویرایش مقاله',NOW(),NOW()),
('articles.delete','حذف مقاله',NOW(),NOW()),
('articles.publish','انتشار مقاله',NOW(),NOW()),
('articles.unpublish','عدم انتشار مقاله',NOW(),NOW()),
('articles.overview.view','مشاهده خلاصه مقاله',NOW(),NOW()),
('articles.comments.view','مشاهده نظرات مقاله',NOW(),NOW()),
-- Articles (granular - own vs any)
('articles.view.own','مشاهده مقالات خود',NOW(),NOW()),
('articles.view.any','مشاهده همه مقالات',NOW(),NOW()),
('articles.list.any','لیست همه مقالات',NOW(),NOW()),
('articles.update.own','ویرایش مقالات خود',NOW(),NOW()),
('articles.update.any','ویرایش هر مقاله‌ای',NOW(),NOW()),
('articles.delete.own','حذف مقالات خود',NOW(),NOW()),
('articles.delete.any','حذف هر مقاله‌ای',NOW(),NOW()),
('articles.publish.own','انتشار/عدم انتشار مقالات خود',NOW(),NOW()),
('articles.publish.any','انتشار/عدم انتشار هر مقاله‌ای',NOW(),NOW()),

-- Payments
('payments.view','مشاهده پرداخت‌ها',NOW(),NOW()),
('payments.stats','آمار پرداخت‌ها',NOW(),NOW()),
('payments.export','خروجی پرداخت‌ها',NOW(),NOW()),
('payments.details','جزئیات پرداخت',NOW(),NOW()),
('payments.update_status','بروزرسانی وضعیت پرداخت',NOW(),NOW()),
('payments.delete','حذف پرداخت',NOW(),NOW()),
('payments.create','ایجاد پرداخت',NOW(),NOW()),
('payments.search','جستجوی پرداخت/آیتم‌ها',NOW(),NOW()),
('payments.search.users','جستجوی کاربران برای پرداخت',NOW(),NOW()),
('payments.search.courses','جستجوی دوره‌ها برای پرداخت',NOW(),NOW()),
('payments.search.plans','جستجوی پلن‌ها برای پرداخت',NOW(),NOW()),
('payments.search.paths','جستجوی مسیرها برای پرداخت',NOW(),NOW()),
-- Payments (granular)
('payments.view.own','مشاهده پرداخت‌های مرتبط با دوره‌های خود',NOW(),NOW()),
('payments.view.any','مشاهده همه پرداخت‌ها',NOW(),NOW()),
('payments.export.any','خروجی گرفتن از همه پرداخت‌ها',NOW(),NOW()),
('payments.delete.any','حذف هر پرداختی',NOW(),NOW()),
('payments.update_status.any','بروزرسانی وضعیت هر پرداختی',NOW(),NOW()),

-- Discounts
('discounts.view','مشاهده کدتخفیف‌ها',NOW(),NOW()),
('discounts.show','نمایش کدتخفیف',NOW(),NOW()),
('discounts.create','ایجاد کدتخفیف',NOW(),NOW()),
('discounts.update','ویرایش کدتخفیف',NOW(),NOW()),
('discounts.toggle_status','تغییر وضعیت کدتخفیف',NOW(),NOW()),
('discounts.delete','حذف کدتخفیف',NOW(),NOW()),
('discounts.search_eligibility','جستجوی صلاحیت تخفیف',NOW(),NOW()),

-- Panel/Profile/VIP/Access Tokens
('panel.view','مشاهده پنل کاربر',NOW(),NOW()),
('panel.financial','بخش مالی پنل',NOW(),NOW()),
('vip.view','مشاهده VIP',NOW(),NOW()),
('vip.purchase','خرید VIP',NOW(),NOW()),
('profile.mobile.info','دریافت موبایل پروفایل',NOW(),NOW()),
('profile.mobile.send_otp','ارسال کد موبایل',NOW(),NOW()),
('profile.mobile.verify_otp','تایید کد موبایل',NOW(),NOW()),
('profile.change_password','تغییر رمز پروفایل',NOW(),NOW()),
('profile.preferences.view','مشاهده ترجیحات',NOW(),NOW()),
('profile.preferences.update','به‌روزرسانی ترجیحات',NOW(),NOW()),
('profile.data.view','دریافت اطلاعات کاربر',NOW(),NOW()),
('profile.update','ویرایش پروفایل',NOW(),NOW()),
('profile.upload.avatar','تغییر عکس پروفایل',NOW(),NOW()),
('profile.upload.cover','تغییر کاور پروفایل',NOW(),NOW()),
('profile.tokens.view','مشاهده توکن‌های دسترسی',NOW(),NOW()),
('profile.tokens.terminate','خاتمه یک نشست',NOW(),NOW()),
('profile.tokens.terminate_all','خاتمه همه نشست‌ها',NOW(),NOW()),

-- Comments/Community/Discuss
('comments.view','مشاهده نظرات',NOW(),NOW()),
('comments.moderate','مدیریت تایید/رد نظر',NOW(),NOW()),
('comments.reply','ارسال پاسخ به نظر',NOW(),NOW()),
-- Comments (granular - own vs any)
('comments.view.own','مشاهده نظرات دوره‌های خود',NOW(),NOW()),
('comments.view.any','مشاهده نظرات همه دوره‌ها',NOW(),NOW()),
('comments.moderate.own','تایید/رد نظرات دوره‌های خود',NOW(),NOW()),
('comments.moderate.any','تایید/رد نظرات همه دوره‌ها',NOW(),NOW()),
('comments.reply.own','ارسال پاسخ در نظرات دوره‌های خود',NOW(),NOW()),
('comments.reply.any','ارسال پاسخ در نظرات همه دوره‌ها',NOW(),NOW()),
('comments.delete.own','حذف نظر در دوره‌های خود',NOW(),NOW()),
('comments.delete.any','حذف هر نظر',NOW(),NOW()),

-- Comments by entity (scoped)
-- Course comments
('comments.course.view.own','مشاهده نظرات دوره‌های خود (دوره)',NOW(),NOW()),
('comments.course.view.any','مشاهده نظرات همه دوره‌ها (دوره)',NOW(),NOW()),
('comments.course.moderate.own','تایید/رد نظرات دوره‌های خود (دوره)',NOW(),NOW()),
('comments.course.moderate.any','تایید/رد نظرات همه دوره‌ها (دوره)',NOW(),NOW()),
('comments.course.reply.own','ارسال پاسخ در نظرات دوره‌های خود (دوره)',NOW(),NOW()),
('comments.course.reply.any','ارسال پاسخ در نظرات همه دوره‌ها (دوره)',NOW(),NOW()),
('comments.course.delete.own','حذف نظر در دوره‌های خود (دوره)',NOW(),NOW()),
('comments.course.delete.any','حذف هر نظر (دوره)',NOW(),NOW()),

-- Episode comments
('comments.episode.view.own','مشاهده نظرات دوره‌های خود (جلسه)',NOW(),NOW()),
('comments.episode.view.any','مشاهده نظرات همه دوره‌ها (جلسه)',NOW(),NOW()),
('comments.episode.moderate.own','تایید/رد نظرات دوره‌های خود (جلسه)',NOW(),NOW()),
('comments.episode.moderate.any','تایید/رد نظرات همه دوره‌ها (جلسه)',NOW(),NOW()),
('comments.episode.reply.own','ارسال پاسخ در نظرات دوره‌های خود (جلسه)',NOW(),NOW()),
('comments.episode.reply.any','ارسال پاسخ در نظرات همه دوره‌ها (جلسه)',NOW(),NOW()),
('comments.episode.delete.own','حذف نظر در دوره‌های خود (جلسه)',NOW(),NOW()),
('comments.episode.delete.any','حذف هر نظر (جلسه)',NOW(),NOW()),

-- Path comments (no ownership model → only any)
('comments.path.view.any','مشاهده نظرات همه مسیرها',NOW(),NOW()),
('comments.path.moderate.any','تایید/رد نظرات همه مسیرها',NOW(),NOW()),
('comments.path.reply.any','ارسال پاسخ در نظرات همه مسیرها',NOW(),NOW()),
('comments.path.delete.any','حذف هر نظر (مسیر)',NOW(),NOW()),

-- Article comments (future)
('comments.article.view.own','مشاهده نظرات مقالات خود',NOW(),NOW()),
('comments.article.view.any','مشاهده نظرات همه مقالات',NOW(),NOW()),
('comments.article.moderate.own','تایید/رد نظرات مقالات خود',NOW(),NOW()),
('comments.article.moderate.any','تایید/رد نظرات همه مقالات',NOW(),NOW()),
('comments.article.reply.own','ارسال پاسخ در نظرات مقالات خود',NOW(),NOW()),
('comments.article.reply.any','ارسال پاسخ در نظرات همه مقالات',NOW(),NOW()),
('comments.article.delete.own','حذف نظر در مقالات خود',NOW(),NOW()),
('comments.article.delete.any','حذف هر نظر (مقاله)',NOW(),NOW()),
('discuss.list','لیست پرسش‌ها',NOW(),NOW()),
('discuss.show','نمایش پرسش/پاسخ‌ها',NOW(),NOW()),
('discuss.create','ایجاد پرسش',NOW(),NOW()),
('discuss.answer.create','ایجاد پاسخ',NOW(),NOW()),
('discuss.update','ویرایش پرسش/پاسخ',NOW(),NOW()),
('discuss.delete','حذف پرسش/پاسخ',NOW(),NOW()),
('discuss.like_dislike','پسند/ناپسند',NOW(),NOW()),
('discuss.toggle_pin','سنجاق پرسش',NOW(),NOW()),

-- Interactions
('bookmark.toggle','بوکمارک',NOW(),NOW()),
('like.toggle','پسند',NOW(),NOW()),
('follow.toggle','دنبال‌کردن',NOW(),NOW()),
('report.send','ارسال گزارش',NOW(),NOW()),
('rating.set','ثبت امتیاز',NOW(),NOW()),

-- Notifications
('notifications.view','مشاهده اعلان‌ها',NOW(),NOW()),
('notifications.details','جزئیات اعلان',NOW(),NOW()),
('notifications.unread','تعداد خوانده‌نشده',NOW(),NOW()),
('notifications.delete','حذف اعلان',NOW(),NOW()),

-- Chat
('chat.messages.view','مشاهده پیام‌ها',NOW(),NOW()),
('chat.messages.send','ارسال پیام',NOW(),NOW()),
('chat.messages.edit','ویرایش پیام',NOW(),NOW()),
('chat.messages.delete','حذف پیام',NOW(),NOW()),
('chat.messages.mark_read','خوانده‌شدن پیام',NOW(),NOW()),
('chat.conversations.view','مشاهده گفتگوها',NOW(),NOW()),
('chat.contacts.view','مشاهده مخاطبین',NOW(),NOW()),

-- Video Views / Downloads
('videoviews.get','دریافت وضعیت تماشا',NOW(),NOW()),
('videoviews.set','ثبت وضعیت تماشا',NOW(),NOW()),
('episode.download.check','بررسی مجوز دانلود',NOW(),NOW()),
('episode.download.signed','دانلود امضاشده',NOW(),NOW()),

-- File Manager / Upload
('files.manage','مدیریت فایل‌ها',NOW(),NOW()),
('uploads.image.editor','آپلود تصویر ادیتور',NOW(),NOW()),

-- Security / Routes Mapping (ادمین)
('security.access.view','مشاهده دسترسی‌ها',NOW(),NOW()),
('security.access.manage','مدیریت دسترسی‌ها',NOW(),NOW()),
('security.routes.view','مشاهده مپ روت→پرمیشن',NOW(),NOW()),
('security.routes.manage','مدیریت مپ روت→پرمیشن',NOW(),NOW()),

-- Marketing / SEO (future-proof)
('marketing.manage','مدیریت مارکتینگ/بنرها',NOW(),NOW()),
('marketing.view','مشاهده مارکتینگ/بنرها',NOW(),NOW()),
('marketing.update','ویرایش مارکتینگ/بنرها',NOW(),NOW()),
('seo.manage','مدیریت SEO',NOW(),NOW()),
('seo.view','مشاهده SEO',NOW(),NOW()),
('seo.update','ویرایش SEO',NOW(),NOW());

-- ROLE → PERMISSIONS

-- Administrator gets ALL
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r CROSS JOIN permissions p WHERE r.name='administrator';

-- Content Admin: دوره/جلسه/ویدیو/دسته/سطح/وضعیت/مسیر/کامنت/دیسکس/آپلود/فایل
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(courses\\.|episodes\\.|videos\\.|categories\\.|levels\\.|statuses\\.|paths\\.|comments\\.|discuss\\.|uploads\\.|files\\.)'
WHERE r.name='content_admin';

-- Course Manager: فقط دامنه دوره/جلسه/ویدیو
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(courses\\.|episodes\\.|videos\\.)'
WHERE r.name='course_manager';

-- Course Manager (Own): فقط دامنه دوره/جلسه/ویدیو برای دوره‌های خود
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON (
    (p.name REGEXP '^(courses\\.|episodes\\.|videos\\.)' AND p.name LIKE '%.own')
    OR p.name IN ('courses.create','courses.view','courses.comments.view','courses.overview.view')
)
WHERE r.name='course_manager_own';

-- Community Manager: کامنت/دیسکس/تعاملات/گزارش/نوتیف/چت مشاهده
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(comments\\.|discuss\\.|bookmark\\.|like\\.|follow\\.|report\\.|notifications\\.|chat\\.)'
WHERE r.name='community_manager';

-- Finance Manager: پرداخت/تخفیف/پلن
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(payments\\.|discounts\\.|plans\\.)'
WHERE r.name='finance_manager';

-- Teacher Finance Viewer: فقط مشاهده پرداخت‌های مرتبط با دوره‌های خودش
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('payments.view','payments.view.own','payments.details','payments.search','payments.search.users','payments.search.courses','payments.search.paths')
WHERE r.name='teacher_finance_viewer';

-- Course Viewer (All): مشاهده همه دوره‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('courses.view','courses.view.any','courses.list','courses.list.any','courses.overview.view','courses.comments.view')
WHERE r.name='course_viewer_all';

-- Course Manager (All): مدیریت کامل همه دوره‌ها/جلسات/ویدیوها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(courses\\.|episodes\\.|videos\\.)'
WHERE r.name='course_manager_all';

-- Comments Moderator (All): مدیریت کامل نظرات
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'comments.%'
WHERE r.name='comments_moderator_all';

-- Comments Moderator (Own): نظرات مرتبط با دوره‌های خود
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON (
  p.name LIKE 'comments.%' AND p.name LIKE '%.own'
)
WHERE r.name='comments_moderator_own';

-- Catalog Manager: دسته/سطح/وضعیت/مسیر
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(categories\\.|levels\\.|statuses\\.|paths\\.)'
WHERE r.name='catalog_manager';

-- Catalog Viewer: فقط مشاهده در دامنه‌های کاتالوگ
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN (
  'categories.view','levels.view','statuses.view','paths.view','paths.search.courses','paths.search.paths'
)
WHERE r.name='catalog_viewer';

-- Category Manager: فقط دسته‌بندی‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'categories.%'
WHERE r.name='category_manager';

-- Level Manager: فقط سطح‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'levels.%'
WHERE r.name='level_manager';

-- Status Manager: فقط وضعیت‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'statuses.%'
WHERE r.name='status_manager';

-- Path Manager: فقط مسیرها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'paths.%'
WHERE r.name='path_manager';

-- Article roles
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name REGEXP '^(articles\\.)'
WHERE r.name='article_manager_all';

INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON (
  (p.name LIKE 'articles.%' AND p.name LIKE '%.own')
  OR p.name IN ('articles.view','articles.create','articles.comments.view','articles.overview.view')
)
WHERE r.name='article_manager_own';

INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('articles.view','articles.view.any','articles.list','articles.list.any','articles.overview.view','articles.comments.view')
WHERE r.name='article_viewer_all';

-- Discount Manager: فقط تخفیف‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'discounts.%'
WHERE r.name='discount_manager';

-- Plan Manager: فقط پلن‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'plans.%'
WHERE r.name='plan_manager';

-- Support: کاربران پایه + مشاهده پرداخت‌ها + جستجو کاربر + نشست‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('users.view','users.update','admin.search_user','users.sessions.view','users.sessions.terminate','payments.view')
WHERE r.name='support';

-- Moderator: تمرکز روی نظارت گفتگو/نظرات/چت
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('comments.moderate','discuss.update','discuss.delete','chat.messages.delete','chat.messages.edit')
WHERE r.name='moderator';

-- File Manager: مدیریت فایل‌ها و آپلود ادیتور
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('files.manage','uploads.image.editor')
WHERE r.name='file_manager';

-- Notification Manager: اعلان‌ها
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'notifications.%'
WHERE r.name='notification_manager';

-- Chat Moderator
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name LIKE 'chat.%'
WHERE r.name='chat_moderator';

-- Marketing Manager / SEO Manager / Analytics Viewer
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r JOIN permissions p ON p.name='marketing.manage' WHERE r.name='marketing_manager';
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r JOIN permissions p ON p.name='seo.manage' WHERE r.name='seo_manager';
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r JOIN permissions p ON p.name IN ('analytics.view','payments.stats') WHERE r.name='analytics_viewer';

-- Admin Auditor: read-only across key domains
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN (
  'dashboard.view','analytics.view',
  'users.view','admin.search_user',
  'payments.view','payments.view.any','payments.details','payments.export.any','payments.stats',
  'categories.view','levels.view','statuses.view','paths.view','plans.view','discounts.view',
  'security.routes.view','security.access.view'
)
WHERE r.name='admin_auditor';

-- Content Publisher: publish/unpublish courses & articles (any) + listing/view
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN (
  'courses.view','courses.view.any','courses.list','courses.list.any','courses.publish.any',
  'articles.view','articles.view.any','articles.list','articles.list.any','articles.publish.any'
)
WHERE r.name='content_publisher';

-- Video Operator: video workflows
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN (
  'videos.upload.any','videos.process.any','videos.key.view','videos.playlist.view','episodes.upload_file'
)
WHERE r.name='video_operator';

-- Support Lite: restricted support
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('users.view','admin.search_user','users.sessions.view','payments.view')
WHERE r.name='support_lite';

-- Teacher Assistant: manage episodes/comments for own courses
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN (
  'courses.view','courses.view.own','courses.overview.view',
  'episodes.create.own','episodes.edit.own','episodes.reorder.own','episodes.upload_file',
  'comments.view.own','comments.reply.own'
)
WHERE r.name='teacher_assistant';

-- Discount Analyst: read-only discounts
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('discounts.view','discounts.show','discounts.search_eligibility')
WHERE r.name='discount_analyst';

-- Payments Auditor: read-only payments
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('payments.view','payments.view.any','payments.details','payments.export.any','payments.stats')
WHERE r.name='payments_auditor';

-- Payments Operator: operational payments without discounts/plans
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN (
  'payments.view','payments.view.any','payments.details','payments.update_status.any','payments.delete.any',
  'payments.search','payments.search.users','payments.search.courses','payments.search.plans','payments.search.paths'
)
WHERE r.name='payments_operator';

-- Chat Viewer
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('chat.messages.view','chat.conversations.view','chat.contacts.view')
WHERE r.name='chat_viewer';

-- Community Viewer
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('comments.view.any','discuss.list','discuss.show','notifications.view')
WHERE r.name='community_viewer';

-- Editor Uploader (only editor image upload)
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('uploads.image.editor')
WHERE r.name='editor_uploader';

-- Marketing roles
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('marketing.view')
WHERE r.name='marketing_viewer';

INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('marketing.view','marketing.update')
WHERE r.name='marketing_editor';

-- SEO roles
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('seo.view')
WHERE r.name='seo_viewer';

INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('seo.view','seo.update')
WHERE r.name='seo_editor';

-- Security Admin
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('security.routes.view','security.routes.manage','security.access.view','security.access.manage')
WHERE r.name='security_admin';

-- Teacher: تولید محتوا محدود (دوره/جلسه/ویدیو)
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('courses.view','courses.create','courses.update','episodes.create','episodes.edit','episodes.upload_file','videos.upload','videos.process')
WHERE r.name='teacher';

-- Student: بدون پرمیشن ادمین

COMMIT;