<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqCategoriesAndFaqsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // دسته‌بندی 1: شروع کار
        $category1Id = DB::table('faq_categories')->insertGetId([
            'title' => 'شروع کار',
            'english_title' => 'getting-started',
            'slug' => 'getting-started',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M13.83 2.3a1 1 0 0 0-1.66 0L4.6 13.44A1 1 0 0 0 5.43 15H10l-1.3 6.35a1 1 0 0 0 1.78.79l7.92-11.58A1 1 0 0 0 17.57 9H13l1.6-6.03a1 1 0 0 0-.77-.67Z"/></svg>',
            'order' => 1,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // FAQ های دسته‌بندی شروع کار
        $faqs1 = [
            [
                'category_id' => $category1Id,
                'question' => 'چطور در سایت ثبت‌نام کنم؟',
                'answer' => 'برای ثبت‌نام در سایت، روی دکمه "ثبت‌نام" کلیک کنید و اطلاعات خود شامل نام، ایمیل و رمز عبور را وارد کنید. سپس ایمیل خود را تایید کنید تا حساب کاربری شما فعال شود.',
                'order' => 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category1Id,
                'question' => 'چطور دوره‌ها را خریداری کنم؟',
                'answer' => 'برای خرید دوره، ابتدا وارد حساب کاربری خود شوید. سپس به صفحه دوره مورد نظر بروید و روی دکمه "خرید دوره" کلیک کنید. می‌توانید با کیف پول یا پرداخت آنلاین خرید کنید.',
                'order' => 2,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category1Id,
                'question' => 'آیا دوره‌ها برای همیشه در دسترس هستند؟',
                'answer' => 'بله، پس از خرید دوره، دسترسی شما به محتوای دوره برای همیشه باقی می‌ماند و می‌توانید هر زمان که بخواهید به آن دسترسی داشته باشید.',
                'order' => 3,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category1Id,
                'question' => 'چطور رمز عبور خود را تغییر دهم؟',
                'answer' => 'برای تغییر رمز عبور، وارد پنل کاربری خود شوید و به بخش "تنظیمات" بروید. در قسمت "تغییر رمز عبور" رمز جدید خود را وارد کنید.',
                'order' => 4,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // دسته‌بندی 2: دوره‌ها و آموزش
        $category2Id = DB::table('faq_categories')->insertGetId([
            'title' => 'دوره‌ها و آموزش',
            'english_title' => 'courses-and-learning',
            'slug' => 'courses-and-learning',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M11.7 2.84a1 1 0 0 1 .6 0l9 3a1 1 0 0 1 0 1.9l-9 3a1 1 0 0 1-.6 0L6 8.83v.01l-1.7-.57v4.48c.6.35 1 1 1 1.75a2 2 0 0 1-1 1.73l.9 2.44a.5.5 0 0 1-.47.67H3.27a.5.5 0 0 1-.47-.67l.9-2.44a2 2 0 0 1 .3-3.71V7.6L2.7 7.74a1 1 0 0 1 0-1.9l9-3Z"/><path d="M6.5 11.02 11.4 12.66a2 2 0 0 0 1.2 0l4.9-1.64v3.09c0 .8-.44 1.55-1.16 1.88-1.24.57-3.2 1.35-5.34 1.35s-4.1-.78-5.34-1.35A2.07 2.07 0 0 1 4.5 14.1v-3.09l2 .67Z"/></svg>',
            'order' => 2,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $faqs2 = [
            [
                'category_id' => $category2Id,
                'question' => 'آیا دوره‌ها به صورت آنلاین برگزار می‌شوند؟',
                'answer' => 'بله، تمام دوره‌های ما به صورت آنلاین و ویدیویی هستند. شما می‌توانید در هر زمان و مکان که بخواهید به محتوای دوره دسترسی داشته باشید.',
                'order' => 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category2Id,
                'question' => 'آیا می‌توانم فایل‌های دوره را دانلود کنم؟',
                'answer' => 'بله، در صورت خرید دوره، می‌توانید فایل‌های دوره را دانلود کنید. این امکان در پنل کاربری شما در بخش "دوره‌های من" موجود است.',
                'order' => 2,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category2Id,
                'question' => 'آیا گواهینامه پایان دوره ارائه می‌شود؟',
                'answer' => 'بله، پس از تکمیل دوره و گذراندن آزمون‌های مربوطه، گواهینامه معتبر پایان دوره برای شما صادر می‌شود که می‌توانید آن را دانلود کنید.',
                'order' => 3,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category2Id,
                'question' => 'چطور می‌توانم پیشرفت خود را در دوره ببینم؟',
                'answer' => 'در پنل کاربری خود، به بخش "دوره‌های من" بروید. در آنجا می‌توانید درصد پیشرفت خود در هر دوره را مشاهده کنید و ببینید کدام قسمت‌ها را تماشا کرده‌اید.',
                'order' => 4,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category2Id,
                'question' => 'آیا می‌توانم سوالات خود را از مدرس بپرسم؟',
                'answer' => 'بله، در بخش نظرات هر دوره می‌توانید سوالات خود را مطرح کنید و مدرس یا سایر دانشجویان به شما پاسخ خواهند داد.',
                'order' => 5,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category2Id,
                'question' => 'مراحل دریافت گارانتی بازگشت وجه چیست؟',
                'answer' => "برای استفاده از گارانتی بازگشت وجه این مراحل را دنبال کنید:\n\n1. **تماشای کامل محتوا:** ابتدا تمام جلسات و محتوای دوره را به‌طور کامل مشاهده کنید.\n2. **بررسی نتیجه:** اگر پس از اتمام دوره به نتیجه‌ای که در توضیحات دوره وعده داده شده بود نرسیدید، می‌توانید درخواست گارانتی ثبت کنید.\n3. **ثبت درخواست:** از بخش «تماس با ما» یا تیکت پشتیبانی در پنل کاربری، درخواست بازگشت وجه خود را با ذکر نام دوره و شماره سفارش ثبت کنید.\n4. **بررسی توسط پشتیبانی:** تیم پشتیبانی درخواست شما را بررسی می‌کند و در صورت تأیید شرایط گارانتی، **۱۰۰٪ مبلغ پرداختی** به همان روش پرداخت یا کیف پول شما بازگردانده می‌شود.\n\n> توجه: گارانتی فقط برای دوره‌هایی فعال است که در صفحه دوره بخش «گارانتی بازگشت وجه» برای آن‌ها نمایش داده می‌شود.",
                'order' => 6,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // دسته‌بندی 3: پرداخت و اشتراک VIP
        $category3Id = DB::table('faq_categories')->insertGetId([
            'title' => 'پرداخت و اشتراک VIP',
            'english_title' => 'payment-and-vip',
            'slug' => 'payment-and-vip',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M3.3 8.2a1 1 0 0 1 1.5-.55l3.02 1.9 3.35-4.9a1 1 0 0 1 1.66 0l3.35 4.9 3.02-1.9a1 1 0 0 1 1.5 1.06l-1.6 8.04a1 1 0 0 1-.98.8H5.88a1 1 0 0 1-.98-.8L3.3 8.2Z"/><path d="M5.5 19.5a1 1 0 0 1 1-1h11a1 1 0 1 1 0 2h-11a1 1 0 0 1-1-1Z"/></svg>',
            'order' => 3,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $faqs3 = [
            [
                'category_id' => $category3Id,
                'question' => 'چطور اشتراک VIP خریداری کنم؟',
                'answer' => 'برای خرید اشتراک VIP، وارد پنل کاربری خود شوید و به بخش "اشتراک VIP" بروید. سپس پلن مورد نظر خود را انتخاب کرده و با کیف پول یا پرداخت آنلاین خرید کنید.',
                'order' => 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category3Id,
                'question' => 'مزایای اشتراک VIP چیست؟',
                'answer' => 'با اشتراک VIP به تمام دوره‌ها دسترسی نامحدود دارید، می‌توانید فایل‌های دوره را دانلود کنید، از پشتیبانی اولویت‌دار بهره‌مند شوید و به دوره‌های جدید قبل از انتشار عمومی دسترسی داشته باشید.',
                'order' => 2,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category3Id,
                'question' => 'چطور کیف پول خود را شارژ کنم؟',
                'answer' => 'برای شارژ کیف پول، وارد پنل کاربری خود شوید و به بخش "کیف پول" بروید. سپس مبلغ مورد نظر را انتخاب کرده و با پرداخت آنلاین کیف پول خود را شارژ کنید.',
                'order' => 3,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category3Id,
                'question' => 'آیا می‌توانم از کیف پول برای خرید استفاده کنم؟',
                'answer' => 'بله، در هنگام خرید دوره یا اشتراک VIP می‌توانید از موجودی کیف پول خود استفاده کنید. همچنین می‌توانید ترکیبی از کیف پول و پرداخت آنلاین استفاده کنید.',
                'order' => 4,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category3Id,
                'question' => 'چطور می‌توانم فاکتور خرید خود را دریافت کنم؟',
                'answer' => 'پس از هر خرید، فاکتور به صورت خودکار به ایمیل شما ارسال می‌شود. همچنین می‌توانید در بخش "خریدهای من" در پنل کاربری، فاکتورهای خود را مشاهده و دانلود کنید.',
                'order' => 5,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // دسته‌بندی 4: مسیرهای یادگیری
        $category4Id = DB::table('faq_categories')->insertGetId([
            'title' => 'مسیرهای یادگیری',
            'english_title' => 'learning-paths',
            'slug' => 'learning-paths',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M7 2a3 3 0 0 0-1 5.83V16a3 3 0 0 0 3 3h6a1 1 0 0 0 0-2H9a1 1 0 0 1-1-1V7.83A3 3 0 0 0 7 2Zm0 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/><path d="M17 9a3 3 0 0 0-1 5.83V16a1 1 0 1 0 2 0v-1.17A3 3 0 0 0 17 9Zm0 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/></svg>',
            'order' => 4,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $faqs4 = [
            [
                'category_id' => $category4Id,
                'question' => 'مسیر یادگیری چیست؟',
                'answer' => 'مسیر یادگیری یک مجموعه منظم از دوره‌هاست که به صورت گام به گام شما را از مبتدی به حرفه‌ای تبدیل می‌کند. هر مسیر شامل دوره‌های مرتبط و پیش‌نیازهای لازم است.',
                'order' => 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category4Id,
                'question' => 'چطور مسیر یادگیری مناسب خود را انتخاب کنم؟',
                'answer' => 'برای انتخاب مسیر مناسب، ابتدا سطح فعلی خود را مشخص کنید (مبتدی، متوسط، پیشرفته). سپس علاقه‌مندی خود را در نظر بگیرید (فرانت‌اند، بک‌اند، موبایل و...). توصیه می‌کنیم از مسیرهای مقدماتی شروع کنید.',
                'order' => 2,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category4Id,
                'question' => 'آیا باید دوره‌های یک مسیر را به ترتیب بگذرانم؟',
                'answer' => 'بله، توصیه می‌کنیم دوره‌های هر مسیر را به ترتیب پیشنهادی بگذرانید، زیرا هر دوره بر اساس دانش دوره قبلی ساخته شده است. اما در صورت داشتن دانش قبلی می‌توانید برخی دوره‌ها را رد کنید.',
                'order' => 3,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // دسته‌بندی 5: پشتیبانی و کمک
        $category5Id = DB::table('faq_categories')->insertGetId([
            'title' => 'پشتیبانی و کمک',
            'english_title' => 'support-and-help',
            'slug' => 'support-and-help',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2a9 9 0 0 0-9 9v4.5A2.5 2.5 0 0 0 5.5 18H7a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5.06A7 7 0 0 1 19 11h-1.9a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h.4a2 2 0 0 1-1.9 1.5h-1.35a1.5 1.5 0 1 0 0 2h1.35a4 4 0 0 0 3.9-3.13A2.5 2.5 0 0 0 21 15.5V11a9 9 0 0 0-9-9Z"/></svg>',
            'order' => 5,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $faqs5 = [
            [
                'category_id' => $category5Id,
                'question' => 'چطور با پشتیبانی تماس بگیرم؟',
                'answer' => 'می‌توانید از طریق بخش "تماس با ما" در سایت، تیکت پشتیبانی ایجاد کنید. همچنین می‌توانید از طریق ایمیل یا چت آنلاین با ما در ارتباط باشید. زمان پاسخگویی معمولاً کمتر از 24 ساعت است.',
                'order' => 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category5Id,
                'question' => 'آیا پشتیبانی رایگان است؟',
                'answer' => 'بله، پشتیبانی برای تمام کاربران رایگان است. اما کاربران VIP از پشتیبانی اولویت‌دار و سریع‌تر بهره‌مند می‌شوند.',
                'order' => 2,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category5Id,
                'question' => 'چطور می‌توانم مشکل فنی را گزارش دهم؟',
                'answer' => 'برای گزارش مشکل فنی، وارد پنل کاربری خود شوید و به بخش "پشتیبانی" بروید. سپس تیکت جدید ایجاد کرده و مشکل خود را به تفصیل شرح دهید. تیم فنی ما در اسرع وقت به شما پاسخ خواهد داد.',
                'order' => 3,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // دسته‌بندی 6: برنامه‌نویسی و تکنولوژی
        $category6Id = DB::table('faq_categories')->insertGetId([
            'title' => 'برنامه‌نویسی و تکنولوژی',
            'english_title' => 'programming-and-technology',
            'slug' => 'programming-and-technology',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M4 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H4Zm5.7 6.3a1 1 0 0 1 0 1.4L8.42 12l1.3 1.3a1 1 0 1 1-1.42 1.4l-2-2a1 1 0 0 1 0-1.4l2-2a1 1 0 0 1 1.42 0Zm4.6 0a1 1 0 0 1 1.4 0l2 2a1 1 0 0 1 0 1.4l-2 2a1 1 0 0 1-1.4-1.4l1.28-1.3-1.29-1.3a1 1 0 0 1 0-1.4Z"/></svg>',
            'order' => 6,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $faqs6 = [
            [
                'category_id' => $category6Id,
                'question' => 'برای شروع برنامه‌نویسی از کجا باید شروع کنم؟',
                'answer' => 'برای شروع برنامه‌نویسی، ابتدا یک زبان برنامه‌نویسی ساده مانند Python یا JavaScript را انتخاب کنید. سپس از دوره‌های مقدماتی ما شروع کنید و به تدریج به دوره‌های پیشرفته‌تر بروید. مسیر یادگیری "برنامه‌نویسی از صفر" برای شما مناسب است.',
                'order' => 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category6Id,
                'question' => 'آیا دوره‌های شما برای افراد مبتدی مناسب است؟',
                'answer' => 'بله، ما دوره‌هایی برای تمام سطوح داریم. دوره‌های سطح "مقدماتی" برای افرادی که هیچ تجربه برنامه‌نویسی ندارند طراحی شده‌اند و از صفر شروع می‌کنند.',
                'order' => 2,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category6Id,
                'question' => 'چطور می‌توانم مهارت‌های خود را تمرین کنم؟',
                'answer' => 'در هر دوره، پروژه‌های عملی و تمرین‌های متنوعی وجود دارد. همچنین می‌توانید در بخش "پرسش و پاسخ" سوالات خود را مطرح کنید و با سایر دانشجویان و مدرسین در ارتباط باشید.',
                'order' => 3,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $category6Id,
                'question' => 'آیا دوره‌ها به‌روز می‌شوند؟',
                'answer' => 'بله، ما به طور منظم دوره‌ها را به‌روزرسانی می‌کنیم تا با آخرین تغییرات تکنولوژی هماهنگ باشند. تمام به‌روزرسانی‌ها برای خریداران دوره رایگان است.',
                'order' => 4,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert all FAQs
        DB::table('faqs')->insert($faqs1);
        DB::table('faqs')->insert($faqs2);
        DB::table('faqs')->insert($faqs3);
        DB::table('faqs')->insert($faqs4);
        DB::table('faqs')->insert($faqs5);
        DB::table('faqs')->insert($faqs6);
    }
}

