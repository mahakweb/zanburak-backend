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
('teacher','مدرس',NOW(),NOW()),
('student','دانشجو',NOW(),NOW());

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

-- Episodes
('episodes.create','ایجاد جلسه/قسمت',NOW(),NOW()),
('episodes.edit','ویرایش جلسه/قسمت',NOW(),NOW()),
('episodes.delete','حذف جلسه/قسمت',NOW(),NOW()),
('episodes.upload_file','آپلود فایل جلسه',NOW(),NOW()),
('episodes.status','تغییر وضعیت جلسه',NOW(),NOW()),
('episodes.get_for_edit','دریافت جلسه برای ویرایش',NOW(),NOW()),
('episodes.reorder','مرتب‌سازی جلسات',NOW(),NOW()),

-- Videos
('videos.upload','آپلود ویدیو',NOW(),NOW()),
('videos.process','پردازش ویدیو',NOW(),NOW()),
('videos.key.view','دریافت کلید ویدیو',NOW(),NOW()),
('videos.playlist.view','مشاهده پلی‌لیست',NOW(),NOW()),
('videos.download.signed','دانلود امضاشده',NOW(),NOW()),

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
('comments.moderate','مدیریت تایید/رد نظر',NOW(),NOW()),
('comments.reply','ارسال پاسخ به نظر',NOW(),NOW()),
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
('seo.manage','مدیریت SEO',NOW(),NOW());

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

-- Teacher: تولید محتوا محدود (دوره/جلسه/ویدیو)
INSERT IGNORE INTO permission_role (role_id, permission_id, created_at, updated_at)
SELECT r.id, p.id, NOW(), NOW() FROM roles r
JOIN permissions p ON p.name IN ('courses.view','courses.create','courses.update','episodes.create','episodes.edit','episodes.upload_file','videos.upload','videos.process')
WHERE r.name='teacher';

-- Student: بدون پرمیشن ادمین

COMMIT;