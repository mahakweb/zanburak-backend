# توضیح معماری Event ها

## دو نوع Event در سیستم:

### 1. **Event های فایل‌باز (Laravel Events)**
این Event ها در فولدر `app/Events` هستند و برای **فایر کردن** در کد استفاده می‌شوند.

**مثال:**
```php
event(new FollowUser($user));
event(new SelectBestAnswer($answer));
event(new SubmitNewAnswer($user, $answer));
```

**ویژگی‌ها:**
- کلاس PHP هستند
- در `EventServiceProvider` ثبت می‌شوند
- Listener های خاص دارند
- برای منطق پیچیده و قابل استفاده مجدد

**Event های موجود:**
- `FollowUser`
- `SelectBestAnswer`
- `SubmitNewAnswer`
- `SubmitNewQuestion`
- `GetCourse`
- `PurchaseEvent`
- `ChangeMobile`
- `ChangePassword`
- Event های Chat و Mission

### 2. **Event های دیتابیس (Notification Events)**
این Event ها در جدول `events` ذخیره می‌شوند و برای **مدیریت کانال‌های اطلاع‌رسانی** استفاده می‌شوند.

**مثال:**
```php
sendNotification($user, 'comment-approved', [...]);
sendNotification($user, 'best-answer', [...]);
sendNotification($user, 'new-follower', [...]);
```

**ویژگی‌ها:**
- در دیتابیس ذخیره می‌شوند
- توسط ادمین قابل مدیریت هستند
- برای تنظیمات کاربر استفاده می‌شوند
- کانال‌های اطلاع‌رسانی (Email, SMS, Site, Telegram) را کنترل می‌کنند

**Event های موجود:**
- `comment-approved`
- `best-answer`
- `course-updated`
- `login`
- `course-purchase`
- `vip-upgrade`
- `reply-to-comment`
- `reply-to-discussion`
- `new-follower`
- و...

## رابطه بین این دو:

### سناریو 1: Event فایل‌باز → Event دیتابیس
```php
// 1. Event فایل‌باز فایر می‌شود
event(new SelectBestAnswer($answer));

// 2. Listener اجرا می‌شود
class SelectBestAnswerListener {
    public function handle($event) {
        // 3. Event دیتابیس برای اطلاع‌رسانی استفاده می‌شود
        sendNotification($user, 'best-answer', [...]);
    }
}
```

### سناریو 2: فقط Event دیتابیس
```php
// مستقیماً Event دیتابیس استفاده می‌شود
sendNotification($user, 'comment-approved', [...]);
```

## مقایسه:

| ویژگی | Event فایل‌باز | Event دیتابیس |
|-------|---------------|---------------|
| محل ذخیره‌سازی | فایل PHP | جدول دیتابیس |
| مدیریت | توسط توسعه‌دهنده | توسط ادمین |
| استفاده | فایر کردن در کد | ارسال اطلاع‌رسانی |
| Listener | دارد | ندارد |
| تنظیمات کاربر | ندارد | دارد |
| کانال‌های اطلاع‌رسانی | ندارد | دارد |

## توصیه:

### ✅ **روش فعلی درست است!**

**دلایل:**
1. **Event های فایل‌باز** برای منطق پیچیده و Listener ها
2. **Event های دیتابیس** برای مدیریت پویا و تنظیمات کاربر
3. این دو مکمل هم هستند، نه جایگزین

### 🔄 **نقشه راه:**

1. **Event های فایل‌باز** را نگه دارید برای:
   - منطق پیچیده (مثل Score دادن)
   - Listener های خاص
   - Event هایی که نیاز به Broadcasting دارند

2. **Event های دیتابیس** را استفاده کنید برای:
   - اطلاع‌رسانی‌های کاربر
   - تنظیمات کانال‌های اطلاع‌رسانی
   - مدیریت توسط ادمین

3. **ترکیب استفاده:**
   ```php
   // در Listener
   public function handle($event) {
       // منطق پیچیده
       $user->scores()->create([...]);
       
       // اطلاع‌رسانی از طریق Event دیتابیس
       sendNotification($user, 'best-answer', [...]);
   }
   ```

## Event های موجود و نقش آن‌ها:

### Event های فایل‌باز که Event دیتابیس هم دارند:
- `SelectBestAnswer` → `best-answer` ✅
- `SubmitNewAnswer` → `reply-to-discussion` ✅
- `SubmitNewQuestion` → `new-discussion` ✅
- `FollowUser` → `new-follower` ✅
- `Login` (Laravel) → `login` ✅

### Event های فایل‌باز که Event دیتابیس ندارند:
- `GetCourse` - فقط برای منطق داخلی
- `PurchaseEvent` - برای Mission System
- `ChangeMobile`, `ChangePassword` - برای تغییر اطلاعات

### Event های دیتابیس که Event فایل‌باز ندارند:
- `comment-approved` - مستقیماً در Controller فایر می‌شود
- `course-updated` - مستقیماً در Controller فایر می‌شود
- `course-purchase` - مستقیماً در PaymentService فایر می‌شود
- `reply-to-comment` - مستقیماً در Controller فایر می‌شود

## نتیجه‌گیری:

**سیستم فعلی درست است!** نیازی به تغییر نیست. این دو نوع Event برای اهداف مختلف هستند و مکمل هم عمل می‌کنند.

