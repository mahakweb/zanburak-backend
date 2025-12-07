# 📘 راهنمای استفاده از Notification های فیزیکی با NotificationService

این راهنما نحوه استفاده از Notification های فیزیکی را نشان می‌دهد که از `NotificationService` برای دریافت کانال‌های فعال استفاده می‌کنند.

---

## 🎯 مفهوم

**NotificationService** فقط کانال‌های فعال را برمی‌گرداند (بر اساس تنظیمات کاربر)  
**Notification فیزیکی** خودش ارسال را انجام می‌دهد با محتوای خاص خودش

---

## 📋 ساختار

### 1. BaseNotification Class

کلاس پایه که از `NotificationService` برای دریافت کانال‌ها استفاده می‌کند:

```php
abstract class BaseNotification extends Notification
{
    // از NotificationService کانال‌ها را می‌گیرد
    public function via($notifiable)
    {
        $channels = $this->notificationService->getChannels($notifiable, $this->getEventSlug());
        return $channels ?: ['database'];
    }
    
    // باید در کلاس فرزند override شود
    abstract protected function getEventSlug(): ?string;
}
```

### 2. Notification فیزیکی

هر Notification از `BaseNotification` ارث‌بری می‌کند:

```php
class SelectBestAnswerNotification extends BaseNotification
{
    protected function getEventSlug(): ?string
    {
        return 'best-answer'; // slug رویداد در دیتابیس
    }
    
    public function toMail($notifiable) { /* محتوای خاص */ }
    public function toSms($notifiable) { /* محتوای خاص */ }
    public function toArray($notifiable) { /* محتوای خاص */ }
}
```

---

## 🔧 نحوه استفاده

### مثال 1: SelectBestAnswerNotification

```php
// در Listener
$user->notify(new SelectBestAnswerNotification($answer));

// Notification خودش:
// 1. از NotificationService کانال‌ها را می‌گیرد
// 2. بر اساس تنظیمات کاربر، کانال‌های فعال را انتخاب می‌کند
// 3. خودش ارسال را انجام می‌دهد
```

### مثال 2: ساخت Notification جدید

```php
<?php

namespace App\Notifications\Discuss;

use App\Notifications\BaseNotification;
use App\Models\Question;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Channels\SmsChannel;

class SubmitAnswerNotification extends BaseNotification
{
    public $question;

    public function __construct(Question $question)
    {
        parent::__construct();
        $this->question = $question;
    }

    /**
     * تعیین slug رویداد
     */
    protected function getEventSlug(): ?string
    {
        return 'submit-answer';
    }

    /**
     * محتوای ایمیل
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('پاسخ جدید به سوال شما')
            ->greeting('سلام ' . $notifiable->first_name . ' عزیز!')
            ->line("یک پاسخ جدید برای سوال «{$this->question->subject}» ثبت شده است.")
            ->action('مشاهده پاسخ', url("/discuss/{$this->question->slug}"));
    }

    /**
     * محتوای پیامک
     */
    public function toSms($notifiable)
    {
        return [
            'message' => "یک پاسخ جدید برای سوال «{$this->question->subject}» ثبت شده است. زنبورک",
            'phone' => $notifiable->mobile,
        ];
    }

    /**
     * محتوای دیتابیس (برای نمایش در سایت)
     */
    public function toArray($notifiable)
    {
        return [
            'message' => "یک پاسخ جدید برای سوال «{$this->question->subject}» ثبت شده است.",
            'icon' => '<svg>...</svg>',
            'action_url' => url("/discuss/{$this->question->slug}"),
        ];
    }
}
```

---

## ✅ مزایا

1. **انعطاف‌پذیری**: هر Notification محتوای خاص خودش را دارد
2. **یکپارچگی**: کانال‌ها به صورت خودکار از تنظیمات کاربر چک می‌شوند
3. **قابلیت توسعه**: می‌توانید Listener های مختلف اضافه کنید
4. **سازگاری**: با الگوی Laravel سازگار است

---

## 🔄 تفاوت با سیستم قبلی

### قبل (سیستم قدیمی):
```php
public function via($notifiable)
{
    return ['database']; // فقط دیتابیس
}
```

### حالا (با BaseNotification):
```php
// via() خودکار از NotificationService کانال‌ها را می‌گیرد
// بر اساس تنظیمات کاربر: database, mail, sms
```

---

## 📝 نکات مهم

1. **Event Slug**: باید در دیتابیس (`events` table) وجود داشته باشد
2. **متد getEventSlug()**: باید در هر Notification override شود
3. **متدهای toMail, toSms, toArray**: برای محتوای خاص override کنید
4. **Queue**: می‌توانید `ShouldQueue` را implement کنید

---

## 🚀 مراحل تبدیل Notification قدیمی به جدید

1. از `BaseNotification` ارث‌بری کنید
2. متد `getEventSlug()` را اضافه کنید
3. متدهای `toMail()`, `toSms()`, `toArray()` را کامل کنید
4. `sendNotification()` را از Listener حذف کنید
5. فقط `$user->notify(new YourNotification())` را نگه دارید

---

## 📚 مثال‌های بیشتر

برای مثال‌های بیشتر، فایل `SelectBestAnswerNotification.php` را ببینید.

