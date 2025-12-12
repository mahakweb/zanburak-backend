# مستندات پیاده‌سازی سیستم امتیازدهی

## خلاصه

سیستم امتیازدهی کامل برای پروژه زنبورک پیاده‌سازی شده است. این سیستم شامل:

1. **PointsService**: سرویس مرکزی برای مدیریت امتیازدهی
2. **Events & Listeners**: ایونت‌ها و لیستنرهای مربوط به امتیازدهی (امتیازات در لیستنرها ثابت هستند)
3. **User API**: کنترلرهای API برای مشاهده و تبدیل امتیاز به پول در پنل کاربر
4. **Config**: فایل config برای تنظیمات تبدیل امتیاز به پول

## فایل‌های ایجاد شده

### Services
- `app/Services/PointsService.php` - سرویس اصلی مدیریت امتیاز

### Config
- `config/points.php` - تنظیمات تبدیل امتیاز به پول

### Events
- `app/Events/Score/Episode/EpisodeFullyWatched.php`
- `app/Events/Score/Comment/CommentOnEpisode.php`
- `app/Events/Score/Rating/RatingSubmitted.php`
- `app/Events/Score/Profile/ProfileCompleted.php`
- `app/Events/Score/User/EmailVerified.php`
- `app/Events/Score/User/MobileVerified.php`
- `app/Events/Score/User/DailyLogin.php`
- `app/Events/Score/User/UserFollowed.php`
- `app/Events/Score/Report/ReportApprovedForPoints.php`

### Listeners
- `app/Listeners/Score/Episode/EpisodeFullyWatchedListener.php`
- `app/Listeners/Score/Course/CourseCompletedListener.php`
- `app/Listeners/Score/Course/CertificateIssuedListener.php`
- `app/Listeners/Score/Comment/CommentOnEpisodeListener.php`
- `app/Listeners/Score/Comment/ReplyToCommentListener.php`
- `app/Listeners/Score/Rating/RatingSubmittedListener.php`
- `app/Listeners/Score/Post/PostLikedListener.php`
- `app/Listeners/Score/Profile/ProfileCompletedListener.php`
- `app/Listeners/Score/User/EmailVerifiedListener.php`
- `app/Listeners/Score/User/MobileVerifiedListener.php`
- `app/Listeners/Score/User/DailyLoginListener.php`
- `app/Listeners/Score/User/UserFollowedListener.php`
- `app/Listeners/Score/User/FollowUserListener.php`
- `app/Listeners/Score/Payment/FirstPurchaseListener.php`
- `app/Listeners/Score/Payment/VipUpgradedListener.php`
- `app/Listeners/Score/Report/ReportApprovedListener.php`

### Controllers
- `app/Http/Controllers/Api/Panel/PointsController.php`

## به‌روزرسانی فایل‌های موجود

### Listeners (به‌روزرسانی شده)
- `app/Listeners/Score/Discuss/SubmitNewQuestionListener.php` - استفاده از PointsService
- `app/Listeners/Score/Discuss/SubmitNewAnswerListener.php` - استفاده از PointsService
- `app/Listeners/Score/Discuss/SelectBestAnswerListener.php` - استفاده از PointsService

### Controllers (به‌روزرسانی شده)
- `app/Http/Controllers/Api/VideoViewsController.php` - فایر کردن EpisodeFullyWatched
- `app/Http/Controllers/Home/RatingController.php` - فایر کردن RatingSubmitted
- `app/Http/Controllers/Api/Auth/EmailVerificationController.php` - فایر کردن EmailVerified
- `app/Http/Controllers/Api/Panel/ProfileController.php` - فایر کردن MobileVerified
- `app/Http/Controllers/Api/Auth/UnifiedAuthController.php` - فایر کردن MobileVerified
- `app/Http/Controllers/Api/CommentController.php` - فایر کردن CommentOnEpisode

### Providers
- `app/Providers/EventServiceProvider.php` - ثبت همه لیستنرهای جدید

### Routes
- `routes/api/panel.php` - اضافه شدن routeهای مشاهده و تبدیل امتیاز

## نحوه استفاده

### در لیستنرها

```php
use App\Services\PointsService;

class MyListener
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    public function handle($event)
    {
        $this->pointsService->awardPoints(
            $user,
            'توضیحات امتیاز',
            100 // مقدار امتیاز (ثابت در کد)
        );
    }
}
```

### API Endpoints

#### User (مشاهده و تبدیل)
- `GET /api/panel/points` - دریافت امتیاز کل و تاریخچه
- `POST /api/panel/points/convert-to-money` - تبدیل امتیاز به پول
- `GET /api/panel/points/conversion-info` - اطلاعات تبدیل (نرخ و حداقل)

## کارهای باقی‌مانده

### 1. فایر کردن ایونت‌های اضافی

برخی ایونت‌ها نیاز به فایر شدن در مکان‌های مناسب دارند:

- **ProfileCompleted**: باید در جایی که پروفایل کامل می‌شود فایر شود

### 2. تنظیمات Config

می‌توانید نرخ تبدیل و حداقل امتیاز را در فایل `.env` تنظیم کنید:

```env
POINTS_CONVERSION_RATE=1
POINTS_MIN_POINTS=1000
```

یا مستقیماً در `config/points.php` تغییر دهید.

### 3. تست

تست کردن تمام ایونت‌ها و لیستنرها برای اطمینان از کارکرد صحیح

## نکات مهم

1. **امتیازات ثابت**: تمام امتیازات در لیستنرها ثابت هستند و قابل تغییر از پنل ادمین نیستند
2. **Conversion Rate**: نرخ تبدیل امتیاز به پول در فایل config قابل تغییر است
3. **Min Points**: حداقل امتیاز برای تبدیل به پول در فایل config قابل تغییر است
4. **Daily Login**: محدودیت روزانه برای ورود روزانه با استفاده از Cache پیاده‌سازی شده است

## مثال استفاده در Frontend

### مشاهده امتیاز
```javascript
axios.get('/api/panel/points')
  .then(response => {
    console.log('Total Points:', response.data.data.total_points);
    console.log('History:', response.data.data.history);
  });
```

### تبدیل به پول
```javascript
axios.post('/api/panel/points/convert-to-money', {
  points: 1000
})
  .then(response => {
    console.log('Converted:', response.data.data);
  });
```

### دریافت اطلاعات تبدیل
```javascript
axios.get('/api/panel/points/conversion-info')
  .then(response => {
    console.log('Rate:', response.data.data.conversion_rate);
    console.log('Min Points:', response.data.data.min_points);
  });
```

