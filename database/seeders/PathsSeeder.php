<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PathsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $paths = [
            [
                'title' => 'مسیر کامل توسعه وب',
                'english_title' => 'full-stack-web-development',
                'slug' => 'full-stack-web-development',
                'description' => 'یادگیری کامل توسعه وب از صفر تا صد شامل فرانت‌اند و بک‌اند',
                'short_description' => 'مسیر جامع برای تبدیل شدن به یک Full Stack Developer',
                'meta_keywords' => 'full stack, web development, frontend, backend',
                'icon' => 'https://static.zanburak.ir/poster/no-image.png',
                'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                'trailer' => null,
                'faqs' => json_encode([
                    [
                        'question' => 'این مسیر برای چه کسانی مناسب است؟',
                        'answer' => 'برای افرادی که می‌خواهند از صفر شروع کنند و به یک توسعه‌دهنده وب کامل تبدیل شوند.'
                    ],
                    [
                        'question' => 'چقدر زمان نیاز است؟',
                        'answer' => 'حدود 6 تا 12 ماه بسته به سرعت یادگیری شما'
                    ]
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مسیر توسعه React',
                'english_title' => 'react-development-path',
                'slug' => 'react-development-path',
                'description' => 'یادگیری کامل React از مبانی تا پیشرفته شامل Hooks، Context API و Next.js',
                'short_description' => 'تبدیل شدن به یک React Developer حرفه‌ای',
                'meta_keywords' => 'react, javascript, frontend, ui development',
                'icon' => 'https://static.zanburak.ir/poster/no-image.png',
                'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                'trailer' => null,
                'faqs' => json_encode([
                    [
                        'question' => 'پیش‌نیاز این مسیر چیست؟',
                        'answer' => 'آشنایی با HTML, CSS و JavaScript پایه'
                    ]
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مسیر Laravel Developer',
                'english_title' => 'laravel-developer-path',
                'slug' => 'laravel-developer-path',
                'description' => 'یادگیری Laravel از مبتدی تا حرفه‌ای شامل MVC، Eloquent، API و Testing',
                'short_description' => 'تبدیل شدن به یک Laravel Developer متخصص',
                'meta_keywords' => 'laravel, php, backend, api development',
                'icon' => 'https://static.zanburak.ir/poster/no-image.png',
                'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                'trailer' => null,
                'faqs' => json_encode([
                    [
                        'question' => 'آیا نیاز به دانش PHP دارم؟',
                        'answer' => 'بله، آشنایی با PHP پایه ضروری است'
                    ]
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مسیر توسعه موبایل با React Native',
                'english_title' => 'react-native-mobile-path',
                'slug' => 'react-native-mobile-path',
                'description' => 'یادگیری توسعه اپلیکیشن موبایل با React Native برای iOS و Android',
                'short_description' => 'ساخت اپلیکیشن موبایل با یک کد برای دو پلتفرم',
                'meta_keywords' => 'react native, mobile development, ios, android',
                'icon' => 'https://static.zanburak.ir/poster/no-image.png',
                'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                'trailer' => null,
                'faqs' => json_encode([
                    [
                        'question' => 'آیا نیاز به Mac دارم؟',
                        'answer' => 'برای توسعه iOS بله، اما برای Android خیر'
                    ]
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مسیر Python و علوم داده',
                'english_title' => 'python-data-science-path',
                'slug' => 'python-data-science-path',
                'description' => 'یادگیری Python برای علوم داده، تحلیل داده و ماشین لرنینگ',
                'short_description' => 'ورود به دنیای علوم داده و هوش مصنوعی',
                'meta_keywords' => 'python, data science, machine learning, ai',
                'icon' => 'https://static.zanburak.ir/poster/no-image.png',
                'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                'trailer' => null,
                'faqs' => json_encode([
                    [
                        'question' => 'پیش‌نیاز ریاضی چقدر است؟',
                        'answer' => 'آشنایی با ریاضی پایه و آمار مفید است اما ضروری نیست'
                    ]
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'مسیر DevOps و Deployment',
                'english_title' => 'devops-deployment-path',
                'slug' => 'devops-deployment-path',
                'description' => 'یادگیری DevOps شامل Docker، Kubernetes، CI/CD و Cloud Services',
                'short_description' => 'تبدیل شدن به یک DevOps Engineer',
                'meta_keywords' => 'devops, docker, kubernetes, ci/cd, cloud',
                'icon' => 'https://static.zanburak.ir/poster/no-image.png',
                'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                'trailer' => null,
                'faqs' => json_encode([
                    [
                        'question' => 'این مسیر برای چه کسانی مناسب است؟',
                        'answer' => 'برای توسعه‌دهندگانی که می‌خواهند مهارت‌های DevOps را یاد بگیرند'
                    ]
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('paths')->upsert($paths, ['slug'], ['title', 'english_title', 'description', 'short_description', 'meta_keywords', 'icon', 'poster', 'trailer', 'faqs', 'status', 'updated_at']);
    }
}

