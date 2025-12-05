# راهنمای کامل Event های اطلاع‌رسانی

این فایل شامل لیست کامل تمام Event های اطلاع‌رسانی و محل استفاده آن‌ها است.

## ✅ گروه 1: فعالیت شما

### 1. تایید دیدگاه (`comment-approved`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Http/Controllers/Api/Admin/Comment/CommentController.php` - متد `toggleApproval()`  
**شرط**: وقتی کامنت از حالت عدم تایید به تایید تغییر می‌کند

### 2. بهترین پاسخ (`best-answer`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/Score/Discuss/SelectBestAnswerListener.php` - متد `handle()`  
**شرط**: وقتی پاسخ به عنوان بهترین پاسخ انتخاب می‌شود

### 3. آپدیت دوره (`course-updated`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: 
- `app/Http/Controllers/Api/Admin/Course/CourseController.php` - متد `update()`
- `app/Http/Controllers/Api/Admin/Course/EpisodeController.php` - متد `updateEpisode()`  
**شرط**: وقتی دوره یا اپیزود به‌روزرسانی می‌شود، به همه کاربرانی که در دوره ثبت‌نام کرده‌اند اطلاع داده می‌شود

### 4. ورود به سایت (`login`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/SendLoginNotification.php` - متد `handle()`  
**شرط**: بعد از لاگین موفق کاربر

### 5. خرید دوره (`course-purchase`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: 
- `app/Services/PaymentService.php` - متد `sendPurchaseNotifications()`
- `app/Http/Controllers/Api/Admin/PaymentController.php` - متد `sendPurchaseNotifications()`  
**شرط**: بعد از پرداخت موفق، با مدیریت خرید چندتایی (یک نوتیف برای همه خریدها)

### 6. ارتقاء عضویت ویژه (`vip-upgrade`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: 
- `app/Services/PaymentService.php` - متد `sendPurchaseNotifications()`
- `app/Http/Controllers/Api/Admin/PaymentController.php` - متد `sendPurchaseNotifications()`  
**شرط**: بعد از خرید پلن VIP، با مدیریت خرید چندتایی

### 7. اطلاع رسانی تخفیف (`discount-notification`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Http/Controllers/Api/DiscountController.php` - متد `applyDiscount()`  
**شرط**: وقتی کد تخفیف با موفقیت اعمال می‌شود

### 8. اطلاع رسانی دوره جدید (`new-course-notification`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Http/Controllers/Api/Admin/Course/CourseController.php` - متد `store()`  
**شرط**: وقتی دوره جدیدی publish می‌شود  
**منطق**: به کاربرانی اطلاع داده می‌شود که:
- دوره‌های مشابه را bookmark کرده‌اند
- در دسته‌بندی‌های مشابه دوره خریداری کرده‌اند

### 9. اطلاع رسانی متفرقه (`miscellaneous-notification`)
**وضعیت**: ✅ آماده برای استفاده  
**مکان**: هر جایی که نیاز به اطلاع‌رسانی عمومی دارید  
**استفاده**:
```php
sendNotification($user, 'miscellaneous-notification', [
    'message' => 'پیام متفرقه',
    'subject' => 'عنوان',
    'action_url' => frontendUrl('path'),
    'action_text' => 'مشاهده',
    'sms_message' => 'پیام متفرقه',
]);
```

---

## ✅ گروه 2: فعالیت دیگران

### 1. پاسخ به دیدگاه شما (`reply-to-comment`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: 
- `app/Http/Controllers/Api/CommentController.php` - متد `store()`
- `app/Http/Controllers/Api/Admin/Comment/CommentController.php` - متد `sendReply()`  
**شرط**: وقتی کسی به دیدگاه کاربر پاسخ می‌دهد

### 2. پاسخ به گفتگو شما (`reply-to-discussion`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/Score/Discuss/SubmitNewAnswerListener.php` - متد `handle()`  
**شرط**: وقتی کسی به سوال/گفتگوی کاربر پاسخ می‌دهد

### 3. لایک و دیس‌لایک مطلب شما (`like-dislike-post`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: 
- `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `likeDislike()`
- `app/Http/Controllers/Api/LikeController.php` - متد `toggleLike()`  
**شرط**: وقتی کسی مطلب کاربر را لایک یا دیس‌لایک می‌کند

### 4. پاسخ به گفتگویی که شما دنبال می‌کنید (`reply-to-followed-discussion`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/Score/Discuss/SubmitNewAnswerListener.php` - متد `handle()`  
**شرط**: وقتی به گفتگویی که کاربر bookmark کرده پاسخ داده می‌شود

### 5. پیام از طرف رزومه (`message-from-resume`)
**وضعیت**: ⚠️ نیاز به بررسی  
**مکان**: باید بررسی شود که آیا سیستم رزومه وجود دارد یا نه  
**نکته**: اگر سیستم رزومه وجود دارد، باید در Controller مربوطه اضافه شود

### 6. ثبت دیدگاه در مقالات شما (`comment-on-article`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Http/Controllers/Api/CommentController.php` - متد `store()`  
**شرط**: وقتی در محتوای کاربر (Course, Episode, Path) دیدگاه ثبت می‌شود و صاحب محتوا با کامنت‌کننده متفاوت است

### 7. دنبال‌کننده جدید (`new-follower`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/SendFollowNotification.php` - متد `handle()`  
**شرط**: وقتی کسی کاربر را دنبال می‌کند

### 8. اشاره کردن به شما (Mention) (`mention`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: 
- `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `store()` (سوال جدید)
- `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `updateQuestion()` (ویرایش سوال)  
**شرط**: وقتی در سوال private به کاربر اشاره می‌شود (mention_users)

---

## ✅ گروه 3: فعالیت افرادی که دنبال می‌کنید

### 1. ارسال گفتگو (`new-discussion`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/Score/Discuss/SubmitNewQuestionListener.php` - متد `handle()`  
**شرط**: وقتی فردی که کاربر دنبال می‌کند گفتگوی جدیدی ارسال می‌کند

### 2. پاسخ به گفتگو (`followed-user-reply-to-discussion`)
**وضعیت**: ✅ پیاده‌سازی شده  
**مکان**: `app/Listeners/Score/Discuss/SubmitNewAnswerListener.php` - متد `handle()`  
**شرط**: وقتی فردی که کاربر دنبال می‌کند به گفتگویی پاسخ می‌دهد

---

## 📋 خلاصه وضعیت

| Event | وضعیت | مکان |
|-------|-------|------|
| تایید دیدگاه | ✅ | CommentController::toggleApproval |
| بهترین پاسخ | ✅ | SelectBestAnswerListener |
| آپدیت دوره | ✅ | CourseController::update, EpisodeController::updateEpisode |
| ورود به سایت | ✅ | SendLoginNotification |
| خرید دوره | ✅ | PaymentService, PaymentController |
| ارتقاء عضویت ویژه | ✅ | PaymentService, PaymentController |
| اطلاع رسانی تخفیف | ✅ | DiscountController::applyDiscount |
| اطلاع رسانی دوره جدید | ✅ | CourseController::store |
| اطلاع رسانی متفرقه | ✅ | آماده برای استفاده |
| پاسخ به دیدگاه شما | ✅ | CommentController::store, CommentController::sendReply |
| پاسخ به گفتگو شما | ✅ | SubmitNewAnswerListener |
| لایک و دیس‌لایک | ✅ | DiscussController::likeDislike, LikeController::toggleLike |
| پاسخ به گفتگوی دنبال شده | ✅ | SubmitNewAnswerListener |
| پیام از طرف رزومه | ⚠️ | نیاز به بررسی |
| ثبت دیدگاه در مقالات | ✅ | CommentController::store |
| دنبال‌کننده جدید | ✅ | SendFollowNotification |
| Mention | ✅ | DiscussController::store, DiscussController::updateQuestion |
| ارسال گفتگو | ✅ | SubmitNewQuestionListener |
| پاسخ به گفتگو (دنبال شده) | ✅ | SubmitNewAnswerListener |

---

## 🔧 Event های نیازمند بررسی

### 1. پیام از طرف رزومه (`message-from-resume`)
**وضعیت**: ❌ سیستم رزومه پیدا نشد  
**نکته**: در حال حاضر سیستم رزومه در کدبیس وجود ندارد. اگر در آینده اضافه شود، باید در Controller مربوطه اضافه شود.  
**مکان احتمالی**: اگر سیستم رزومه اضافه شود، باید در Controller مربوط به ارسال پیام از طریق رزومه اضافه شود.

---

## 📝 نکات مهم

1. **همه لینک‌ها از `frontendUrl()` استفاده می‌کنند** که از `FRONT_APP_URL` در `.env` می‌خواند
2. **خرید چندتایی** به صورت یک نوتیف مدیریت می‌شود
3. **Event های فایل‌باز** برای منطق پیچیده و Listener ها استفاده می‌شوند
4. **Event های دیتابیس** برای مدیریت کانال‌های اطلاع‌رسانی استفاده می‌شوند

