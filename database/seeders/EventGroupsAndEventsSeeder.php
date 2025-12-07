<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventGroup;
use App\Models\Event;

class EventGroupsAndEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // گروه اول: فعالیت شما
        $yourActivityGroup = EventGroup::create([
            'title' => 'فعالیت شما',
            'english_title' => 'Your Activity',
            'description' => 'اطلاع‌رسانی‌های مربوط به فعالیت‌های خود شما',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"/></svg>',
        ]);

        $yourActivityEvents = [
            [
                'title' => 'تایید دیدگاه',
                'english_title' => 'Comment Approved',
                'description' => 'زمانی که دیدگاه شما تایید می‌شود',
                'slug' => 'comment-approved',
            ],
            [
                'title' => 'بهترین پاسخ',
                'english_title' => 'Best Answer',
                'description' => 'زمانی که پاسخ شما به عنوان بهترین پاسخ انتخاب می‌شود',
                'slug' => 'best-answer',
            ],
            [
                'title' => 'آپدیت دوره',
                'english_title' => 'Course Updated',
                'description' => 'زمانی که دوره‌ای که در آن ثبت‌نام کرده‌اید به‌روزرسانی می‌شود',
                'slug' => 'course-updated',
            ],
            [
                'title' => 'ورود به سایت',
                'english_title' => 'Login',
                'description' => 'زمانی که به حساب کاربری خود وارد می‌شوید',
                'slug' => 'login',
            ],
            [
                'title' => 'خرید دوره',
                'english_title' => 'Course Purchase',
                'description' => 'زمانی که یک دوره را خریداری می‌کنید',
                'slug' => 'course-purchase',
            ],
            [
                'title' => 'ارتقاء عضویت ویژه',
                'english_title' => 'VIP Upgrade',
                'description' => 'زمانی که عضویت ویژه شما ارتقا می‌یابد',
                'slug' => 'vip-upgrade',
            ],
            [
                'title' => 'اطلاع رسانی تخفیف',
                'english_title' => 'Discount Notification',
                'description' => 'زمانی که تخفیف جدیدی برای شما اعمال می‌شود',
                'slug' => 'discount-notification',
            ],
            [
                'title' => 'اطلاع رسانی دوره جدید',
                'english_title' => 'New Course Notification',
                'description' => 'زمانی که دوره جدیدی منتشر می‌شود',
                'slug' => 'new-course-notification',
            ],
            [
                'title' => 'اطلاع رسانی متفرقه',
                'english_title' => 'Miscellaneous Notification',
                'description' => 'سایر اطلاع‌رسانی‌های متفرقه',
                'slug' => 'miscellaneous-notification',
            ],
            [
                'title' => 'تغییر رمز عبور',
                'english_title' => 'Change Password',
                'description' => 'زمانی که رمز عبور شما تغییر می‌کند',
                'slug' => 'change-password',
            ],
            [
                'title' => 'تغییر شماره موبایل',
                'english_title' => 'Change Mobile',
                'description' => 'زمانی که شماره موبایل شما تغییر می‌کند',
                'slug' => 'change-mobile',
            ],
            [
                'title' => 'تکمیل دوره',
                'english_title' => 'Course Completed',
                'description' => 'زمانی که دوره را 100% تماشا می‌کنید',
                'slug' => 'course-completed',
            ],
            [
                'title' => 'دریافت گواهینامه',
                'english_title' => 'Certificate Issued',
                'description' => 'زمانی که گواهینامه شما صادر می‌شود',
                'slug' => 'certificate-issued',
            ],
            [
                'title' => 'پیشرفت در دوره',
                'english_title' => 'Course Progress',
                'description' => 'زمانی که به درصد خاصی از دوره می‌رسید',
                'slug' => 'course-progress',
            ],
            [
                'title' => 'دستیابی به Mission',
                'english_title' => 'Mission Achieved',
                'description' => 'زمانی که یک Mission را کامل می‌کنید',
                'slug' => 'mission-achieved',
            ],
            [
                'title' => 'دستیابی به امتیاز خاص',
                'english_title' => 'Score Milestone',
                'description' => 'زمانی که به امتیاز خاصی می‌رسید',
                'slug' => 'score-milestone',
            ],
            [
                'title' => 'یادآوری دوره ناتمام',
                'english_title' => 'Course Reminder',
                'description' => 'یادآوری برای ادامه تماشای دوره',
                'slug' => 'course-reminder',
            ],
            [
                'title' => 'نزدیک شدن به اتمام دوره',
                'english_title' => 'Course Near Completion',
                'description' => 'زمانی که به 90% دوره می‌رسید',
                'slug' => 'course-near-completion',
            ],
            [
                'title' => 'دوره جدید در دسته‌بندی مورد علاقه',
                'english_title' => 'New Course in Category',
                'description' => 'زمانی که دوره جدیدی در دسته‌بندی مورد علاقه شما منتشر می‌شود',
                'slug' => 'new-course-in-category',
            ],
            [
                'title' => 'ورود از دستگاه جدید',
                'english_title' => 'Login from New Device',
                'description' => 'زمانی که از IP یا دستگاه جدیدی وارد می‌شوید',
                'slug' => 'login-from-new-device',
            ],
            [
                'title' => 'تغییر ایمیل',
                'english_title' => 'Email Changed',
                'description' => 'زمانی که ایمیل شما تغییر می‌کند',
                'slug' => 'email-changed',
            ],
            [
                'title' => 'پایان عضویت VIP (یادآوری)',
                'english_title' => 'VIP Expiring',
                'description' => 'یادآوری 7 روز قبل از اتمام عضویت VIP',
                'slug' => 'vip-expiring',
            ],
            [
                'title' => 'پایان عضویت VIP',
                'english_title' => 'VIP Expired',
                'description' => 'زمانی که عضویت VIP شما به پایان می‌رسد',
                'slug' => 'vip-expired',
            ],
            [
                'title' => 'پرداخت ناموفق',
                'english_title' => 'Payment Failed',
                'description' => 'زمانی که پرداخت شما ناموفق است',
                'slug' => 'payment-failed',
            ],
        ];

        foreach ($yourActivityEvents as $event) {
            Event::create([
                'title' => $event['title'],
                'english_title' => $event['english_title'],
                'slug' => $event['slug'],
                'description' => $event['description'],
                'event_group_id' => $yourActivityGroup->id,
                'is_email_enabled' => true,
                'is_sms_enabled' => true,
                'is_telegram_enabled' => false, // فعلا غیرفعال
                'is_site_enabled' => true,
            ]);
        }

        // گروه دوم: فعالیت دیگران
        $othersActivityGroup = EventGroup::create([
            'title' => 'فعالیت دیگران',
            'english_title' => 'Others Activity',
            'description' => 'اطلاع‌رسانی‌های مربوط به فعالیت‌های دیگران',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ]);

        $othersActivityEvents = [
            [
                'title' => 'پاسخ به دیدگاه شما',
                'english_title' => 'Reply to Your Comment',
                'description' => 'زمانی که کسی به دیدگاه شما پاسخ می‌دهد',
                'slug' => 'reply-to-comment',
            ],
            [
                'title' => 'پاسخ به گفتگو شما',
                'english_title' => 'Reply to Your Discussion',
                'description' => 'زمانی که کسی به گفتگوی شما پاسخ می‌دهد',
                'slug' => 'reply-to-discussion',
            ],
            [
                'title' => 'لایک و دیس‌لایک مطلب شما',
                'english_title' => 'Like/Dislike Your Post',
                'description' => 'زمانی که کسی مطلب شما را لایک یا دیس‌لایک می‌کند',
                'slug' => 'like-dislike-post',
            ],
            [
                'title' => 'پاسخ به گفتگویی که شما دنبال می‌کنید',
                'english_title' => 'Reply to Followed Discussion',
                'description' => 'زمانی که به گفتگویی که دنبال می‌کنید پاسخ داده می‌شود',
                'slug' => 'reply-to-followed-discussion',
            ],
            [
                'title' => 'پیام از طرف رزومه',
                'english_title' => 'Message from Resume',
                'description' => 'زمانی که پیامی از طریق رزومه شما دریافت می‌کنید',
                'slug' => 'message-from-resume',
            ],
            [
                'title' => 'ثبت دیدگاه در مقالات شما',
                'english_title' => 'Comment on Your Article',
                'description' => 'زمانی که در مقالات شما دیدگاه ثبت می‌شود',
                'slug' => 'comment-on-article',
            ],
            [
                'title' => 'دنبال‌کننده جدید',
                'english_title' => 'New Follower',
                'description' => 'زمانی که کسی شما را دنبال می‌کند',
                'slug' => 'new-follower',
            ],
            [
                'title' => 'اشاره کردن به شما (Mention)',
                'english_title' => 'Mention',
                'description' => 'زمانی که کسی در پست یا کامنتی به شما اشاره می‌کند',
                'slug' => 'mention',
            ],
            [
                'title' => 'پاسخ به سوال قدیمی',
                'english_title' => 'Old Question Answered',
                'description' => 'زمانی که به سوال قدیمی شما پاسخ داده می‌شود',
                'slug' => 'old-question-answered',
            ],
            [
                'title' => 'تایید گزارش',
                'english_title' => 'Report Approved',
                'description' => 'زمانی که گزارش شما تایید می‌شود',
                'slug' => 'report-approved',
            ],
            [
                'title' => 'رد گزارش',
                'english_title' => 'Report Rejected',
                'description' => 'زمانی که گزارش شما رد می‌شود',
                'slug' => 'report-rejected',
            ],
        ];

        foreach ($othersActivityEvents as $event) {
            Event::create([
                'title' => $event['title'],
                'english_title' => $event['english_title'],
                'slug' => $event['slug'],
                'description' => $event['description'],
                'event_group_id' => $othersActivityGroup->id,
                'is_email_enabled' => true,
                'is_sms_enabled' => true,
                'is_telegram_enabled' => false, // فعلا غیرفعال
                'is_site_enabled' => true,
            ]);
        }

        // گروه سوم: فعالیت افرادی که دنبال می‌کنید
        $followedActivityGroup = EventGroup::create([
            'title' => 'فعالیت افرادی که دنبال می‌کنید',
            'english_title' => 'Followed Users Activity',
            'description' => 'اطلاع‌رسانی‌های مربوط به فعالیت‌های افرادی که دنبال می‌کنید',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ]);

        $followedActivityEvents = [
            [
                'title' => 'ارسال گفتگو',
                'english_title' => 'New Discussion',
                'description' => 'زمانی که فردی که دنبال می‌کنید گفتگوی جدیدی ارسال می‌کند',
                'slug' => 'new-discussion',
            ],
            [
                'title' => 'پاسخ به گفتگو',
                'english_title' => 'Reply to Discussion by Followed User',
                'description' => 'زمانی که فردی که دنبال می‌کنید به گفتگویی پاسخ می‌دهد',
                'slug' => 'followed-user-reply-to-discussion',
            ],
        ];

        foreach ($followedActivityEvents as $event) {
            Event::create([
                'title' => $event['title'],
                'english_title' => $event['english_title'],
                'slug' => $event['slug'],
                'description' => $event['description'],
                'event_group_id' => $followedActivityGroup->id,
                'is_email_enabled' => true,
                'is_sms_enabled' => true,
                'is_telegram_enabled' => false, // فعلا غیرفعال
                'is_site_enabled' => true,
            ]);
        }

        // گروه چهارم: مدیریت و امنیت
        $managementGroup = EventGroup::create([
            'title' => 'مدیریت و امنیت',
            'english_title' => 'Management & Security',
            'description' => 'اطلاع‌رسانی‌های مربوط به مدیریت حساب کاربری و امنیت',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 1L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 1ZM12 7C13.4 7 14.8 8.6 14.8 10V11.5C15.4 11.5 16 12.1 16 12.7V16.2C16 16.8 15.4 17.3 14.8 17.3H9.2C8.6 17.3 8 16.8 8 16.2V12.7C8 12.1 8.6 11.5 9.2 11.5V10C9.2 8.6 10.6 7 12 7ZM12 8.2C11.2 8.2 10.5 8.7 10.5 10V11.5H13.5V10C13.5 8.7 12.8 8.2 12 8.2Z" fill="currentColor"/></svg>',
        ]);

        $managementEvents = [
            [
                'title' => 'تغییر نقش',
                'english_title' => 'Role Changed',
                'description' => 'زمانی که نقش‌های شما تغییر می‌کند',
                'slug' => 'role-changed',
            ],
            [
                'title' => 'مسدود شدن حساب',
                'english_title' => 'Account Suspended',
                'description' => 'زمانی که حساب کاربری شما مسدود می‌شود',
                'slug' => 'account-suspended',
            ],
            [
                'title' => 'رفع مسدودیت حساب',
                'english_title' => 'Account Unsuspended',
                'description' => 'زمانی که مسدودیت حساب کاربری شما رفع می‌شود',
                'slug' => 'account-unsuspended',
            ],
        ];

        foreach ($managementEvents as $event) {
            Event::create([
                'title' => $event['title'],
                'english_title' => $event['english_title'],
                'slug' => $event['slug'],
                'description' => $event['description'],
                'event_group_id' => $managementGroup->id,
                'is_email_enabled' => true,
                'is_sms_enabled' => true,
                'is_telegram_enabled' => false, // فعلا غیرفعال
                'is_site_enabled' => true,
            ]);
        }
    }
}


