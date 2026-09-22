<?php

namespace Database\Seeders;

use App\Models\Mission;
use App\Models\MissionCategory;
use Illuminate\Database\Seeder;

class MissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ایجاد دسته‌بندی‌های ماموریت
        $categories = [
            [
                'title' => 'خریدها',
                'english_title' => 'Purchases',
                'slug' => 'purchases',
            ],
            [
                'title' => 'تکمیل دوره‌ها',
                'english_title' => 'Course Completions',
                'slug' => 'course-completions',
            ],
            [
                'title' => 'دعوت‌ها',
                'english_title' => 'Invitations',
                'slug' => 'invitations',
            ],
            [
                'title' => 'فعالیت‌های جامعه',
                'english_title' => 'Community Activities',
                'slug' => 'community-activities',
            ],
            [
                'title' => 'انتشار دوره‌ها',
                'english_title' => 'Course Publishing',
                'slug' => 'course-publishing',
            ],
            [
                'title' => 'فعالیت کاربر',
                'english_title' => 'User Engagement',
                'slug' => 'user-engagement',
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $categoryData) {
            $category = MissionCategory::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
            $categoryIds[$categoryData['slug']] = $category->id;
        }

        // تعریف تمام ماموریت‌ها
        $missions = [
            // ========== خریدها ==========
            [
                'id' => 'loyal-buyer',
                'title' => 'خریدار متعهد',
                'category_id' => $categoryIds['purchases'],
                'icon' => '🛒',
                'description' => 'کاربری که حداقل 5 دوره آموزشی را خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 5, 'exp' => 2000, 'requirements' => []],
                    2 => ['goal' => 10, 'exp' => 4000, 'requirements' => []],
                    3 => ['goal' => 20, 'exp' => 10000, 'requirements' => []],
                    4 => ['goal' => 50, 'exp' => 20000, 'requirements' => []],
                    5 => ['goal' => 100, 'exp' => 50000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'top-spender',
                'title' => 'خریدار برتر',
                'category_id' => $categoryIds['purchases'],
                'icon' => '⭐',
                'description' => 'کاربری که بیشترین تعداد خرید دوره آموزشی در یک هفته را داشته است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                    2 => ['goal' => 2, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 3, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'first-purchase',
                'title' => 'اولین خرید',
                'category_id' => $categoryIds['purchases'],
                'icon' => '🎁',
                'description' => 'کاربری که برای اولین بار یک دوره آموزشی خریداری می‌کند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'skill-upgrade',
                'title' => 'ارتقای مهارت',
                'category_id' => $categoryIds['purchases'],
                'icon' => '📚',
                'description' => 'کاربری که تمامی دوره‌های آموزشی در یک حوزه تخصصی خاص خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fast-purchase',
                'title' => 'خریدار سریع',
                'category_id' => $categoryIds['purchases'],
                'icon' => '⚡',
                'description' => 'کاربری که در 24 ساعت اول پس از انتشار دوره، آن را خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'discount-purchase',
                'title' => 'خریدار اقتصادی',
                'category_id' => $categoryIds['purchases'],
                'icon' => '💰',
                'description' => 'کاربری که بیشترین تعداد دوره‌ها را در یک بازه تخفیف ویژه خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'category-completer',
                'title' => 'تکمیل کننده',
                'category_id' => $categoryIds['purchases'],
                'icon' => '✅',
                'description' => 'کاربری که تمامی دوره‌های موجود در یک دسته‌بندی خاص را خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'diverse-purchaser',
                'title' => 'خریدار متنوع',
                'category_id' => $categoryIds['purchases'],
                'icon' => '🌈',
                'description' => 'کاربری که حداقل 2 دوره‌های آموزشی از 5 حوزه مختلف خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 2, 'exp' => 4000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 8000, 'requirements' => []],
                    3 => ['goal' => 4, 'exp' => 12000, 'requirements' => []],
                    4 => ['goal' => 5, 'exp' => 20000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'top-purchaser',
                'title' => 'حامی سایت',
                'category_id' => $categoryIds['purchases'],
                'icon' => '💎',
                'description' => 'کاربری که بیشترین هزینه را برای خرید دوره‌ها در یکماه انجام داده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'loyal-customer',
                'title' => 'خریدار وفادار',
                'category_id' => $categoryIds['purchases'],
                'icon' => '💝',
                'description' => 'کاربری که به صورت مداوم در طی یک سال چندین دوره آموزشی خریداری کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                ],
            ],

            // ========== تکمیل دوره‌ها ==========
            [
                'id' => 'course-completion',
                'title' => 'دانش‌آموخته',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '🎓',
                'description' => 'کاربری که حداقل 1 دوره آموزشی را با موفقیت گذرانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 12000, 'requirements' => []],
                    4 => ['goal' => 25, 'exp' => 24000, 'requirements' => []],
                    5 => ['goal' => 50, 'exp' => 50000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'specialist-completion',
                'title' => 'تخصص‌یافته',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '🎯',
                'description' => 'کاربری که حداقل 10 دوره از یک دسته‌بندی خاص را به پایان رسانده است.',
                'levels' => [
                    1 => ['goal' => 10, 'exp' => 10000, 'requirements' => []],
                    2 => ['goal' => 20, 'exp' => 20000, 'requirements' => []],
                    3 => ['goal' => 30, 'exp' => 40000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'diverse-completion',
                'title' => 'یادگیرنده همه‌جانبه',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '🌍',
                'description' => 'کاربری که 5 دوره در حداقل 2 حوزه مختلف را با موفقیت گذرانده است.',
                'levels' => [
                    1 => ['goal' => 2, 'exp' => 6000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 12000, 'requirements' => []],
                    3 => ['goal' => 4, 'exp' => 20000, 'requirements' => []],
                    4 => ['goal' => 5, 'exp' => 30000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fastest-completion',
                'title' => 'سریع‌ترین تکمیل‌کننده',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '🏃',
                'description' => 'کاربری که یک دوره را در کمترین زمان ممکن پس از ثبت‌نام به اتمام رسانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'interactive-course',
                'title' => 'دوره‌های تعاملی',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '🤝',
                'description' => 'کاربری که دوره‌هایی با بالاترین سطح تعامل را به اتمام رسانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 8000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 16000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'ongoing-learning',
                'title' => 'یادگیری مداوم',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '📖',
                'description' => 'کاربری که به طور مداوم و در طول یک سال چندین دوره را به اتمام رسانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'persistence',
                'title' => 'پشتکار',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '💪',
                'description' => 'کاربری که یک دوره طولانی یا دشوار را به اتمام رسانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'special-course',
                'title' => 'تکمیل دوره‌های ویژه',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '⭐',
                'description' => 'کاربری که دوره‌هایی با موضوعات خاص یا پیشرفته را به پایان رسانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'progress-maker',
                'title' => 'پیشرفت‌کننده',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '📈',
                'description' => 'کاربری که در یک ماه بیشترین تعداد دوره را نسبت به ماه قبل گذرانده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'reviewer',
                'title' => 'بازبینی',
                'category_id' => $categoryIds['course-completions'],
                'icon' => '📝',
                'description' => 'کاربری که پس از گذراندن دوره‌ها، بیشترین تعداد بازبینی یا نقد و بررسی دوره‌ها را ارائه داده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 12000, 'requirements' => []],
                ],
            ],

            // ========== دعوت‌ها ==========
            [
                'id' => 'active-inviter',
                'title' => 'دعوت‌گر فعال',
                'category_id' => $categoryIds['invitations'],
                'icon' => '👥',
                'description' => 'کاربری که حداقل 1 کاربر را با استفاده از لینک دعوت به سایت جذب کرده است. (حداکثر 200 کاربر دعوتی)',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                    2 => ['goal' => 10, 'exp' => 4000, 'requirements' => []],
                    3 => ['goal' => 25, 'exp' => 10000, 'requirements' => []],
                    4 => ['goal' => 50, 'exp' => 20000, 'requirements' => []],
                    5 => ['goal' => 100, 'exp' => 40000, 'requirements' => []],
                    6 => ['goal' => 200, 'exp' => 100000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'successful-inviter',
                'title' => 'جذب‌کننده موفق',
                'category_id' => $categoryIds['invitations'],
                'icon' => '✅',
                'description' => 'کاربری که حداقل 1 کاربر دعوت‌شده توسط او در سایت ثبت‌نام کرده و فعالیت کرده‌اند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 12000, 'requirements' => []],
                    4 => ['goal' => 25, 'exp' => 24000, 'requirements' => []],
                    5 => ['goal' => 50, 'exp' => 50000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'network-builder',
                'title' => 'شبکه‌ساز',
                'category_id' => $categoryIds['invitations'],
                'icon' => '🌐',
                'description' => 'کاربری که حداقل 200 کاربر را با استفاده از لینک دعوت به سایت جذب کرده است.',
                'levels' => [
                    1 => ['goal' => 200, 'exp' => 40000, 'requirements' => []],
                    2 => ['goal' => 500, 'exp' => 100000, 'requirements' => []],
                    3 => ['goal' => 1000, 'exp' => 200000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'site-ambassador',
                'title' => 'سفیر سایت',
                'category_id' => $categoryIds['invitations'],
                'icon' => '👑',
                'description' => 'کاربری که باعث دعوت حداقل 10 هزار کاربر به سایت شده است.',
                'levels' => [
                    1 => ['goal' => 10000, 'exp' => 1000000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'social-networker',
                'title' => 'شبکه‌ساز اجتماعی',
                'category_id' => $categoryIds['invitations'],
                'icon' => '📱',
                'description' => 'کاربری که لینک دعوت خود را به بیشترین تعداد شبکه‌های اجتماعی یا پلتفرم‌های مختلف به اشتراک گذاشته است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'influential-inviter',
                'title' => 'دعوت‌گر تاثیرگذار',
                'category_id' => $categoryIds['invitations'],
                'icon' => '🌟',
                'description' => 'کاربری که حداقل 5 کاربر دعوت‌شده توسط او، ماموریت خریدار متعهد را کسب کرده باشند.',
                'levels' => [
                    1 => ['goal' => 5, 'exp' => 20000, 'requirements' => []],
                    2 => ['goal' => 10, 'exp' => 50000, 'requirements' => []],
                    3 => ['goal' => 20, 'exp' => 100000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'community-grower',
                'title' => 'رشد‌دهنده جامعه',
                'category_id' => $categoryIds['invitations'],
                'icon' => '🌱',
                'description' => 'کاربری که لینک دعوتش باعث دعوت حداقل 200 کاربر فعال شده است.',
                'levels' => [
                    1 => ['goal' => 200, 'exp' => 60000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'continuous-inviter',
                'title' => 'دعوت‌گر مستمر',
                'category_id' => $categoryIds['invitations'],
                'icon' => '🔄',
                'description' => 'کاربری که در یک بازه زمانی مشخص (مثلاً هر ماه) حداقل 20 کاربر جدید را با لینک دعوت به سایت جذب کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fast-inviter',
                'title' => 'دعوت‌گر سریع',
                'category_id' => $categoryIds['invitations'],
                'icon' => '🚀',
                'description' => 'کاربری که در کوتاه‌ترین زمان پس از دریافت لینک دعوت، بیشترین تعداد افراد را به سایت جذب کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'innovative-inviter',
                'title' => 'دعوت‌گر نوآور',
                'category_id' => $categoryIds['invitations'],
                'icon' => '💡',
                'description' => 'کاربری که با استفاده از روش‌های خلاقانه و نوآورانه، لینک دعوت خود را به دیگران معرفی کرده و افراد بیشتری را به سایت جذب کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 12000, 'requirements' => []],
                ],
            ],

            // ========== فعالیت‌های جامعه ==========
            [
                'id' => 'active-contributor',
                'title' => 'فعال‌ترین مشارکت‌کننده',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '💬',
                'description' => 'کاربری که در یک ماه گذشته بیشترین تعداد کامنت را در دوره‌های مختلف ارسال کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'top-responder',
                'title' => 'پاسخ‌دهنده حرفه‌ای',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🎯',
                'description' => 'کاربری که بیشترین تعداد پاسخ مفید (تایید شده توسط دیگر کاربران) به کامنت‌های دیگران داده است. مجموع پاسخ هایی که حداقل 50 نظر مثبت گرفته باشند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 10000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 20000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'popular-commenter',
                'title' => 'کامنت‌های پرطرفدار',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '❤️',
                'description' => 'کاربری که کامنت‌هایش بیشترین تعداد لایک یا رای مثبت را از سایر کاربران دریافت کرده است.',
                'levels' => [
                    1 => ['goal' => 10, 'exp' => 2000, 'requirements' => []],
                    2 => ['goal' => 50, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 100, 'exp' => 12000, 'requirements' => []],
                    4 => ['goal' => 500, 'exp' => 30000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'valuable-comment',
                'title' => 'کامنت با ارزش',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '💎',
                'description' => 'کاربری که اولین کامنت مفید در یک دوره جدید را ارسال کرده است. کامنتی که پس از تایید، اولین امتیاز نظر مثبت گرفته باشه.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'inquirer',
                'title' => 'پرسشگر فعال',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '❓',
                'description' => 'کاربری که بیشترین امتیاز برای پرسش خود در یک بازه زمانی مشخص (مثلاً ماهانه یا هفتگی) کسب کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'persistent-inquirer',
                'title' => 'پرسشگر کنجکاو',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🔍',
                'description' => 'کاربری که حداقل 1 پرسش چالشی و بحث‌برانگیز را مطرح کرده است. (پرسشی چالش برانگیز است که حداقل 10 پاسخ در روز اول پرسش داشته باشد)',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 10000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 20000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'problem-solver',
                'title' => 'حل‌کننده مشکلات',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '✅',
                'description' => 'کاربری که حداقل 1 پاسخش توسط پرسشگران به عنوان بهترین پاسخ انتخاب شده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 16000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 30000, 'requirements' => []],
                    4 => ['goal' => 25, 'exp' => 60000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'issue-reporter',
                'title' => 'گزارشگر دقیق',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🚨',
                'description' => 'کاربری که حداقل 1 گزارش معتبر و تأیید شده توسط مدیریت سایت را ارائه داده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 6000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 12000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'constructive-feedback',
                'title' => 'بازخورد دهنده فعال',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '💭',
                'description' => 'کاربری که در یک بازه زمانی مشخص (مثلاً هر ماه) حداقل 10 انتقاد و بازخورد سازنده را ارائه کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'analytical-responder',
                'title' => 'تحلیل‌گر پرسش',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '📊',
                'description' => 'کاربری که به پرسش‌ها پاسخ‌های جامع و با تحلیل عمیق ارائه کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 12000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 24000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'first-responder',
                'title' => 'جستجوگر',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🔍',
                'description' => 'کاربری که حداقل 1 بار اولین پاسخ داده باشد.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                    2 => ['goal' => 5, 'exp' => 8000, 'requirements' => []],
                    3 => ['goal' => 10, 'exp' => 16000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'popular-questions',
                'title' => 'پرسش‌های پرتکرار',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🔥',
                'description' => 'کاربری که پرسش‌هایش بیشترین تعداد مشاهده و پاسخ را از دیگر کاربران دریافت کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 10000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 20000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'question-guide',
                'title' => 'راهنمای پرسشگر',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🧭',
                'description' => 'کاربری که به دیگران کمک کرده تا پرسش‌های بهتری مطرح کنند یا پرسش‌های خود را بهبود دهند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'question-follower',
                'title' => 'پیگیری',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '👀',
                'description' => 'کاربری که پرسش‌های خود را پیگیری کرده و به پاسخ‌ها واکنش نشان داده است، مانند تشکر، رای مثبت یا تایید بهترین پاسخ.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'community-guardian',
                'title' => 'نگهبان جامعه',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🛡️',
                'description' => 'کاربری که گزارش‌هایی را ارائه کرده که منجر به بهبود یا اصلاح محتوای پرسش و پاسخ‌ها شده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fast-reporter',
                'title' => 'گزارش سریع',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '⚡',
                'description' => 'کاربری که سریع‌ترین زمان را بین مشاهده یک مشکل و ارسال گزارش آن داشته است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'active-reporter',
                'title' => 'گزارش‌دهنده فعال',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '📋',
                'description' => 'کاربری که در یک بازه زمانی مشخص (مثلاً هر ماه) حداقل 10 گزارش تایید شده را ارسال کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'constructive-reporter',
                'title' => 'گزارشگر سازنده',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🔧',
                'description' => 'کاربری که گزارش‌هایش منجر به اقدامات اصلاحی مانند ویرایش، حذف، یا بسته شدن پرسش یا پاسخ‌های نامناسب شده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'reviewer-community',
                'title' => 'بازبینی‌کننده',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🔍',
                'description' => 'کاربری که گزارش‌هایش باعث شده تا محتوای پرسش و پاسخ‌ها توسط تیم مدیریت بررسی و بازبینی شود.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'educational-reporter',
                'title' => 'گزارش‌های آموزشی',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '📚',
                'description' => 'کاربری که گزارش‌هایی را ارائه کرده که به بهبود کیفیت آموزشی محتوا کمک کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'collaborative-reporter',
                'title' => 'گزارش‌دهنده همکار',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🤝',
                'description' => 'کاربری که به سایر کاربران کمک کرده تا گزارش‌های خود را تکمیل کنند یا مشکلات مشابه را گزارش دهند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fair-reporter',
                'title' => 'گزارشگر منصف',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '⚖️',
                'description' => 'کاربری که گزارش‌های دقیق و منصفانه‌ای را ارائه کرده که از هرگونه سوگیری یا تعصب خالی بوده‌اند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'supportive-reporter',
                'title' => 'گزارشگر پشتیبان',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '💪',
                'description' => 'کاربری که علاوه بر گزارش، راهکارهای سازنده‌ای برای حل مشکلات ارائه کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'reformer',
                'title' => 'اصلاح‌گر',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🔨',
                'description' => 'کاربری که انتقادهایش منجر به تغییرات مثبت و بهبود در سایت یا محتوای آموزشی شده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'accurate-feedback',
                'title' => 'بازخورد دقیق',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '📊',
                'description' => 'کاربری که انتقادهای دقیق و مبتنی بر داده‌ها و شواهد مشخص ارائه کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'comprehensive-feedback',
                'title' => 'بازخورد جامع',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '📑',
                'description' => 'کاربری که بازخوردهای جامعی ارائه کرده که به جوانب مختلف یک دوره یا بخش سایت پرداخته است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'collaborative-feedback',
                'title' => 'بازخورد همکار',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '🤝',
                'description' => 'کاربری که علاوه بر ارائه انتقاد، راهکارهای سازنده‌ای برای بهبود ارائه داده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'transparency',
                'title' => 'شفاف‌سازی',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '💡',
                'description' => 'کاربری که انتقادهایش منجر به شفاف‌سازی سیاست‌ها، رویه‌ها یا محتوای سایت شده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'feedback-follower',
                'title' => 'پیگیری بازخورد',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '👀',
                'description' => 'کاربری که انتقادهای خود را پیگیری کرده و به پاسخ‌های تیم مدیریت واکنش نشان داده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fair-critic',
                'title' => 'منتقد منصف',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '⚖️',
                'description' => 'کاربری که انتقادهای منصفانه و متعادل، بدون تعصب یا سوگیری ارائه کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'innovative-critic',
                'title' => 'انتقادگر نوآور',
                'category_id' => $categoryIds['community-activities'],
                'icon' => '💡',
                'description' => 'کاربری که انتقادهای خلاقانه و نوآورانه‌ای ارائه کرده که منجر به ایده‌های جدید برای بهبود سایت شده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                ],
            ],

            // ========== انتشار دوره‌ها ==========
            [
                'id' => 'first-publication',
                'title' => 'اولین انتشار',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🎬',
                'description' => 'مدرسی که اولین دوره آموزشی خود را در سایت منتشر می‌کند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'fast-publisher',
                'title' => 'انتشار سریع',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '⚡',
                'description' => 'مدرسی که در یک بازه زمانی مشخص بیشترین تعداد دوره‌های آموزشی را منتشر کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 4000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'top-teacher',
                'title' => 'مدرس محبوب',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🏆',
                'description' => 'مدرسی که دوره‌ای با حداقل 100 خرید را در هفته اول انتشار دوره داشته باشد.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 20000, 'requirements' => []],
                    2 => ['goal' => 2, 'exp' => 50000, 'requirements' => []],
                    3 => ['goal' => 3, 'exp' => 100000, 'requirements' => []],
                    4 => ['goal' => 4, 'exp' => 150000, 'requirements' => []],
                    5 => ['goal' => 5, 'exp' => 200000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'active-collaborator',
                'title' => 'همکار فعال',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🤝',
                'description' => 'مدرسی که بیشترین تعداد همکاری یا اشتراک‌گذاری محتوا با دیگر مدرسان را در دوره‌های خود داشته است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 16000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 30000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'innovative-publisher',
                'title' => 'نوآور',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '💡',
                'description' => 'مدرسی که اولین دوره آموزشی در یک موضوع جدید و خلاقانه را منتشر کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 12000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'interactive-publisher',
                'title' => 'دوره‌های تعاملی',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🎮',
                'description' => 'مدرسی که بیشترین تعداد جلسات پرسش و پاسخ زنده (وبینار) یا فعالیت‌های عملی در دوره‌های خود را ارائه داده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 20000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 40000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'skill-improvement',
                'title' => 'ارتقای مهارت',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '📈',
                'description' => 'مدرسی که دوره‌های آموزشی در چندین حوزه تخصصی مختلف منتشر کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'positive-feedback',
                'title' => 'بازخورد مثبت',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '👍',
                'description' => 'مدرسی که برای یک دوره حداقل 1000 بازخورد مثبت و امتیاز بالا را از کاربران دریافت کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 30000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'golden-feedback',
                'title' => 'بازخورد طلایی',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🏅',
                'description' => 'مدرسی که برای 5 دوره، ماموریت بازخورد مثبت دریافت کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 100000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'productive-teacher',
                'title' => 'مدرسان پرکار',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '📚',
                'description' => 'مدرسی که بیشترین تعداد دوره‌های آموزشی را در یک بازه زمانی مشخص (مثلاً هر ماه یا هر هفته) منتشر کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 6000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'active-teacher',
                'title' => 'مدرسان فعال',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🎯',
                'description' => 'مدرسی که بیشترین مدت زمان دوره آموزشی را در یک بازه زمانی مشخص (مثلاً هر ماه یا هر هفته) منتشر کرده است.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 8000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'top-seller-teacher',
                'title' => 'پرفروش‌ترین مدرس',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '💎',
                'description' => 'مدرسی که برای 5 دوره، ماموریت مدرس محبوب را کسب کند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 200000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'popular-courses',
                'title' => 'دوره‌های محبوب',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🔥',
                'description' => 'مدرسی که دوره‌هایش بیشترین تعداد ثبت‌نام یا خرید را در یک بازه زمانی خاص داشته‌اند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 16000, 'requirements' => []],
                    2 => ['goal' => 3, 'exp' => 40000, 'requirements' => []],
                    3 => ['goal' => 5, 'exp' => 80000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'top-master',
                'title' => 'استاد برتر',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '👑',
                'description' => 'مدرسی که دوره‌هایش به عنوان دوره‌های برتر از نظر امتیازدهی خریداران دوره در یک ماه یا هفته انتخاب شده‌اند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 40000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'consistent-publisher',
                'title' => 'ناشر مداوم',
                'category_id' => $categoryIds['course-publishing'],
                'icon' => '🔄',
                'description' => 'مدرسانی که به‌طور مداوم محتوای جدید ارائه می‌دهند و همچنان پرفروش هستند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 30000, 'requirements' => []],
                ],
            ],

            // ========== فعالیت کاربر (امتیاز رویدادها — قابل تنظیم از ادمین) ==========
            [
                'id' => 'user-registration',
                'title' => 'ثبت‌نام در زنبورک',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '🎉',
                'description' => 'پاداش ثبت‌نام اولیه در سایت.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 50000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'invitee-reward',
                'title' => 'ثبت‌نام با کد دعوت',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '🎁',
                'description' => 'پاداش کاربرانی که با کد دعوت ثبت‌نام می‌کنند.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 25000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'inviter-reward',
                'title' => 'پاداش دعوت‌کننده',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '🤝',
                'description' => 'امتیاز هر بار دعوت موفق یک کاربر جدید.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 50000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'profile-completed',
                'title' => 'تکمیل پروفایل',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '👤',
                'description' => 'تکمیل اطلاعات پروفایل کاربری.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'mobile-verified',
                'title' => 'تایید شماره موبایل',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '📱',
                'description' => 'تایید شماره موبایل کاربر.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'email-verified',
                'title' => 'تایید ایمیل',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '✉️',
                'description' => 'تایید آدرس ایمیل کاربر.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'daily-login',
                'title' => 'ورود روزانه',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '📅',
                'description' => 'امتیاز ورود روزانه بر اساس روزهای متوالی. سطح ۱=روز اول، سطح ۲=روز دوم، سطح ۳=روز سوم به بعد.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                    2 => ['goal' => 2, 'exp' => 1000, 'requirements' => []],
                    3 => ['goal' => 3, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'user-followed',
                'title' => 'دنبال کردن کاربر',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '➕',
                'description' => 'امتیاز دنبال کردن هر کاربر.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'course-purchase',
                'title' => 'خرید دوره',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '🛒',
                'description' => 'امتیاز هر بار خرید دوره آموزشی.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 2000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'vip-upgraded',
                'title' => 'ارتقا به VIP',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '👑',
                'description' => 'پاداش ارتقا به اشتراک VIP.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 10000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'certificate-issued',
                'title' => 'دریافت گواهینامه',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '📜',
                'description' => 'امتیاز دریافت گواهینامه دوره.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 5000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'episode-fully-watched',
                'title' => 'مشاهده کامل اپیزود',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '▶️',
                'description' => 'امتیاز مشاهده کامل هر اپیزود.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'submit-question',
                'title' => 'ثبت پرسش',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '❓',
                'description' => 'امتیاز ثبت هر پرسش جدید.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'submit-first-answer',
                'title' => 'اولین پاسخ به پرسش',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '💡',
                'description' => 'امتیاز اولین پاسخ به یک پرسش.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 3000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'submit-answer',
                'title' => 'ثبت پاسخ',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '💬',
                'description' => 'امتیاز ثبت پاسخ برای پرسش‌ها.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1500, 'requirements' => []],
                ],
            ],
            [
                'id' => 'best-answer',
                'title' => 'بهترین پاسخ',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '🏆',
                'description' => 'امتیاز انتخاب شدن پاسخ به‌عنوان بهترین پاسخ.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 15000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'like-on-answer',
                'title' => 'لایک روی پاسخ',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '👍',
                'description' => 'امتیاز دریافت لایک روی پاسخ.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'like-on-question',
                'title' => 'لایک روی پرسش',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '👍',
                'description' => 'امتیاز دریافت لایک روی پرسش.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'like-on-comment',
                'title' => 'لایک روی نظر',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '👍',
                'description' => 'امتیاز دریافت لایک روی نظر.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'comment-on-episode',
                'title' => 'نظر روی اپیزود',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '💭',
                'description' => 'امتیاز ثبت نظر روی اپیزود.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1500, 'requirements' => []],
                ],
            ],
            [
                'id' => 'reply-to-comment',
                'title' => 'پاسخ به نظر',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '↩️',
                'description' => 'امتیاز پاسخ دادن به یک نظر.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'rating-submitted',
                'title' => 'ثبت امتیاز دوره',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '⭐',
                'description' => 'امتیاز ثبت ریتینگ برای دوره.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
            [
                'id' => 'report-approved',
                'title' => 'گزارش مفید',
                'category_id' => $categoryIds['user-engagement'],
                'icon' => '🚩',
                'description' => 'امتیاز تایید شدن گزارش مفید.',
                'levels' => [
                    1 => ['goal' => 1, 'exp' => 1000, 'requirements' => []],
                ],
            ],
        ];

        // ایجاد یا به‌روزرسانی ماموریت‌ها
        foreach ($missions as $missionData) {
            $levels = $missionData['levels'];
            unset($missionData['levels']);

            Mission::updateOrCreate(
                ['id' => $missionData['id']],
                array_merge($missionData, [
                    'levels' => json_encode($levels),
                ])
            );
        }

        // $this->command->info('تمام ماموریت‌ها با موفقیت ایجاد شدند!');
    }
}

