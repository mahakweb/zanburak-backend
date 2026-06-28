<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Question;
use App\Models\Tag;
use Cviebrock\EloquentTaggable\Services\TagService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds ~100 popular discussion/course tags for the Zanburak tag ecosystem.
 */
class TagsTableSeeder extends Seeder
{
    public function run(): void
    {
        $tagService = app(TagService::class);
        $created = 0;
        $skipped = 0;

        foreach ($this->tagNames() as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $existing = $tagService->find($name);
            if ($existing) {
                $skipped++;
                continue;
            }

            $tagService->findOrCreate($name);
            $created++;
        }

        $this->assignTagsToContent();
        $this->syncPostgresSequence();

        $this->command?->info("TagsTableSeeder: {$created} tags created, {$skipped} already existed.");
    }

    /**
     * @return list<string>
     */
    private function tagNames(): array
    {
        return [
            // زبان‌ها و فریم‌ورک‌های بک‌اند
            'PHP',
            'لاراول',
            'Laravel',
            'Symfony',
            'WordPress',
            'وردپرس',
            'Node.js',
            'Express',
            'NestJS',
            'Python',
            'Django',
            'FastAPI',
            'Flask',
            'Java',
            'Spring Boot',
            'Kotlin',
            'Go',
            'Rust',
            'C#',
            '.NET',
            'Ruby',
            'Rails',

            // فرانت‌اند و UI
            'JavaScript',
            'TypeScript',
            'Vue',
            'Vue.js',
            'React',
            'Next.js',
            'Nuxt',
            'Angular',
            'Svelte',
            'Alpine.js',
            'jQuery',
            'HTML',
            'CSS',
            'Sass',
            'Tailwind',
            'Bootstrap',
            'طراحی-وب',
            'UI/UX',
            'Figma',
            'Responsive Design',

            // موبایل
            'React Native',
            'Flutter',
            'Dart',
            'Android',
            'iOS',
            'Swift',
            'Kotlin Android',

            // دیتابیس و داده
            'MySQL',
            'PostgreSQL',
            'MongoDB',
            'Redis',
            'SQLite',
            'Elasticsearch',
            'Database Design',
            'Eloquent',
            'ORM',

            // API و معماری
            'REST API',
            'API',
            'GraphQL',
            'gRPC',
            'Microservices',
            'Serverless',
            'WebSocket',
            'JWT',
            'OAuth',
            'احراز-هویت',
            'معماری-نرم‌افزار',
            'الگوی-طراحی',
            'SOLID',
            'Clean Code',
            'MVC',

            // DevOps و ابزار
            'Git',
            'GitHub',
            'GitLab',
            'Docker',
            'Kubernetes',
            'Linux',
            'DevOps',
            'CI/CD',
            'Nginx',
            'AWS',
            'Azure',
            'GCP',
            'Cloudflare',
            'Composer',
            'NPM',
            'Vite',
            'Webpack',

            // Laravel ecosystem
            'Livewire',
            'Inertia',
            'Filament',
            'Laravel Sanctum',
            'Laravel Horizon',
            'Laravel Telescope',
            'Pest',
            'PHPUnit',
            'Spatie',

            // تست و کیفیت
            'Testing',
            'TDD',
            'QA',
            'دیباگ',
            'Code Review',
            'Static Analysis',

            // امنیت
            'امنیت-وب',
            'XSS',
            'CSRF',
            'Cybersecurity',
            'Penetration Testing',

            // هوش مصنوعی و داده
            'Machine Learning',
            'هوش-مصنوعی',
            'ChatGPT',
            'Data Science',
            'TensorFlow',
            'PyTorch',

            // مسیر شغلی و جامعه
            'Agile',
            'Scrum',
            'Open Source',
            'Freelance',
            'دورکاری',
            'استارتاپ',
            'مصاحبه-شغلی',
            'رزومه',
            'بهینه‌سازی',
            'Performance',
            'Caching',
            'SEO',
            'PWA',
            'زنبورک',
        ];
    }

    /**
     * Attach random tags to published questions and courses so counts look realistic.
     */
    private function assignTagsToContent(): void
    {
        $tags = Tag::query()->get();
        if ($tags->isEmpty()) {
            return;
        }

        $tagNames = $tags->pluck('name')->all();

        Question::query()
            ->where('publish', 1)
            ->whereDoesntHave('tags')
            ->inRandomOrder()
            ->limit(80)
            ->get()
            ->each(function (Question $question) use ($tagNames) {
                $picked = $this->pickRandomTagNames($tagNames, 1, 3);
                if ($picked !== []) {
                    $question->retag($picked);
                }
            });

        Course::query()
            ->whereDoesntHave('tags')
            ->inRandomOrder()
            ->limit(40)
            ->get()
            ->each(function (Course $course) use ($tagNames) {
                $picked = $this->pickRandomTagNames($tagNames, 1, 3);
                if ($picked !== []) {
                    $course->retag($picked);
                }
            });
    }

    /**
     * @param list<string> $tagNames
     * @return list<string>
     */
    private function pickRandomTagNames(array $tagNames, int $min, int $max): array
    {
        if ($tagNames === []) {
            return [];
        }

        $count = min(count($tagNames), random_int($min, $max));
        $keys = (array) array_rand($tagNames, $count);

        return array_values(array_unique(array_map(fn ($key) => $tagNames[$key], $keys)));
    }

    private function syncPostgresSequence(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('taggable_tags', 'tag_id'), COALESCE((SELECT MAX(tag_id) FROM taggable_tags), 1), true)"
        );
    }
}
