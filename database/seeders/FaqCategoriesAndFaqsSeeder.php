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
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 6.253V13.5L15.5 15.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
        ];

        // دسته‌بندی 3: پرداخت و اشتراک VIP
        $category3Id = DB::table('faq_categories')->insertGetId([
            'title' => 'پرداخت و اشتراک VIP',
            'english_title' => 'payment-and-vip',
            'slug' => 'payment-and-vip',
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 4H3C1.89543 4 1 4.89543 1 6V18C1 19.1046 1.89543 20 3 20H21C22.1046 20 23 19.1046 23 18V6C23 4.89543 22.1046 4 21 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 10H23" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.09 9C9.3251 8.33167 9.78915 7.76811 10.4 7.40913C11.0108 7.05016 11.7289 6.91894 12.4272 7.03871C13.1255 7.15849 13.7588 7.52152 14.2151 8.06353C14.6713 8.60553 14.9211 9.29152 14.92 10C14.92 12 11.92 13 11.92 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
            'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 2H21V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 2L12 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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

