<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Episode;
use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizQuestion;
use App\Models\Quiz\QuizQuestionCategory;
use App\Models\Quiz\QuizTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the question bank with several questions of every supported type,
 * plus quizzes attached to a few courses and episodes (lessons).
 */
class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->where('username', 'teacher')->value('id')
            ?? DB::table('users')->where('is_staff', true)->value('id')
            ?? DB::table('users')->value('id');

        $categories = $this->seedCategories();
        $tags = $this->seedTags();
        $questions = $this->seedQuestions($categories, $tags, $authorId);

        $this->seedQuizzes($questions, $authorId);
    }

    private function seedCategories(): array
    {
        $defs = [
            'general' => 'عمومی',
            'frontend' => 'فرانت‌اند',
            'backend' => 'بک‌اند',
            'javascript' => 'جاوااسکریپت',
            'php' => 'پی‌اچ‌پی',
        ];

        $map = [];
        foreach ($defs as $slug => $name) {
            $map[$slug] = QuizQuestionCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            )->id;
        }

        return $map;
    }

    private function seedTags(): array
    {
        $names = ['مبتدی', 'متوسط', 'پیشرفته', 'مفهومی', 'کاربردی', 'تئوری'];
        $map = [];
        foreach ($names as $name) {
            $map[$name] = QuizTag::firstOrCreate(
                ['slug' => Str::slug($name, '-', 'fa')],
                ['name' => $name]
            )->id;
        }

        return $map;
    }

    /**
     * Build several questions for each of the 8 question types.
     *
     * @return array<int> created question IDs
     */
    private function seedQuestions(array $categories, array $tags, ?int $authorId): array
    {
        $ids = [];

        // ── 1) Single choice ────────────────────────────────────────
        $singleChoice = [
            [
                'text' => 'کدام هوک React برای مدیریت state در کامپوننت تابعی استفاده می‌شود؟',
                'category' => 'frontend',
                'options' => [['useState', true], ['useFetch', false], ['useStore', false], ['useData', false]],
                'explanation' => 'useState برای نگه‌داری state محلی در کامپوننت‌های تابعی است.',
            ],
            [
                'text' => 'دستور صحیح برای ایجاد یک پروژه جدید Laravel کدام است؟',
                'category' => 'php',
                'options' => [['composer create-project laravel/laravel app', true], ['npm init laravel', false], ['laravel build', false], ['php artisan new', false]],
            ],
            [
                'text' => 'خروجی typeof null در جاوااسکریپت چیست؟',
                'category' => 'javascript',
                'options' => [['"object"', true], ['"null"', false], ['"undefined"', false], ['"number"', false]],
                'explanation' => 'این یک رفتار تاریخی و شناخته‌شده در جاوااسکریپت است.',
            ],
            [
                'text' => 'کدام متد HTTP برای ایجاد یک منبع جدید به‌کار می‌رود؟',
                'category' => 'backend',
                'options' => [['POST', true], ['GET', false], ['DELETE', false], ['HEAD', false]],
            ],
        ];
        foreach ($singleChoice as $q) {
            $ids[] = $this->makeChoiceQuestion('single_choice', $q, $categories, $tags, $authorId);
        }

        // ── 2) Multiple choice ──────────────────────────────────────
        $multipleChoice = [
            [
                'text' => 'کدام موارد از hookهای داخلی React هستند؟ (چند گزینه)',
                'category' => 'frontend',
                'options' => [['useEffect', true], ['useMemo', true], ['useCallback', true], ['useDatabase', false]],
            ],
            [
                'text' => 'کدام موارد جزو متدهای آرایه در جاوااسکریپت هستند؟',
                'category' => 'javascript',
                'options' => [['map', true], ['filter', true], ['reduce', true], ['transform', false]],
            ],
            [
                'text' => 'کدام موارد از قابلیت‌های Eloquent ORM هستند؟',
                'category' => 'php',
                'options' => [['Relationships', true], ['Eager Loading', true], ['Soft Deletes', true], ['Auto Deploy', false]],
            ],
        ];
        foreach ($multipleChoice as $q) {
            $ids[] = $this->makeChoiceQuestion('multiple_choice', $q, $categories, $tags, $authorId);
        }

        // ── 3) True / False ─────────────────────────────────────────
        $trueFalse = [
            ['text' => 'در جاوااسکریپت، === مقدار و نوع را با هم مقایسه می‌کند.', 'answer' => true, 'category' => 'javascript'],
            ['text' => 'در Laravel، فایل‌های migration در پوشه app قرار دارند.', 'answer' => false, 'category' => 'php', 'explanation' => 'فایل‌های migration در پوشه database/migrations قرار دارند.'],
            ['text' => 'React یک کتابخانه برای ساخت رابط کاربری است.', 'answer' => true, 'category' => 'frontend'],
            ['text' => 'متد GET در HTTP برای حذف منابع استفاده می‌شود.', 'answer' => false, 'category' => 'backend'],
        ];
        foreach ($trueFalse as $q) {
            $ids[] = $this->makeTrueFalse($q, $categories, $tags, $authorId);
        }

        // ── 4) Short answer ─────────────────────────────────────────
        $shortAnswer = [
            ['text' => 'نام دستوری که در Laravel برای اجرای migrationها استفاده می‌شود را بنویسید (بدون php artisan).', 'accepted' => ['migrate'], 'category' => 'php'],
            ['text' => 'تابعی که در جاوااسکریپت طول یک رشته را برمی‌گرداند چه نام دارد؟', 'accepted' => ['length'], 'category' => 'javascript'],
            ['text' => 'پروتکل امن نسخه HTTP چه نام دارد؟', 'accepted' => ['https', 'HTTPS'], 'category' => 'backend'],
        ];
        foreach ($shortAnswer as $q) {
            $ids[] = $this->makeShortAnswer($q, $categories, $tags, $authorId);
        }

        // ── 5) Long / descriptive answer (manual review) ────────────
        $longAnswer = [
            ['text' => 'تفاوت بین props و state در React را توضیح دهید.', 'category' => 'frontend'],
            ['text' => 'مفهوم Dependency Injection را با یک مثال شرح دهید.', 'category' => 'backend'],
            ['text' => 'چرخه رویداد (Event Loop) در جاوااسکریپت چگونه کار می‌کند؟', 'category' => 'javascript'],
        ];
        foreach ($longAnswer as $q) {
            $ids[] = $this->makeLongAnswer($q, $categories, $tags, $authorId);
        }

        // ── 6) Fill in the blank ────────────────────────────────────
        $fillBlank = [
            [
                'text' => 'برای تعریف ثابت در جاوااسکریپت از کلیدواژه ____ و برای متغیر قابل‌تغییر بلوکی از ____ استفاده می‌کنیم.',
                'blanks' => [['const'], ['let']],
                'category' => 'javascript',
            ],
            [
                'text' => 'در Laravel فایل تنظیمات اصلی دیتابیس در config/____.php و متغیرهای محیطی در فایل ____ قرار دارند.',
                'blanks' => [['database'], ['.env', 'env']],
                'category' => 'php',
            ],
        ];
        foreach ($fillBlank as $q) {
            $ids[] = $this->makeFillBlank($q, $categories, $tags, $authorId);
        }

        // ── 7) Matching ─────────────────────────────────────────────
        $matching = [
            [
                'text' => 'هر متد HTTP را به کاربرد آن متصل کنید.',
                'category' => 'backend',
                'pairs' => [['GET', 'دریافت داده'], ['POST', 'ایجاد داده'], ['PUT', 'به‌روزرسانی داده'], ['DELETE', 'حذف داده']],
            ],
            [
                'text' => 'هر هوک React را به کاربردش متصل کنید.',
                'category' => 'frontend',
                'pairs' => [['useState', 'مدیریت state'], ['useEffect', 'side effects'], ['useContext', 'دسترسی به context']],
            ],
        ];
        foreach ($matching as $q) {
            $ids[] = $this->makeMatching($q, $categories, $tags, $authorId);
        }

        // ── 8) Ordering ─────────────────────────────────────────────
        $ordering = [
            [
                'text' => 'مراحل ساخت یک پروژه React را به ترتیب صحیح مرتب کنید.',
                'category' => 'frontend',
                'items' => ['نصب Node.js', 'اجرای create-react-app', 'نوشتن کامپوننت', 'اجرای npm start'],
            ],
            [
                'text' => 'چرخه عمر یک درخواست در Laravel را مرتب کنید.',
                'category' => 'php',
                'items' => ['ورود درخواست به public/index.php', 'بارگذاری Kernel', 'عبور از Middleware', 'اجرای Controller', 'ارسال Response'],
            ],
        ];
        foreach ($ordering as $q) {
            $ids[] = $this->makeOrdering($q, $categories, $tags, $authorId);
        }

        return $ids;
    }

    private function makeChoiceQuestion(string $type, array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => $type,
            'text' => $q['text'],
            'explanation' => $q['explanation'] ?? null,
            'difficulty' => $q['difficulty'] ?? 'medium',
            'default_score' => 1,
            'category_id' => $categories[$q['category']] ?? null,
            'created_by' => $authorId,
        ]);

        foreach ($q['options'] as $i => [$text, $correct]) {
            $question->options()->create([
                'text' => $text,
                'is_correct' => $correct,
                'position' => $i,
            ]);
        }

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function makeTrueFalse(array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => 'true_false',
            'text' => $q['text'],
            'explanation' => $q['explanation'] ?? null,
            'difficulty' => 'easy',
            'default_score' => 1,
            'category_id' => $categories[$q['category']] ?? null,
            'created_by' => $authorId,
        ]);

        $question->options()->create(['text' => 'درست', 'is_correct' => $q['answer'] === true, 'position' => 0]);
        $question->options()->create(['text' => 'غلط', 'is_correct' => $q['answer'] === false, 'position' => 1]);

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function makeShortAnswer(array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => 'short_answer',
            'text' => $q['text'],
            'difficulty' => 'medium',
            'default_score' => 1,
            'category_id' => $categories[$q['category']] ?? null,
            'settings' => ['case_sensitive' => false],
            'created_by' => $authorId,
        ]);

        foreach ($q['accepted'] as $i => $answer) {
            $question->options()->create(['text' => $answer, 'is_correct' => true, 'position' => $i]);
        }

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function makeLongAnswer(array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => 'long_answer',
            'text' => $q['text'],
            'difficulty' => 'hard',
            'default_score' => 5,
            'category_id' => $categories[$q['category']] ?? null,
            'requires_manual_review' => true,
            'created_by' => $authorId,
        ]);

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function makeFillBlank(array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => 'fill_blank',
            'text' => $q['text'],
            'difficulty' => 'medium',
            'default_score' => 2,
            'category_id' => $categories[$q['category']] ?? null,
            'settings' => ['case_sensitive' => false],
            'created_by' => $authorId,
        ]);

        $position = 0;
        foreach ($q['blanks'] as $blankIndex => $acceptedList) {
            foreach ($acceptedList as $accepted) {
                $question->options()->create([
                    'text' => $accepted,
                    'is_correct' => true,
                    'blank_index' => $blankIndex,
                    'position' => $position++,
                ]);
            }
        }

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function makeMatching(array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => 'matching',
            'text' => $q['text'],
            'difficulty' => 'medium',
            'default_score' => 3,
            'category_id' => $categories[$q['category']] ?? null,
            'created_by' => $authorId,
        ]);

        foreach ($q['pairs'] as $i => [$key, $value]) {
            $question->options()->create([
                'text' => $key,
                'match_key' => $key,
                'match_value' => $value,
                'position' => $i,
            ]);
        }

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function makeOrdering(array $q, array $categories, array $tags, ?int $authorId): int
    {
        $question = QuizQuestion::create([
            'type' => 'ordering',
            'text' => $q['text'],
            'difficulty' => 'medium',
            'default_score' => 3,
            'category_id' => $categories[$q['category']] ?? null,
            'created_by' => $authorId,
        ]);

        foreach ($q['items'] as $i => $item) {
            $question->options()->create([
                'text' => $item,
                'correct_position' => $i,
                'position' => $i,
            ]);
        }

        $this->attachTags($question, $tags);

        return $question->id;
    }

    private function attachTags(QuizQuestion $question, array $tags): void
    {
        $pick = collect($tags)->random(min(2, count($tags)));
        $question->tags()->syncWithoutDetaching($pick->values()->all());
    }

    /**
     * Create quizzes attached to a few courses and episodes.
     */
    private function seedQuizzes(array $questionIds, ?int $authorId): void
    {
        if (empty($questionIds)) {
            return;
        }

        $courses = Course::query()->orderBy('id')->take(3)->get();
        $episodes = Episode::query()->orderBy('id')->take(4)->get();

        $now = now();

        // Course-level final exams.
        foreach ($courses as $index => $course) {
            $quiz = $this->createQuiz([
                'quizzable' => $course,
                'title' => 'آزمون پایانی دوره: ' . $course->title,
                'description' => 'این آزمون دانش شما را در کل دوره می‌سنجد.',
                'passing_percentage' => 60,
                'time_limit' => 1200,
                'max_attempts' => $index === 0 ? null : 3,
                'randomize_questions' => true,
                'randomize_answers' => true,
                'negative_scoring' => $index === 1,
                'negative_scoring_factor' => 0.25,
                'manual_review_required' => false,
                'show_correct_answers' => true,
                'result_display' => 'immediately',
                'is_published' => true,
                'created_by' => $authorId,
            ]);

            // Mix of question types, 8 per course quiz.
            $picked = collect($questionIds)->shuffle()->take(8)->values()->all();
            $this->attachQuestions($quiz, $picked);
        }

        // Episode (lesson) quick quizzes.
        foreach ($episodes as $index => $episode) {
            $quiz = $this->createQuiz([
                'quizzable' => $episode,
                'title' => 'کوییز درس: ' . $episode->title,
                'description' => 'یک آزمون کوتاه برای جمع‌بندی این درس.',
                'passing_percentage' => 50,
                'time_limit' => 300,
                'max_attempts' => null,
                'randomize_questions' => false,
                'randomize_answers' => true,
                'negative_scoring' => false,
                'manual_review_required' => false,
                'show_correct_answers' => true,
                'result_display' => 'immediately',
                'is_published' => true,
                'created_by' => $authorId,
            ]);

            $picked = collect($questionIds)->shuffle()->take(4)->values()->all();
            $this->attachQuestions($quiz, $picked);
        }

        // One quiz that requires manual review (contains descriptive questions).
        if ($courses->isNotEmpty()) {
            $quiz = $this->createQuiz([
                'quizzable' => $courses->first(),
                'title' => 'آزمون تشریحی (نیازمند تصحیح دستی)',
                'description' => 'این آزمون شامل سوالات تشریحی است و پس از تصحیح مدرس نمره‌دهی می‌شود.',
                'passing_percentage' => 70,
                'time_limit' => 1800,
                'max_attempts' => 2,
                'randomize_questions' => false,
                'randomize_answers' => false,
                'negative_scoring' => false,
                'manual_review_required' => true,
                'show_correct_answers' => false,
                'result_display' => 'after_review',
                'is_published' => true,
                'created_by' => $authorId,
            ]);

            $longAnswerIds = QuizQuestion::where('type', 'long_answer')->pluck('id')->all();
            $extra = collect($questionIds)->shuffle()->take(3)->values()->all();
            $this->attachQuestions($quiz, array_merge($longAnswerIds, $extra));
        }
    }

    private function createQuiz(array $data): Quiz
    {
        $quizzable = $data['quizzable'];
        unset($data['quizzable']);

        $data['slug'] = Str::slug($data['title']) ?: Str::random(8);

        $quiz = new Quiz($data);
        $quiz->quizzable()->associate($quizzable);
        $quiz->save();

        return $quiz;
    }

    private function attachQuestions(Quiz $quiz, array $questionIds): void
    {
        $sync = [];
        foreach (array_values(array_unique($questionIds)) as $position => $id) {
            $sync[$id] = ['position' => $position + 1];
        }
        $quiz->questions()->sync($sync);
        $quiz->recalculateTotals();
    }
}
