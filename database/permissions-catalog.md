# فهرست دسترسی‌ها

این فایل پس از اجرای `PermissionsAndRolesSeeder` به‌روز می‌شود.

## قوانین اسکوپ

- **ایجاد دوره/مقاله:** فقط پرمیشن پایه (`courses.create`, `articles.create`) — بدون `.own` / `.any`
- **سایر عملیات محتوایی:** سه‌تایی `base` + `.own` + `.any` (در صورت نیاز)
- **مدرس:** فقط `.own` برای مشاهده/ویرایش/حذف + `courses.create` / بدون دسترسی سراسری

## admin

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `admin.search_user` | جستجوی کاربر در ادمین | — | — |

## analytics

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `analytics.view` | مشاهده آمار/گزارش‌ها | ✓ | ✓ |

## articles

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `articles.categories.manage` | مدیریت دسته‌بندی مقالات | — | — |
| `articles.comments.view` | مشاهده نظرات مقاله | ✓ | ✓ |
| `articles.create` | ایجاد مقاله | — | — |
| `articles.delete` | حذف مقاله | ✓ | ✓ |
| `articles.list` | لیست مقالات | ✓ | ✓ |
| `articles.overview.view` | مشاهده خلاصه مقاله | ✓ | ✓ |
| `articles.publish` | انتشار مقاله | ✓ | ✓ |
| `articles.stats.view` | مشاهده آمار مقالات | ✓ | ✓ |
| `articles.unpublish` | عدم انتشار مقاله | ✓ | ✓ |
| `articles.update` | ویرایش مقاله | ✓ | ✓ |
| `articles.view` | مشاهده مقالات | ✓ | ✓ |

## bookmark

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `bookmark.toggle` | بوکمارک | — | — |

## categories

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `categories.create` | ایجاد دسته‌بندی | — | — |
| `categories.delete` | حذف دسته‌بندی | — | — |
| `categories.edit` | ویرایش دسته‌بندی | — | — |
| `categories.upload_icon` | آپلود آیکون دسته‌بندی | — | — |
| `categories.view` | مشاهده دسته‌بندی‌ها | — | — |

## certificates

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `certificates.create` | صدور گواهینامه | ✓ | ✓ |
| `certificates.delete` | حذف/ابطال گواهینامه | ✓ | ✓ |
| `certificates.export` | خروجی گواهینامه‌ها | ✓ | ✓ |
| `certificates.templates.create` | ایجاد قالب گواهینامه | — | ✓ |
| `certificates.templates.delete` | حذف قالب گواهینامه | — | ✓ |
| `certificates.templates.update` | ویرایش قالب گواهینامه | — | ✓ |
| `certificates.templates.view` | مشاهده قالب گواهینامه | — | ✓ |
| `certificates.update` | ویرایش گواهینامه | ✓ | ✓ |
| `certificates.view` | مشاهده گواهینامه‌ها | ✓ | ✓ |

## chat

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `chat.contacts.view` | مشاهده مخاطبین | — | — |
| `chat.conversations.view` | مشاهده گفتگوها | — | — |
| `chat.messages.delete` | حذف پیام | — | — |
| `chat.messages.edit` | ویرایش پیام | — | — |
| `chat.messages.mark_read` | خوانده‌شدن پیام | — | — |
| `chat.messages.send` | ارسال پیام | — | — |
| `chat.messages.view` | مشاهده پیام‌ها | — | — |

## comments

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `comments.moderate` | مدیریت تایید/رد نظر | ✓ | ✓ |
| `comments.reply` | ارسال پاسخ به نظر | ✓ | ✓ |
| `comments.view` | مشاهده نظرات | ✓ | ✓ |

## courses

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `courses.assign_user` | اختصاص دوره به کاربر | ✓ | ✓ |
| `courses.comments.manage` | مدیریت نظرات دوره | ✓ | ✓ |
| `courses.comments.view` | مشاهده نظرات دوره | ✓ | ✓ |
| `courses.create` | ایجاد دوره | — | — |
| `courses.delete` | حذف دوره | ✓ | ✓ |
| `courses.list` | لیست دوره‌ها | ✓ | ✓ |
| `courses.overview.update` | ویرایش خلاصه دوره | ✓ | ✓ |
| `courses.overview.view` | مشاهده خلاصه دوره | ✓ | ✓ |
| `courses.publish` | انتشار دوره | ✓ | ✓ |
| `courses.reorder_episodes` | مرتب‌سازی قسمت‌ها | ✓ | ✓ |
| `courses.unpublish` | عدم انتشار دوره | ✓ | ✓ |
| `courses.update` | ویرایش دوره | ✓ | ✓ |
| `courses.view` | مشاهده دوره‌ها | ✓ | ✓ |

## dashboard

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `dashboard.view` | مشاهده داشبورد ادمین | ✓ | ✓ |

## discounts

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `discounts.create` | ایجاد کدتخفیف | — | — |
| `discounts.delete` | حذف کدتخفیف | — | — |
| `discounts.search_eligibility` | جستجوی صلاحیت تخفیف | — | — |
| `discounts.show` | نمایش کدتخفیف | — | — |
| `discounts.toggle_status` | تغییر وضعیت کدتخفیف | — | — |
| `discounts.update` | ویرایش کدتخفیف | — | — |
| `discounts.view` | مشاهده کدتخفیف‌ها | — | — |

## discuss

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `discuss.answer.create` | ایجاد پاسخ | — | — |
| `discuss.create` | ایجاد پرسش | — | — |
| `discuss.delete` | حذف پرسش/پاسخ | — | — |
| `discuss.like_dislike` | پسند/ناپسند | — | — |
| `discuss.list` | لیست پرسش‌ها | — | — |
| `discuss.show` | نمایش پرسش/پاسخ‌ها | — | — |
| `discuss.toggle_pin` | سنجاق پرسش | — | — |
| `discuss.update` | ویرایش پرسش/پاسخ | — | — |

## episode

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `episode.download.check` | بررسی مجوز دانلود | — | — |
| `episode.download.signed` | دانلود امضاشده | — | — |

## episodes

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `episodes.create` | ایجاد جلسه/قسمت | ✓ | ✓ |
| `episodes.delete` | حذف جلسه/قسمت | ✓ | ✓ |
| `episodes.edit` | ویرایش جلسه/قسمت | ✓ | ✓ |
| `episodes.get_for_edit` | دریافت جلسه برای ویرایش | — | — |
| `episodes.reorder` | مرتب‌سازی جلسات | ✓ | ✓ |
| `episodes.status` | تغییر وضعیت جلسه | ✓ | ✓ |
| `episodes.upload_file` | آپلود فایل جلسه | ✓ | ✓ |
| `episodes.view` | مشاهده جلسه/قسمت | ✓ | ✓ |

## files

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `files.manage` | مدیریت فایل‌ها | — | — |

## follow

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `follow.toggle` | دنبال‌کردن | — | — |

## levels

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `levels.create` | ایجاد سطح | — | — |
| `levels.delete` | حذف سطح | — | — |
| `levels.update` | ویرایش سطح | — | — |
| `levels.view` | مشاهده سطح‌ها | — | — |

## like

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `like.toggle` | پسند | — | — |

## marketing

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `marketing.manage` | مدیریت مارکتینگ/بنرها | — | — |
| `marketing.update` | ویرایش مارکتینگ/بنرها | — | — |
| `marketing.view` | مشاهده مارکتینگ/بنرها | — | — |

## mission-categories

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `mission-categories.manage` | مدیریت دسته‌بندی ماموریت‌ها | — | — |

## missions

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `missions.create` | ایجاد ماموریت | — | — |
| `missions.delete` | حذف ماموریت | — | — |
| `missions.update` | ویرایش ماموریت | — | — |
| `missions.view` | مشاهده ماموریت‌ها | — | — |

## notifications

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `notifications.delete` | حذف اعلان | — | — |
| `notifications.details` | جزئیات اعلان | — | — |
| `notifications.unread` | تعداد خوانده‌نشده | — | — |
| `notifications.view` | مشاهده اعلان‌ها | — | — |

## panel

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `panel.financial` | بخش مالی پنل | — | — |
| `panel.view` | مشاهده پنل کاربر | — | — |

## paths

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `paths.courses.attach` | افزودن دوره به مسیر | — | — |
| `paths.courses.detach` | حذف دوره از مسیر | — | — |
| `paths.create` | ایجاد مسیر | — | — |
| `paths.delete` | حذف مسیر | — | — |
| `paths.edit` | ویرایش مسیر | — | — |
| `paths.remove_file` | حذف فایل مسیر | — | — |
| `paths.search.courses` | جستجوی دوره‌ها برای مسیر | — | — |
| `paths.search.paths` | جستجوی مسیرها | — | — |
| `paths.update` | به‌روزرسانی مسیر | — | — |
| `paths.upload.icon` | آپلود آیکون مسیر | — | — |
| `paths.upload.poster` | آپلود پوستر مسیر | — | — |
| `paths.upload.trailer` | آپلود تریلر مسیر | — | — |
| `paths.view` | مشاهده مسیرها | — | — |

## payments

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `payments.create` | ایجاد پرداخت | ✓ | ✓ |
| `payments.delete` | حذف پرداخت | ✓ | ✓ |
| `payments.details` | جزئیات پرداخت | ✓ | ✓ |
| `payments.export` | خروجی پرداخت‌ها | ✓ | ✓ |
| `payments.search` | جستجوی پرداخت/آیتم‌ها | — | — |
| `payments.search.courses` | جستجوی دوره‌ها برای پرداخت | — | — |
| `payments.search.paths` | جستجوی مسیرها برای پرداخت | — | — |
| `payments.search.plans` | جستجوی پلن‌ها برای پرداخت | — | — |
| `payments.search.users` | جستجوی کاربران برای پرداخت | — | — |
| `payments.stats` | آمار پرداخت‌ها | ✓ | ✓ |
| `payments.update_status` | بروزرسانی وضعیت پرداخت | ✓ | ✓ |
| `payments.view` | مشاهده پرداخت‌ها | ✓ | ✓ |

## plans

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `plans.create` | ایجاد پلن | — | — |
| `plans.delete` | حذف پلن | — | — |
| `plans.show` | نمایش پلن | — | — |
| `plans.update` | ویرایش پلن | — | — |
| `plans.view` | مشاهده پلن‌ها | — | — |

## profile

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `profile.change_password` | تغییر رمز پروفایل | — | — |
| `profile.data.view` | دریافت اطلاعات کاربر | — | — |
| `profile.mobile.info` | دریافت موبایل پروفایل | — | — |
| `profile.mobile.send_otp` | ارسال کد موبایل | — | — |
| `profile.mobile.verify_otp` | تایید کد موبایل | — | — |
| `profile.preferences.update` | به‌روزرسانی ترجیحات | — | — |
| `profile.preferences.view` | مشاهده ترجیحات | — | — |
| `profile.tokens.terminate` | خاتمه یک نشست | — | — |
| `profile.tokens.terminate_all` | خاتمه همه نشست‌ها | — | — |
| `profile.tokens.view` | مشاهده توکن‌های دسترسی | — | — |
| `profile.update` | ویرایش پروفایل | — | — |
| `profile.upload.avatar` | تغییر عکس پروفایل | — | — |
| `profile.upload.cover` | تغییر کاور پروفایل | — | — |

## quiz_questions

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `quiz_questions.create` | ایجاد سوال | ✓ | ✓ |
| `quiz_questions.delete` | حذف سوال | ✓ | ✓ |
| `quiz_questions.update` | ویرایش سوال | ✓ | ✓ |
| `quiz_questions.view` | مشاهده بانک سوال | ✓ | ✓ |

## quizzes

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `quizzes.create` | ایجاد آزمون | ✓ | ✓ |
| `quizzes.delete` | حذف آزمون | ✓ | ✓ |
| `quizzes.reports` | گزارش آزمون | ✓ | ✓ |
| `quizzes.review` | تصحیح دستی آزمون | ✓ | ✓ |
| `quizzes.update` | ویرایش آزمون | ✓ | ✓ |
| `quizzes.view` | مشاهده آزمون‌ها | ✓ | ✓ |

## rating

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `rating.set` | ثبت امتیاز | — | — |

## report

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `report.send` | ارسال گزارش | — | — |

## sections

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `sections.create` | ایجاد فصل | ✓ | ✓ |
| `sections.delete` | حذف فصل | ✓ | ✓ |
| `sections.update` | ویرایش فصل | ✓ | ✓ |
| `sections.view` | مشاهده فصل‌ها | ✓ | ✓ |

## security

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `security.access.manage` | مدیریت دسترسی‌ها | — | — |
| `security.access.view` | مشاهده دسترسی‌ها | — | — |
| `security.routes.manage` | مدیریت مپ روت→پرمیشن | — | — |
| `security.routes.view` | مشاهده مپ روت→پرمیشن | — | — |

## seo

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `seo.manage` | مدیریت SEO | — | — |
| `seo.update` | ویرایش SEO | — | — |
| `seo.view` | مشاهده SEO | — | — |

## statuses

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `statuses.create` | ایجاد وضعیت | — | — |
| `statuses.delete` | حذف وضعیت | — | — |
| `statuses.update` | ویرایش وضعیت | — | — |
| `statuses.view` | مشاهده وضعیت‌ها | — | — |

## system

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `system.resources.view` | مشاهده منابع سیستم | — | — |

## tags

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `tags.create` | ایجاد تگ | — | — |
| `tags.delete` | حذف تگ | — | — |
| `tags.merge` | ادغام تگ‌ها | — | — |
| `tags.update` | ویرایش تگ | — | — |
| `tags.view` | مشاهده تگ‌ها | — | — |

## uploads

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `uploads.image.editor` | آپلود تصویر ادیتور | — | — |

## users

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `users.create` | ایجاد کاربر | — | — |
| `users.deactivate` | غیرفعال‌سازی کاربر | — | — |
| `users.delete` | حذف کاربر | — | — |
| `users.impersonate` | لاگین به‌جای کاربر | — | — |
| `users.manage_permissions` | مدیریت دسترسی‌های کاربر | — | — |
| `users.manage_roles` | مدیریت نقش‌های کاربر | — | — |
| `users.reactivate` | فعال‌سازی مجدد کاربر | — | — |
| `users.reset_password` | ریست پسورد کاربر | — | — |
| `users.sessions.terminate` | خاتمه نشست‌های کاربر | — | — |
| `users.sessions.view` | مشاهده نشست‌های کاربر | — | — |
| `users.update` | ویرایش کاربر | — | — |
| `users.view` | مشاهده کاربران | — | — |

## videos

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `videos.download.signed` | دانلود امضاشده | — | — |
| `videos.key.view` | دریافت کلید ویدیو | — | — |
| `videos.playlist.view` | مشاهده پلی‌لیست | — | — |
| `videos.process` | پردازش ویدیو | ✓ | ✓ |
| `videos.upload` | آپلود ویدیو | ✓ | ✓ |

## videoviews

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `videoviews.get` | دریافت وضعیت تماشا | — | — |
| `videoviews.set` | ثبت وضعیت تماشا | — | — |

## vip

| پرمیشن | برچسب | own | any |
|--------|-------|-----|-----|
| `vip.purchase` | خرید VIP | — | — |
| `vip.view` | مشاهده VIP | — | — |

