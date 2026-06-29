<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticlesTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->limit(5)->get();
        if ($users->isEmpty()) {
            return;
        }

        $categories = [
            ['title' => 'برنامه‌نویسی', 'english_title' => 'Programming', 'slug' => 'programming', 'order' => 1],
            ['title' => 'فرانت‌اند', 'english_title' => 'Frontend', 'slug' => 'frontend', 'order' => 2],
            ['title' => 'بک‌اند', 'english_title' => 'Backend', 'slug' => 'backend', 'order' => 3],
            ['title' => 'طراحی وب', 'english_title' => 'Web Design', 'slug' => 'web-design', 'order' => 4],
            ['title' => 'UI و UX', 'english_title' => 'UI UX', 'slug' => 'ui-ux', 'order' => 5],
            ['title' => 'DevOps', 'english_title' => 'DevOps', 'slug' => 'devops', 'order' => 6],
            ['title' => 'لینوکس و سرور', 'english_title' => 'Linux Server', 'slug' => 'linux-server', 'order' => 7],
            ['title' => 'پایگاه داده', 'english_title' => 'Database', 'slug' => 'database', 'order' => 8],
            ['title' => 'امنیت', 'english_title' => 'Security', 'slug' => 'security', 'order' => 9],
            ['title' => 'موبایل', 'english_title' => 'Mobile', 'slug' => 'mobile', 'order' => 10],
            ['title' => 'هوش مصنوعی', 'english_title' => 'Artificial Intelligence', 'slug' => 'artificial-intelligence', 'order' => 11],
            ['title' => 'مسیر شغلی', 'english_title' => 'Career', 'slug' => 'career', 'order' => 12],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat['slug']] = ArticleCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                $cat + ['status' => true]
            );
        }

        $samples = $this->articles();

        Article::withoutSyncingToSearch(function () use ($samples, $categoryMap, $users) {
            foreach ($samples as $index => $sample) {
                $category = $categoryMap[$sample['category_slug']] ?? reset($categoryMap);
                $user = $users[$index % $users->count()];
                $tags = $sample['tags'] ?? [];
                unset($sample['tags'], $sample['category_slug']);

                $slug = Str::slug($sample['english_title']);

                $article = Article::firstOrCreate(
                    ['slug' => $slug],
                    array_merge($sample, [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'publish' => true,
                        'status' => 'published',
                        'published_at' => now()->subDays(rand(1, 180)),
                    ])
                );

                if ($tags) {
                    $article->tag($tags);
                }
            }
        });
    }

    private function articles(): array
    {
        return [
            // Programming (5)
            ['category_slug' => 'programming', 'english_title' => 'Clean Code Principles for PHP Developers', 'title' => 'اصول Clean Code برای توسعه‌دهندگان PHP', 'excerpt' => 'نام‌گذاری، توابع کوچک و اصل مسئولیت واحد در پروژه‌های واقعی.', 'content' => "## Clean Code\n\nکد تمیز خوانایی، نگهداری و همکاری تیمی را ساده‌تر می‌کند.\n\n- نام‌گذاری معنادار\n- توابع کوتاه\n- اجتناب از side effect", 'is_featured' => true, 'tags' => ['php', 'clean-code']],
            ['category_slug' => 'programming', 'english_title' => 'SOLID Principles Explained Simply', 'title' => 'اصول SOLID به زبان ساده', 'excerpt' => 'پنج اصل طراحی شی‌گرا که معماری نرم‌افزار را مقاوم‌تر می‌کند.', 'content' => "## SOLID\n\nهر حرف یک اصل است: SRP، OCP، LSP، ISP، DIP.\n\nدر Laravel با interface و dependency injection عملی می‌شوند.", 'is_featured' => false, 'tags' => ['solid', 'architecture']],
            ['category_slug' => 'programming', 'english_title' => 'Git Workflow for Teams', 'title' => 'گردش کار Git برای تیم‌ها', 'excerpt' => 'branch، merge request و release branch در پروژه‌های تیمی.', 'content' => "## Git Flow\n\n`main` پایدار، `develop` برای ادغام، feature branch برای هر تسک.\n\n```bash\ngit checkout -b feature/article-seeder\n```", 'is_featured' => false, 'tags' => ['git', 'workflow']],
            ['category_slug' => 'programming', 'english_title' => 'Refactoring Legacy PHP Code', 'title' => 'بازآرایی کد Legacy در PHP', 'excerpt' => 'چطور بدون شکستن production، کد قدیمی را مرحله‌ای بهبود دهیم.', 'content' => "## Refactoring\n\n1. تست بنویسید\n2. تغییرات کوچک\n3. deploy مکرر\n\nاز strangler pattern برای ماژول‌های بزرگ استفاده کنید.", 'is_featured' => false, 'tags' => ['php', 'refactoring']],
            ['category_slug' => 'programming', 'english_title' => 'Design Patterns in Laravel', 'title' => 'الگوهای طراحی در Laravel', 'excerpt' => 'Repository، Strategy و Observer در پروژه‌های Laravel.', 'content' => "## Patterns\n\nLaravel خودش Facade، Factory و Observer دارد.\n\nService container جایگزین خوبی برای wiring دستی است.", 'is_featured' => true, 'tags' => ['laravel', 'patterns']],

            // Frontend (5)
            ['category_slug' => 'frontend', 'english_title' => 'Vue 3 Composition API Guide', 'title' => 'راهنمای Composition API در Vue 3', 'excerpt' => 'از setup تا composables — شروع سریع و عملی.', 'content' => "## Vue 3\n\nComposition API انعطاف بیشتری برای logic reuse می‌دهد.\n\n- ref و reactive\n- computed\n- watchEffect", 'is_featured' => true, 'tags' => ['vue', 'javascript']],
            ['category_slug' => 'frontend', 'english_title' => 'Tailwind CSS Best Practices', 'title' => 'بهترین روش‌های Tailwind CSS', 'excerpt' => 'سازماندهی کلاس‌ها، dark mode و component extraction.', 'content' => "## Tailwind\n\nاز `@apply` کم استفاده کنید؛ component-based بهتر است.\n\n`dark:` variant برای تم تاریک.", 'is_featured' => false, 'tags' => ['tailwind', 'css']],
            ['category_slug' => 'frontend', 'english_title' => 'Core Web Vitals Explained', 'title' => 'Core Web Vitals چیست و چرا مهم است؟', 'excerpt' => 'LCP، INP و CLS و تأثیر آن‌ها بر SEO.', 'content' => "## Web Vitals\n\n### LCP\nزمان رندر بزرگ‌ترین المان.\n\n### CLS\nپایداری layout.", 'is_featured' => true, 'tags' => ['performance', 'seo']],
            ['category_slug' => 'frontend', 'english_title' => 'State Management with Pinia', 'title' => 'مدیریت state با Pinia', 'excerpt' => 'جایگزین Vuex برای storeهای ساده و type-safe.', 'content' => "## Pinia\n\nStore تعریف کنید، در component با `useStore` مصرف کنید.\n\nDevTools پشتیبانی کامل دارد.", 'is_featured' => false, 'tags' => ['vue', 'pinia']],
            ['category_slug' => 'frontend', 'english_title' => 'Accessible Forms in HTML', 'title' => 'فرم‌های accessible در HTML', 'excerpt' => 'label، aria و keyboard navigation برای فرم‌های inclusive.', 'content' => "## Accessibility\n\nهر input باید label داشته باشد.\n\nخطاها با `aria-describedby` به screen reader برسند.", 'is_featured' => false, 'tags' => ['a11y', 'html']],

            // Backend (4)
            ['category_slug' => 'backend', 'english_title' => 'REST API Design Best Practices', 'title' => 'بهترین روش‌های طراحی REST API', 'excerpt' => 'resource naming، status code و pagination استاندارد.', 'content' => "## REST\n\nاز فعل در URL پرهیز کنید.\n\n`GET /articles` لیست، `POST /articles` ایجاد.", 'is_featured' => true, 'tags' => ['api', 'rest']],
            ['category_slug' => 'backend', 'english_title' => 'Laravel Queue and Jobs', 'title' => 'صف و Job در Laravel', 'excerpt' => 'کارهای سنگین را async با Redis و Horizon اجرا کنید.', 'content' => "## Queues\n\n`php artisan queue:work`\n\nFailed jobs را monitor کنید.", 'is_featured' => false, 'tags' => ['laravel', 'queue']],
            ['category_slug' => 'backend', 'english_title' => 'Caching Strategies in Web Apps', 'title' => 'استراتژی‌های cache در وب', 'excerpt' => 'Redis، cache invalidation و stale-while-revalidate.', 'content' => "## Caching\n\nCache-aside رایج‌ترین الگو است.\n\nTTL را بر اساس نرخ تغییر داده تنظیم کنید.", 'is_featured' => false, 'tags' => ['cache', 'redis']],
            ['category_slug' => 'backend', 'english_title' => 'Authentication JWT vs Session', 'title' => 'JWT در برابر Session برای احراز هویت', 'excerpt' => 'مزایا و معایب هر رویکرد در SPA و API.', 'content' => "## Auth\n\nSession برای same-site ساده‌تر است.\n\nJWT برای mobile و microservice مناسب‌تر است.", 'is_featured' => false, 'tags' => ['auth', 'jwt']],

            // Web Design (4)
            ['category_slug' => 'web-design', 'english_title' => 'Responsive Grid Layout Guide', 'title' => 'راهنمای layout شبکه‌ای responsive', 'excerpt' => 'CSS Grid و Flexbox در کنار هم برای layout مدرن.', 'content' => "## Layout\n\nGrid برای macro layout، Flex برای component داخلی.", 'is_featured' => false, 'tags' => ['css', 'layout']],
            ['category_slug' => 'web-design', 'english_title' => 'Typography for Persian Websites', 'title' => 'تایپوگرافی برای سایت‌های فارسی', 'excerpt' => 'فونت، line-height و readability در RTL.', 'content' => "## Typography\n\nفونت variable weight انتخاب کنید.\n\n`line-height` بین 1.6 تا 1.8 برای پارagraph.", 'is_featured' => true, 'tags' => ['typography', 'rtl']],
            ['category_slug' => 'web-design', 'english_title' => 'Color Theory for Developers', 'title' => 'تیوری رنگ برای توسعه‌دهندگان', 'excerpt' => 'پالت، contrast ratio و semantic color tokens.', 'content' => "## Colors\n\nPrimary، secondary، surface و error را جدا define کنید.", 'is_featured' => false, 'tags' => ['design', 'colors']],
            ['category_slug' => 'web-design', 'english_title' => 'Landing Page Structure', 'title' => 'ساختار یک landing page مؤثر', 'excerpt' => 'hero، social proof، CTA و FAQ.', 'content' => "## Landing\n\nیک CTA اصلی در fold اول.\n\nTrust signal قبل از فرم ثبت‌نام.", 'is_featured' => false, 'tags' => ['landing', 'marketing']],

            // UI/UX (4)
            ['category_slug' => 'ui-ux', 'english_title' => 'UX Research Basics', 'title' => 'مبانی تحقیق UX', 'excerpt' => 'مصاحبه کاربر، usability test و synthesis یافته‌ها.', 'content' => "## UX Research\n\n۵ کاربر کافی است برای کشف مشکلات بزرگ.\n\nاز leading question پرهیز کنید.", 'is_featured' => false, 'tags' => ['ux', 'research']],
            ['category_slug' => 'ui-ux', 'english_title' => 'Design System Components', 'title' => 'کامپوننت‌های design system', 'excerpt' => 'Button، Input و Card با variant و state یکسان.', 'content' => "## Design System\n\nToken → Component → Pattern → Page", 'is_featured' => true, 'tags' => ['design-system', 'ui']],
            ['category_slug' => 'ui-ux', 'english_title' => 'Microinteractions That Matter', 'title' => 'میکرواینتراکشن‌های مؤثر', 'excerpt' => 'feedback بصری کوچک که UX را human می‌کند.', 'content' => "## Microinteractions\n\nHover، loading و success state را جدی بگیرید.", 'is_featured' => false, 'tags' => ['ux', 'animation']],
            ['category_slug' => 'ui-ux', 'english_title' => 'Empty States and Error UX', 'title' => 'Empty state و UX خطا', 'excerpt' => 'وقتی داده نیست یا خطا رخ می‌دهد، کاربر را رها نکنید.', 'content' => "## Empty States\n\nعلت + action بعدی.\n\n404 باید راه برگشت داشته باشد.", 'is_featured' => false, 'tags' => ['ux', 'errors']],

            // DevOps (4)
            ['category_slug' => 'devops', 'english_title' => 'Docker for Beginners', 'title' => 'Docker برای مبتدیان', 'excerpt' => 'Image، container و docker-compose در کمتر از ۱۰ دقیقه.', 'content' => "## Docker\n\n> Tip: از `.dockerignore` استفاده کنید.\n\nMulti-stage build برای image کوچک‌تر.", 'is_featured' => true, 'tags' => ['docker', 'devops']],
            ['category_slug' => 'devops', 'english_title' => 'CI CD Pipeline with GitHub Actions', 'title' => 'Pipeline CI/CD با GitHub Actions', 'excerpt' => 'test، lint و deploy خودکار روی push.', 'content' => "## CI/CD\n\n```yaml\non: [push]\njobs:\n  test:\n    runs-on: ubuntu-latest\n```", 'is_featured' => false, 'tags' => ['ci-cd', 'github']],
            ['category_slug' => 'devops', 'english_title' => 'Kubernetes Concepts Overview', 'title' => 'مرور مفاهیم Kubernetes', 'excerpt' => 'Pod، Service، Deployment و Ingress.', 'content' => "## K8s\n\nDeployment replica را manage می‌کند.\n\nService ترافیک را load balance می‌کند.", 'is_featured' => false, 'tags' => ['kubernetes', 'devops']],
            ['category_slug' => 'devops', 'english_title' => 'Monitoring and Logging Stack', 'title' => 'پشته monitoring و logging', 'excerpt' => 'metrics، logs و alerting برای production.', 'content' => "## Observability\n\nMetrics برای trend، logs برای debug، traces برای latency.", 'is_featured' => false, 'tags' => ['monitoring', 'logging']],

            // Linux & Server (4)
            ['category_slug' => 'linux-server', 'english_title' => 'Nginx Reverse Proxy Setup', 'title' => 'راه‌اندازی Nginx به‌عنوان reverse proxy', 'excerpt' => 'SSL termination، upstream و cache static.', 'content' => "## Nginx\n\n`proxy_pass` به PHP-FPM یا Node.\n\nLet's Encrypt برای HTTPS رایگان.", 'is_featured' => false, 'tags' => ['nginx', 'linux']],
            ['category_slug' => 'linux-server', 'english_title' => 'Linux File Permissions Explained', 'title' => 'مجوزهای فایل در لینوکس', 'excerpt' => 'chmod، chown و umask برای admin امن.', 'content' => "## Permissions\n\n755 برای directory، 644 برای فایل معمولی.", 'is_featured' => false, 'tags' => ['linux', 'security']],
            ['category_slug' => 'linux-server', 'english_title' => 'SSH Hardening Checklist', 'title' => 'چک‌لیست سخت‌سازی SSH', 'excerpt' => 'key-only login، fail2ban و غیرفعال کردن root.', 'content' => "## SSH\n\nPasswordAuthentication no\nPermitRootLogin no", 'is_featured' => true, 'tags' => ['ssh', 'security']],
            ['category_slug' => 'linux-server', 'english_title' => 'Cron Jobs and Task Scheduling', 'title' => 'Cron job و زمان‌بندی task', 'excerpt' => 'crontab در Linux و scheduler در Laravel.', 'content' => "## Scheduling\n\n`php artisan schedule:run` هر دقیقه.\n\nTimezone را explicit تنظیم کنید.", 'is_featured' => false, 'tags' => ['cron', 'linux']],

            // Database (4)
            ['category_slug' => 'database', 'english_title' => 'PostgreSQL Indexing Guide', 'title' => 'راهنمای index در PostgreSQL', 'excerpt' => 'B-tree، composite index و EXPLAIN ANALYZE.', 'content' => "## Indexing\n\nIndex روی columnهای filter و join.\n\nOver-indexing write را کند می‌کند.", 'is_featured' => true, 'tags' => ['postgresql', 'database']],
            ['category_slug' => 'database', 'english_title' => 'Database Normalization Basics', 'title' => 'مبانی نرمال‌سازی پایگاه داده', 'excerpt' => '1NF تا 3NF و وقتی denormalize کنیم.', 'content' => "## Normalization\n\n redundancy کمتر، integrity بیشتر.\n\nReporting گاهی denormalize می‌خواهد.", 'is_featured' => false, 'tags' => ['database', 'sql']],
            ['category_slug' => 'database', 'english_title' => 'Redis Use Cases', 'title' => 'کاربردهای Redis', 'excerpt' => 'cache، session، queue و rate limiting.', 'content' => "## Redis\n\nIn-memory و سریع.\n\nPersistence با RDB/AOF.", 'is_featured' => false, 'tags' => ['redis', 'database']],
            ['category_slug' => 'database', 'english_title' => 'Migration Best Practices Laravel', 'title' => 'بهترین روش migration در Laravel', 'excerpt' => 'backward compatible migration و zero-downtime deploy.', 'content' => "## Migrations\n\nستون nullable اضافه کنید، بعد backfill، بعد NOT NULL.", 'is_featured' => false, 'tags' => ['laravel', 'migration']],

            // Security (4)
            ['category_slug' => 'security', 'english_title' => 'OWASP Top 10 Overview', 'title' => 'مرور OWASP Top 10', 'excerpt' => 'رایج‌ترین آسیب‌پذیری‌های وب و پیشگیری.', 'content' => "## OWASP\n\nInjection، broken auth، XSS در صدر لیست.\n\nInput validation و output encoding.", 'is_featured' => true, 'tags' => ['security', 'owasp']],
            ['category_slug' => 'security', 'english_title' => 'XSS Prevention in Vue Apps', 'title' => 'پیشگیری از XSS در Vue', 'excerpt' => 'v-html، CSP و sanitize user content.', 'content' => "## XSS\n\nاز v-html فقط برای trusted content.\n\nDOMPurify برای sanitize.", 'is_featured' => false, 'tags' => ['xss', 'vue']],
            ['category_slug' => 'security', 'english_title' => 'CSRF and SameSite Cookies', 'title' => 'CSRF و کوکی SameSite', 'excerpt' => 'token، double submit cookie و SameSite=Lax.', 'content' => "## CSRF\n\nLaravel `@csrf` و Sanctum SPA guard.", 'is_featured' => false, 'tags' => ['csrf', 'security']],
            ['category_slug' => 'security', 'english_title' => 'Secrets Management in Production', 'title' => 'مدیریت secret در production', 'excerpt' => '.env، vault و rotate credential.', 'content' => "## Secrets\n\nهرگز secret در git commit نکنید.\n\nRotate بعد از leak.", 'is_featured' => false, 'tags' => ['secrets', 'devops']],

            // Mobile (4)
            ['category_slug' => 'mobile', 'english_title' => 'React Native Getting Started', 'title' => 'شروع کار با React Native', 'excerpt' => 'Expo، component و navigation اولیه.', 'content' => "## React Native\n\nیک codebase برای iOS و Android.\n\nNative module برای performance.", 'is_featured' => false, 'tags' => ['react-native', 'mobile']],
            ['category_slug' => 'mobile', 'english_title' => 'Flutter vs React Native', 'title' => 'Flutter در برابر React Native', 'excerpt' => 'مقایسه ecosystem، performance و learning curve.', 'content' => "## Comparison\n\nFlutter: Dart، widget tree یکپارچه.\n\nRN: JavaScript، ecosystem وب.", 'is_featured' => true, 'tags' => ['flutter', 'react-native']],
            ['category_slug' => 'mobile', 'english_title' => 'Mobile App Performance Tips', 'title' => 'نکات performance اپ موبایل', 'excerpt' => 'lazy load، image optimization و memory leak.', 'content' => "## Performance\n\nList virtualization ضروری است.\n\nProfiler را جدی بگیرید.", 'is_featured' => false, 'tags' => ['mobile', 'performance']],
            ['category_slug' => 'mobile', 'english_title' => 'Push Notifications Architecture', 'title' => 'معماری push notification', 'excerpt' => 'FCM، APNs و backend event flow.', 'content' => "## Push\n\nToken per device.\n\nTopic برای broadcast.", 'is_featured' => false, 'tags' => ['push', 'mobile']],

            // AI (4)
            ['category_slug' => 'artificial-intelligence', 'english_title' => 'LLM Prompt Engineering Basics', 'title' => 'مبانی prompt engineering برای LLM', 'excerpt' => 'system prompt، few-shot و chain-of-thought.', 'content' => "## Prompts\n\nContext و format output را explicit بگویید.\n\nTemperature برای creativity.", 'is_featured' => true, 'tags' => ['ai', 'llm']],
            ['category_slug' => 'artificial-intelligence', 'english_title' => 'RAG Architecture Explained', 'title' => 'معماری RAG توضیح داده شد', 'excerpt' => 'retrieval، embedding و augmented generation.', 'content' => "## RAG\n\nVector DB + chunking + rerank.\n\nSource citation برای trust.", 'is_featured' => false, 'tags' => ['rag', 'ai']],
            ['category_slug' => 'artificial-intelligence', 'english_title' => 'Machine Learning for Developers', 'title' => 'یادگیری ماشین برای توسعه‌دهندگان', 'excerpt' => 'supervised vs unsupervised و pipeline ساده.', 'content' => "## ML\n\nData → feature → train → evaluate → deploy.", 'is_featured' => false, 'tags' => ['ml', 'python']],
            ['category_slug' => 'artificial-intelligence', 'english_title' => 'AI Ethics in Product Design', 'title' => 'اخلاق AI در طراحی محصول', 'excerpt' => 'bias، transparency و human-in-the-loop.', 'content' => "## Ethics\n\nDisclaimer برای AI-generated content.\n\nAudit trail برای decision.", 'is_featured' => false, 'tags' => ['ai', 'ethics']],

            // Career (4)
            ['category_slug' => 'career', 'english_title' => 'Junior to Mid Developer Roadmap', 'title' => 'نقشه راه junior تا mid developer', 'excerpt' => 'مهارت‌های فنی و soft skill برای رشد شغلی.', 'content' => "## Roadmap\n\nFundamentals → framework → system design basics.\n\nCode review بخوانید و بنویسید.", 'is_featured' => true, 'tags' => ['career', 'roadmap']],
            ['category_slug' => 'career', 'english_title' => 'Technical Interview Preparation', 'title' => 'آمادگی برای مصاحبه فنی', 'excerpt' => 'algorithm، system design و behavioral.', 'content' => "## Interview\n\nSTAR method برای behavioral.\n\nWhiteboard با voice thinking.", 'is_featured' => false, 'tags' => ['interview', 'career']],
            ['category_slug' => 'career', 'english_title' => 'Remote Work Productivity', 'title' => 'بهره‌وری در کار remote', 'excerpt' => 'async communication، deep work و boundaries.', 'content' => "## Remote\n\nCalendar block برای focus.\n\nDocumentation over meetings.", 'is_featured' => false, 'tags' => ['remote', 'productivity']],
            ['category_slug' => 'career', 'english_title' => 'Building Developer Portfolio', 'title' => 'ساخت portfolio توسعه‌دهنده', 'excerpt' => 'پروژه‌های showcase، README و case study.', 'content' => "## Portfolio\n\n۳ پروژه عمیق بهتر از ۱۰ shallow.\n\nProblem → solution → impact.", 'is_featured' => false, 'tags' => ['portfolio', 'career']],
        ];
    }
}
