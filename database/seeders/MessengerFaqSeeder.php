<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent seeder: adds/updates the "Messenger" FAQ category and its questions.
 * Safe to run on existing databases: php artisan db:seed --class=MessengerFaqSeeder
 */
class MessengerFaqSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $icon = '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>';

        $existing = DB::table('faq_categories')->where('slug', 'messenger')->first();
        if ($existing) {
            $categoryId = $existing->id;
            DB::table('faq_categories')->where('id', $categoryId)->update([
                'title' => 'پیام‌رسان',
                'english_title' => 'messenger',
                'icon' => $icon,
                'status' => true,
                'updated_at' => $now,
            ]);
        } else {
            $maxOrder = (int) DB::table('faq_categories')->max('order');
            $categoryId = DB::table('faq_categories')->insertGetId([
                'title' => 'پیام‌رسان',
                'english_title' => 'messenger',
                'slug' => 'messenger',
                'icon' => $icon,
                'order' => $maxOrder + 1,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $faqs = [
            [
                'order' => 1,
                'question' => 'چطور پروفایل خودم را در پیام‌رسان ویرایش کنم؟',
                'answer' => 'در پیام‌رسان به منو → تنظیمات → حساب کاربری بروید. از آنجا می‌توانید نام، نام کاربری، بیوگرافی و عکس پروفایل را تغییر دهید.',
            ],
            [
                'order' => 2,
                'question' => 'چطور تم روشن یا تاریک پیام‌رسان را عوض کنم؟',
                'answer' => 'به تنظیمات → ظاهر بروید و یکی از گزینه‌های روشن، تیره یا سیستم را انتخاب کنید. با انتخاب «سیستم»، تم به‌صورت خودکار با تنظیمات دستگاه هماهنگ می‌شود.',
            ],
            [
                'order' => 3,
                'question' => 'پس‌زمینه گفتگو را چطور تغییر دهم؟',
                'answer' => 'از تنظیمات → ظاهر → پس‌زمینه گفتگو می‌توانید طرح، عکس یا حالت نئون را انتخاب کنید و میزان محو شدن و تیرگی را تنظیم کنید. همچنین می‌توانید تصویر دلخواه آپلود کنید.',
            ],
            [
                'order' => 4,
                'question' => 'اعلان‌های پیام‌رسان را چطور مدیریت کنم؟',
                'answer' => 'در تنظیمات → اعلان‌ها می‌توانید صدا، پیش‌نمایش متن پیام و نشان اعلان را روشن یا خاموش کنید.',
            ],
            [
                'order' => 5,
                'question' => 'حریم خصوصی (آخرین بازدید، عکس پروفایل و …) را چطور تنظیم کنم؟',
                'answer' => 'به تنظیمات → حریم خصوصی و امنیت بروید. برای هر مورد می‌توانید انتخاب کنید همه، مخاطبین یا هیچ‌کس آن را ببینند و در صورت نیاز استثنای «همیشه مجاز» یا «هرگز مجاز نیست» اضافه کنید.',
            ],
            [
                'order' => 6,
                'question' => 'چطور مخاطب به پیام‌رسان اضافه کنم؟',
                'answer' => 'از منو → مخاطبین، دکمه افزودن مخاطب را بزنید و نام کاربری یا شماره را وارد کنید. همچنین می‌توانید مخاطبین دستگاه را همگام‌سازی کنید.',
            ],
        ];

        foreach ($faqs as $faq) {
            $row = DB::table('faqs')
                ->where('category_id', $categoryId)
                ->where('order', $faq['order'])
                ->first();

            $payload = [
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'status' => true,
                'updated_at' => $now,
            ];

            if ($row) {
                DB::table('faqs')->where('id', $row->id)->update($payload);
            } else {
                DB::table('faqs')->insert(array_merge($payload, [
                    'category_id' => $categoryId,
                    'order' => $faq['order'],
                    'created_at' => $now,
                ]));
            }
        }
    }
}
