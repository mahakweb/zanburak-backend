# الگوی پیامک اطلاع‌رسانی

برای استفاده از سیستم اطلاع‌رسانی SMS، باید یک الگوی پیامک در پنل MeliPayamak ایجاد کنید.

## مراحل ایجاد الگو در پنل MeliPayamak:

1. وارد پنل MeliPayamak شوید
2. به بخش "الگوهای پیامک" بروید
3. یک الگوی جدید ایجاد کنید با محتوای زیر:

### محتوای الگو:

```
{message}
```

یا اگر می‌خواهید نام سایت را هم اضافه کنید:

```
زنبورک: {message}
```

### نکات مهم:

- متغیر `{message}` باید در الگو قرار بگیرد
- بعد از ایجاد الگو، `bodyId` آن را کپی کنید
- `bodyId` را در فایل `.env` به صورت زیر اضافه کنید:

```env
MELI_PAYAMAK_NOTIFICATION_TEMPLATE_ID=YOUR_BODY_ID_HERE
```

## مثال استفاده:

### روش 1: استفاده از Helper Function (پیشنهادی)

```php
// ارسال اطلاع‌رسانی برای یک کاربر
sendNotification(
    $user,
    'comment-approved', // slug رویداد
    [
        'message' => 'دیدگاه شما تایید شد',
        'subject' => 'تایید دیدگاه',
        'action_url' => url('/panel/comments'),
        'action_text' => 'مشاهده دیدگاه',
        'sms_message' => 'دیدگاه شما در سایت زنبورک تایید شد. برای مشاهده به پنل کاربری مراجعه کنید.',
    ]
);

// ارسال برای چند کاربر
sendBulkNotification(
    [1, 2, 3], // آرایه ID کاربران
    'new-course-notification',
    [
        'message' => 'دوره جدید منتشر شد',
        'sms_message' => 'دوره جدید در سایت زنبورک منتشر شد.',
    ]
);
```

### روش 2: استفاده مستقیم از Service

```php
use App\Services\NotificationService;

$notificationService = new NotificationService();

// ارسال اطلاع‌رسانی برای یک کاربر
$notificationService->sendNotification(
    $user,
    'comment-approved',
    [
        'message' => 'دیدگاه شما تایید شد',
        'subject' => 'تایید دیدگاه',
        'action_url' => url('/panel/comments'),
        'action_text' => 'مشاهده دیدگاه',
        'sms_message' => 'دیدگاه شما در سایت زنبورک تایید شد.',
    ]
);
```

## رویدادهای موجود:

### گروه: فعالیت شما
- `comment-approved` - تایید دیدگاه
- `best-answer` - بهترین پاسخ
- `course-updated` - آپدیت دوره
- `login` - ورود به سایت
- `course-purchase` - خرید دوره
- `vip-upgrade` - ارتقاء عضویت ویژه
- `discount-notification` - اطلاع رسانی تخفیف
- `new-course-notification` - اطلاع رسانی دوره جدید
- `miscellaneous-notification` - اطلاع رسانی متفرقه

### گروه: فعالیت دیگران
- `reply-to-comment` - پاسخ به دیدگاه شما
- `reply-to-discussion` - پاسخ به گفتگو شما
- `like-dislike-post` - لایک و دیس‌لایک مطلب شما
- `reply-to-followed-discussion` - پاسخ به گفتگویی که شما دنبال می‌کنید
- `message-from-resume` - پیام از طرف رزومه
- `comment-on-article` - ثبت دیدگاه در مقالات شما
- `new-follower` - دنبال‌کننده جدید
- `mention` - اشاره کردن به شما (Mention)

### گروه: فعالیت افرادی که دنبال می‌کنید
- `new-discussion` - ارسال گفتگو
- `followed-user-reply-to-discussion` - پاسخ به گفتگو

