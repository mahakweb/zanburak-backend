<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Like;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\User;
use App\Models\View;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LaravelInteraction\Bookmark\Bookmark;

/**
 * داده‌های نمونه برای تست content scoping:
 * چند مدرس، دوره‌های جدا، پرداخت، بازدید، لایک، کامنت و گواهینامه.
 */
class ContentScopeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $student = User::query()->where('username', 'student')->first();
        $mainTeacher = User::query()->where('username', 'teacher')->first();
        if (! $student || ! $mainTeacher) {
            $this->command?->warn('ContentScopeDemoSeeder skipped: student or teacher user not found.');

            return;
        }

        $teacherRoleId = DB::table('roles')->where('name', 'teacher')->value('id');
        $financeRoleId = DB::table('roles')->where('name', 'teacher_finance_viewer')->value('id');

        $extraTeachers = [
            [
                'username' => 'teacher_sara',
                'first_name' => 'سارا',
                'last_name' => 'محمدی',
                'email' => 'teacher.sara@zanburak.ir',
                'courses' => [
                    [
                        'title' => 'آموزش Tailwind CSS از صفر',
                        'english_title' => 'tailwind-css-zero-to-hero',
                        'slug' => 'tailwind-css-zero-to-hero',
                        'short_description' => 'یادگیری Tailwind برای ساخت رابط کاربری مدرن',
                        'description' => 'دوره عملی Tailwind CSS با پروژه‌های واقعی برای مدرس سارا.',
                        'price' => 349000,
                    ],
                    [
                        'title' => 'طراحی UI با Figma',
                        'english_title' => 'figma-ui-design-basics',
                        'slug' => 'figma-ui-design-basics',
                        'short_description' => 'اصول طراحی رابط کاربری در Figma',
                        'description' => 'دوره طراحی UI برای تست اسکوپ محتوای مدرس دوم.',
                        'price' => 279000,
                    ],
                ],
            ],
            [
                'username' => 'teacher_reza',
                'first_name' => 'رضا',
                'last_name' => 'نوری',
                'email' => 'teacher.reza@zanburak.ir',
                'courses' => [
                    [
                        'title' => 'برنامه‌نویسی Python برای مبتدیان',
                        'english_title' => 'python-programming-beginners',
                        'slug' => 'python-programming-beginners',
                        'short_description' => 'شروع برنامه‌نویسی با Python',
                        'description' => 'دوره Python متعلق به مدرس رضا برای تست جداسازی محتوا.',
                        'price' => 399000,
                    ],
                    [
                        'title' => 'دیتاساینس مقدماتی',
                        'english_title' => 'intro-data-science',
                        'slug' => 'intro-data-science',
                        'short_description' => 'مفاهیم پایه دیتاساینس',
                        'description' => 'دوره دیتاساینس برای تست گزارش فروش و گواهینامه مدرس رضا.',
                        'price' => 459000,
                    ],
                ],
            ],
        ];

        $levelId = DB::table('levels')->where('english_title', 'beginner')->value('id')
            ?? DB::table('levels')->value('id');
        $statusId = DB::table('statuses')->where('english_title', 'completed')->value('id')
            ?? DB::table('statuses')->value('id');
        $categoryId = DB::table('categories')->where('slug', 'frontend')->value('id')
            ?? DB::table('categories')->value('id');
        $templateId = DB::table('certificate_templates')->value('id');

        $allDemoCourses = collect();

        foreach ($extraTeachers as $teacherData) {
            $teacher = User::query()->firstOrCreate(
                ['username' => $teacherData['username']],
                [
                    'first_name' => $teacherData['first_name'],
                    'last_name' => $teacherData['last_name'],
                    'email' => $teacherData['email'],
                    'email_verified_at' => $now,
                    'mobile' => '+98912'.random_int(1000000, 9999999),
                    'mobile_verified_at' => $now,
                    'password' => Hash::make('password'),
                    'is_superuser' => false,
                    'is_staff' => false,
                    'active' => true,
                    'wallet_balance' => 0,
                    'notifications_enabled' => true,
                    'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
                    'cover_pic' => 'https://static.zanburak.ir/images/cover/default.png',
                ]
            );

            if ($teacherRoleId) {
                DB::table('role_user')->updateOrInsert(
                    ['role_id' => $teacherRoleId, 'user_id' => $teacher->id],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }

            foreach ($teacherData['courses'] as $courseData) {
                $exists = DB::table('courses')->where('slug', $courseData['slug'])->exists();
                if ($exists) {
                    $course = Course::query()->where('slug', $courseData['slug'])->first();
                    $allDemoCourses->push($course);

                    continue;
                }

                $courseId = DB::table('courses')->insertGetId([
                    'teacher_id' => $teacher->id,
                    'title' => $courseData['title'],
                    'english_title' => $courseData['english_title'],
                    'slug' => $courseData['slug'],
                    'short_description' => $courseData['short_description'],
                    'description' => $courseData['description'],
                    'total_time' => '600',
                    'start_date' => $now,
                    'price' => $courseData['price'],
                    'publish' => true,
                    'status_id' => $statusId,
                    'level_id' => $levelId,
                    'type' => 'cash',
                    'poster' => 'https://static.zanburak.ir/poster/no-image.png',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($categoryId) {
                    DB::table('category_course')->updateOrInsert(
                        ['category_id' => $categoryId, 'course_id' => $courseId]
                    );
                }

                $allDemoCourses->push(Course::query()->find($courseId));
            }
        }

        // مدرس اصلی هم دسترسی مالی scoped بگیرد (برای تست گزارش فروش)
        if ($financeRoleId) {
            DB::table('role_user')->updateOrInsert(
                ['role_id' => $financeRoleId, 'user_id' => $mainTeacher->id],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $mainTeacherCourses = Course::query()->where('teacher_id', $mainTeacher->id)->take(2)->get();
        $seedCourses = $allDemoCourses->merge($mainTeacherCourses)->filter()->unique('id')->values();

        foreach ($seedCourses as $index => $course) {
            $this->seedEngagementForCourse($course, $student, $now, $index);
            $this->seedPaymentForCourse($course, $student, $now, $index);
            $this->seedCertificateForCourse($course, $student, $templateId, $now, $index);
        }

        $this->command?->info('ContentScopeDemoSeeder: seeded multi-teacher demo data.');
    }

    private function seedEngagementForCourse(Course $course, User $student, $now, int $index): void
    {
        if (! View::query()->where('viewable_type', Course::class)->where('viewable_id', $course->id)->exists()) {
            for ($i = 0; $i < 5 + $index; $i++) {
                View::query()->create([
                    'viewable_type' => Course::class,
                    'viewable_id' => $course->id,
                    'ip_address' => '127.0.0.'.(10 + $index),
                    'user_agent' => 'Mozilla/5.0 (Demo Seeder)',
                    'user_id' => $i % 2 === 0 ? $student->id : null,
                    'created_at' => $now->copy()->subDays($i),
                    'updated_at' => $now->copy()->subDays($i),
                ]);
            }
        }

        if (! Like::query()->where('likeable_type', Course::class)->where('likeable_id', $course->id)->exists()) {
            Like::query()->create([
                'user_id' => $student->id,
                'likeable_type' => Course::class,
                'likeable_id' => $course->id,
                'type' => 'like',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Bookmark::query()->where('bookmarkable_type', Course::class)->where('bookmarkable_id', $course->id)->exists()) {
            Bookmark::query()->create([
                'user_id' => $student->id,
                'bookmarkable_type' => Course::class,
                'bookmarkable_id' => $course->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Comment::query()->where('commentable_type', Course::class)->where('commentable_id', $course->id)->exists()) {
            Comment::query()->create([
                'user_id' => $student->id,
                'comment' => 'نظر نمونه برای تست اسکوپ دوره «'.$course->title.'»',
                'parent_id' => 0,
                'approved' => $index % 2 === 0 ? 1 : 0,
                'commentable_type' => Course::class,
                'commentable_id' => $course->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedPaymentForCourse(Course $course, User $student, $now, int $index): void
    {
        $exists = PaymentItem::query()
            ->where('payable_type', Course::class)
            ->where('payable_id', $course->id)
            ->exists();

        if ($exists) {
            return;
        }

        $amount = (int) $course->price;
        $payment = Payment::query()->create([
            'user_id' => $student->id,
            'payment_method' => 'bank',
            'driver' => 'zarinpal',
            'resnumber' => 'DEMO-'.strtoupper(Str::random(8)).'-'.$course->id,
            'amount' => $amount,
            'discount_amount' => 0,
            'status' => 1,
            'paid_at' => $now->copy()->subDays($index + 1),
            'description' => 'پرداخت نمونه برای تست اسکوپ — '.$course->title,
        ]);

        PaymentItem::query()->create([
            'payment_id' => $payment->id,
            'payable_type' => Course::class,
            'payable_id' => $course->id,
            'price' => $amount,
            'discount_amount' => 0,
            'final_price' => $amount,
        ]);
    }

    private function seedCertificateForCourse(Course $course, User $student, ?int $templateId, $now, int $index): void
    {
        $exists = Certificate::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return;
        }

        Certificate::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'certificate_template_id' => $templateId,
            'uuid' => (string) Str::uuid(),
            'serial_number' => 'DEMO-'.str_pad((string) ($course->id * 10 + $index), 8, '0', STR_PAD_LEFT),
            'verification_token' => Str::random(32),
            'user_name' => trim($student->first_name.' '.$student->last_name),
            'course_title' => $course->title,
            'instructor_name' => $course->teacher?->first_name.' '.$course->teacher?->last_name,
            'grade' => 85 + $index,
            'time_completed' => 3600 + ($index * 600),
            'issued_at' => $now->copy()->subDays($index),
            'status' => 'issued',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
