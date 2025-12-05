# راهنمای یکپارچه‌سازی سیستم اطلاع‌رسانی

این فایل راهنمای کامل برای یکپارچه‌سازی سیستم اطلاع‌رسانی در نقاط مختلف سیستم است.

## ✅ موارد پیاده‌سازی شده:

### 1. تایید دیدگاه
**فایل**: `app/Http/Controllers/Api/Admin/Comment/CommentController.php`
**متد**: `toggleApproval()`
**Event**: `comment-approved`
```php
// وقتی کامنت تایید می‌شود، به صاحب کامنت اطلاع داده می‌شود
```

### 2. بهترین پاسخ
**فایل**: `app/Listeners/Score/Discuss/SelectBestAnswerListener.php`
**متد**: `handle()`
**Event**: `best-answer`
```php
// وقتی پاسخ به عنوان بهترین پاسخ انتخاب می‌شود
```

### 3. خرید دوره
**فایل**: `app/Services/PaymentService.php` و `app/Http/Controllers/Api/Admin/PaymentController.php`
**Event**: `course-purchase`
```php
// وقتی کاربر دوره را خریداری می‌کند
```

### 4. ارتقاء عضویت ویژه
**فایل**: `app/Services/PaymentService.php` و `app/Http/Controllers/Api/Admin/PaymentController.php`
**Event**: `vip-upgrade`
```php
// وقتی کاربر پلن VIP خریداری می‌کند
```

### 5. دنبال‌کننده جدید
**فایل**: `app/Listeners/SendFollowNotification.php`
**Event**: `new-follower`
```php
// وقتی کسی کاربر را دنبال می‌کند
```

### 6. پاسخ به دیدگاه
**فایل**: 
- `app/Http/Controllers/Api/CommentController.php` (متد `store`)
- `app/Http/Controllers/Api/Admin/Comment/CommentController.php` (متد `sendReply`)
**Event**: `reply-to-comment`
```php
// وقتی کسی به دیدگاه کاربر پاسخ می‌دهد
```

## 📋 موارد باقی‌مانده برای پیاده‌سازی:

### 1. آپدیت دوره
**مکان**: `app/Http/Controllers/Api/Admin/Course/CourseController.php` - متد `update()`
**Event**: `course-updated`
```php
// بعد از آپدیت دوره، به همه کاربرانی که در دوره ثبت‌نام کرده‌اند اطلاع بده
$course = Course::find($courseId);
$course->update($data);

// ارسال اطلاع‌رسانی به کاربران
$enrolledUsers = $course->users;
foreach ($enrolledUsers as $user) {
    sendNotification($user, 'course-updated', [
        'message' => "دوره «{$course->title}» به‌روزرسانی شد و محتوای جدیدی اضافه شده است.",
        'subject' => 'به‌روزرسانی دوره',
        'action_url' => url("/course/{$course->slug}"),
        'action_text' => 'مشاهده دوره',
        'sms_message' => "دوره {$course->title} به‌روزرسانی شد.",
    ]);
}
```

### 2. آپدیت اپیزود
**مکان**: `app/Http/Controllers/Api/Admin/Course/EpisodeController.php` - متد `updateEpisode()`
**Event**: `course-updated`
```php
// بعد از آپدیت اپیزود، به کاربران دوره اطلاع بده
$episode = Episode::find($episodeId);
$episode->update($data);

$course = $episode->section->course;
$enrolledUsers = $course->users;

foreach ($enrolledUsers as $user) {
    sendNotification($user, 'course-updated', [
        'message' => "اپیزود جدیدی به دوره «{$course->title}» اضافه شد.",
        'subject' => 'به‌روزرسانی دوره',
        'action_url' => url("/course/{$course->slug}"),
        'action_text' => 'مشاهده دوره',
        'sms_message' => "اپیزود جدیدی به دوره {$course->title} اضافه شد.",
    ]);
}
```

### 3. ورود به سایت
**مکان**: `app/Http/Controllers/Api/Auth/UnifiedAuthController.php` یا هر جایی که لاگین انجام می‌شود
**Event**: `login`
```php
// بعد از لاگین موفق
sendNotification($user, 'login', [
    'message' => "ورود شما به حساب کاربری با موفقیت انجام شد.",
    'subject' => 'ورود به سایت',
    'action_url' => url("/panel"),
    'action_text' => 'ورود به پنل',
    'sms_message' => "ورود شما به حساب کاربری انجام شد.",
]);
```

### 4. پاسخ به گفتگو
**مکان**: `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `storeAnswer()`
**Event**: `reply-to-discussion`
```php
// وقتی کسی به سوال/گفتگوی کاربر پاسخ می‌دهد
$answer = Answer::create([...]);
$question = $answer->question;

if ($question->user_id != $answer->user_id) {
    sendNotification($question->user, 'reply-to-discussion', [
        'message' => "{$answer->user->first_name} به گفتگوی شما پاسخ داد.",
        'subject' => 'پاسخ به گفتگو',
        'action_url' => url("/discuss/{$question->slug}"),
        'action_text' => 'مشاهده پاسخ',
        'sms_message' => "شما یک پاسخ جدید به گفتگوی خود دریافت کرده‌اید.",
    ]);
}
```

### 5. لایک و دیس‌لایک مطلب
**مکان**: هر جایی که Like/Dislike انجام می‌شود
**Event**: `like-dislike-post`
```php
// وقتی کسی مطلب کاربر را لایک/دیس‌لایک می‌کند
// باید ببینید کجا Like انجام می‌شود
```

### 6. پاسخ به گفتگویی که دنبال می‌کنید
**مکان**: `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `storeAnswer()`
**Event**: `reply-to-followed-discussion`
```php
// وقتی به گفتگویی که کاربر دنبال می‌کند پاسخ داده می‌شود
$answer = Answer::create([...]);
$question = $answer->question;

// پیدا کردن کاربرانی که این سوال را دنبال می‌کنند
$followers = $question->followers;

foreach ($followers as $follower) {
    if ($follower->id != $answer->user_id) {
        sendNotification($follower, 'reply-to-followed-discussion', [
            'message' => "به گفتگویی که دنبال می‌کنید پاسخ جدیدی داده شد.",
            'subject' => 'پاسخ به گفتگوی دنبال شده',
            'action_url' => url("/discuss/{$question->slug}"),
            'action_text' => 'مشاهده پاسخ',
            'sms_message' => "به گفتگویی که دنبال می‌کنید پاسخ جدیدی داده شد.",
        ]);
    }
}
```

### 7. پیام از طرف رزومه
**مکان**: هر جایی که پیام از طریق رزومه ارسال می‌شود
**Event**: `message-from-resume`
```php
// باید ببینید کجا پیام از رزومه ارسال می‌شود
```

### 8. ثبت دیدگاه در مقالات
**مکان**: `app/Http/Controllers/Api/CommentController.php` - متد `store()`
**Event**: `comment-on-article`
```php
// وقتی در مقاله کاربر دیدگاه ثبت می‌شود
$comment = Comment::create([...]);

// اگر commentable یک Article است و صاحب آن با کامنت‌کننده متفاوت است
if ($comment->commentable instanceof Article && $comment->commentable->user_id != $comment->user_id) {
    sendNotification($comment->commentable->user, 'comment-on-article', [
        'message' => "{$comment->user->first_name} در مقاله شما دیدگاه ثبت کرد.",
        'subject' => 'دیدگاه جدید',
        'action_url' => url("/article/{$comment->commentable->slug}"),
        'action_text' => 'مشاهده دیدگاه',
        'sms_message' => "دیدگاه جدیدی در مقاله شما ثبت شد.",
    ]);
}
```

### 9. Mention (اشاره کردن)
**مکان**: هر جایی که Mention انجام می‌شود (احتمالاً در کامنت‌ها یا پست‌ها)
**Event**: `mention`
```php
// وقتی کسی در کامنت یا پست به کاربر اشاره می‌کند
// باید الگوی Mention را پیدا کنید (مثلاً @username)
```

### 10. اطلاع رسانی تخفیف
**مکان**: هر جایی که تخفیف اعمال می‌شود
**Event**: `discount-notification`
```php
// وقتی تخفیف جدیدی برای کاربر اعمال می‌شود
sendNotification($user, 'discount-notification', [
    'message' => "تخفیف ویژه برای شما اعمال شد.",
    'subject' => 'تخفیف ویژه',
    'action_url' => url("/cart"),
    'action_text' => 'مشاهده سبد خرید',
    'sms_message' => "تخفیف ویژه برای شما اعمال شد.",
]);
```

### 11. اطلاع رسانی دوره جدید
**مکان**: `app/Http/Controllers/Api/Admin/Course/CourseController.php` - متد `store()` یا `update()` (وقتی publish می‌شود)
**Event**: `new-course-notification`
```php
// وقتی دوره جدیدی منتشر می‌شود، به کاربران علاقه‌مند اطلاع بده
// یا به همه کاربران
$course = Course::create([...]);

// می‌توانید به کاربران خاص یا همه اطلاع بدهید
sendBulkNotification($userIds, 'new-course-notification', [
    'message' => "دوره جدید «{$course->title}» منتشر شد.",
    'subject' => 'دوره جدید',
    'action_url' => url("/course/{$course->slug}"),
    'action_text' => 'مشاهده دوره',
    'sms_message' => "دوره جدید {$course->title} منتشر شد.",
]);
```

### 12. اطلاع رسانی متفرقه
**مکان**: هر جایی که نیاز به اطلاع‌رسانی عمومی دارید
**Event**: `miscellaneous-notification`
```php
// برای اطلاع‌رسانی‌های متفرقه
sendNotification($user, 'miscellaneous-notification', [
    'message' => "پیام متفرقه",
    'subject' => 'عنوان',
    'action_url' => url("/"),
    'action_text' => 'مشاهده',
    'sms_message' => "پیام متفرقه",
]);
```

### 13. ارسال گفتگو (افرادی که دنبال می‌کنید)
**مکان**: `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `store()`
**Event**: `new-discussion`
```php
// وقتی فردی که دنبال می‌کنید گفتگوی جدیدی ارسال می‌کند
$question = Question::create([...]);

// پیدا کردن کاربرانی که این کاربر را دنبال می‌کنند
$followers = $question->user->followers;

foreach ($followers as $follower) {
    sendNotification($follower, 'new-discussion', [
        'message' => "{$question->user->first_name} گفتگوی جدیدی ارسال کرد.",
        'subject' => 'گفتگوی جدید',
        'action_url' => url("/discuss/{$question->slug}"),
        'action_text' => 'مشاهده گفتگو',
        'sms_message' => "گفتگوی جدیدی از فردی که دنبال می‌کنید ارسال شد.",
    ]);
}
```

### 14. پاسخ به گفتگو (افرادی که دنبال می‌کنید)
**مکان**: `app/Http/Controllers/Api/Discuss/DiscussController.php` - متد `storeAnswer()`
**Event**: `followed-user-reply-to-discussion`
```php
// وقتی فردی که دنبال می‌کنید به گفتگویی پاسخ می‌دهد
$answer = Answer::create([...]);
$question = $answer->question;

// پیدا کردن کاربرانی که این کاربر را دنبال می‌کنند
$followers = $answer->user->followers;

foreach ($followers as $follower) {
    sendNotification($follower, 'followed-user-reply-to-discussion', [
        'message' => "{$answer->user->first_name} به گفتگویی پاسخ داد.",
        'subject' => 'پاسخ به گفتگو',
        'action_url' => url("/discuss/{$question->slug}"),
        'action_text' => 'مشاهده پاسخ',
        'sms_message' => "فردی که دنبال می‌کنید به گفتگویی پاسخ داد.",
    ]);
}
```

## 📝 نکات مهم:

1. **همیشه چک کنید**: قبل از ارسال اطلاع‌رسانی، مطمئن شوید که کاربر با خودش نیست (مثلاً به خودش پاسخ ندهد)

2. **استفاده از Helper**: از تابع `sendNotification()` استفاده کنید که در `app/Helpers/NotificationHelper.php` تعریف شده

3. **Bulk Notification**: برای ارسال به چند کاربر از `sendBulkNotification()` استفاده کنید

4. **Queue**: Notification ها به صورت Queue اجرا می‌شوند، پس نگران سرعت نباشید

5. **Error Handling**: NotificationService خودش Error Handling دارد، اما بهتر است در try-catch قرار دهید

## 🔧 نحوه استفاده:

```php
// ساده
sendNotification($user, 'event-slug', [
    'message' => 'پیام',
    'subject' => 'عنوان',
    'action_url' => url('/path'),
    'action_text' => 'متن دکمه',
    'sms_message' => 'پیام SMS',
]);

// برای چند کاربر
sendBulkNotification([1, 2, 3], 'event-slug', [
    'message' => 'پیام',
]);
```

