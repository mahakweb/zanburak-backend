<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionsAndAnswersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // دریافت ID های مورد نیاز
        $studentId = DB::table('users')->where('username', 'student')->value('id');
        $teacherId = DB::table('users')->where('username', 'teacher')->value('id');
        $adminId = DB::table('users')->where('username', 'admin')->value('id');

        // اگر کاربران وجود نداشتند، از اولین کاربر استفاده کن
        if (!$studentId) {
            $studentId = DB::table('users')->where('is_staff', false)->value('id') ?? 1;
        }
        if (!$teacherId) {
            $teacherId = DB::table('users')->where('is_staff', true)->value('id') ?? 1;
        }
        if (!$adminId) {
            $adminId = DB::table('users')->first()->id ?? 1;
        }

        // دریافت دسته‌بندی‌های پرسش‌ها
        $generalProgrammingCategoryId = DB::table('question_categories')->where('slug', 'general-programming')->value('id');
        $codingIssuesCategoryId = DB::table('question_categories')->where('slug', 'coding-issues')->value('id');
        $toolsTechnologyCategoryId = DB::table('question_categories')->where('slug', 'tools-technology')->value('id');
        $careerCategoryId = DB::table('question_categories')->where('slug', 'career')->value('id');
        $algorithmsCategoryId = DB::table('question_categories')->where('slug', 'algorithms-data-structures')->value('id');
        $debuggingCategoryId = DB::table('question_categories')->where('slug', 'debugging')->value('id');
        $ideEditorsCategoryId = DB::table('question_categories')->where('slug', 'ide-editors')->value('id');
        $jobInterviewCategoryId = DB::table('question_categories')->where('slug', 'job-interview')->value('id');

        // پرسش 1: مربوط به React
        $question1Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $codingIssuesCategoryId ?? $generalProgrammingCategoryId ?? 1,
            'subject' => 'چطور در React از useState استفاده کنم؟',
            'slug' => 'how-to-use-usestate-in-react',
            'question' => 'سلام، من تازه شروع به یادگیری React کردم و می‌خواهم بدانم چطور از useState برای مدیریت state استفاده کنم. لطفا یک مثال ساده بزنید.',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // پاسخ‌های پرسش 1
        $answer1_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question1Id,
            'answer' => 'برای استفاده از useState در React، ابتدا باید آن را import کنید:\n\n```javascript\nimport { useState } from \'react\';\n\nfunction MyComponent() {\n  const [count, setCount] = useState(0);\n  \n  return (\n    <div>\n      <p>شمارش: {count}</p>\n      <button onClick={() => setCount(count + 1)}>\n        افزایش\n      </button>\n    </div>\n  );\n}\n```\n\nuseState یک آرایه برمی‌گرداند که اولین عنصر آن مقدار state و دومی تابع setter است.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // این پاسخ را به عنوان best answer تنظیم می‌کنیم
        DB::table('questions')->where('id', $question1Id)->update(['best_answer' => $answer1_1Id]);

        $answer1_2Id = DB::table('answers')->insertGetId([
            'user_id' => $adminId,
            'question_id' => $question1Id,
            'answer' => 'نکته مهم: همیشه از setter function استفاده کنید و مستقیماً state را تغییر ندهید. این کار باعث re-render نمی‌شود.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(5),
            'updated_at' => $now->copy()->addMinutes(5),
        ]);

        // پرسش 2: مربوط به Laravel
        $question2Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $codingIssuesCategoryId ?? $generalProgrammingCategoryId ?? 1,
            'subject' => 'مشکل در Eloquent Relationships',
            'slug' => 'eloquent-relationships-problem',
            'question' => 'من در Laravel یک رابطه hasMany دارم اما نمی‌توانم داده‌ها را دریافت کنم. کد من:\n\n```php\n$user = User::find(1);\n$posts = $user->posts; // null برمی‌گرداند\n```\n\nکمک می‌خواهم.',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(10),
            'updated_at' => $now->copy()->addMinutes(10),
        ]);

        $answer2_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question2Id,
            'answer' => 'مشکل احتمالاً در تعریف relationship در Model است. مطمئن شوید که:\n\n1. در Model User رابطه را درست تعریف کرده‌اید:\n```php\npublic function posts()\n{\n    return $this->hasMany(Post::class);\n}\n```\n\n2. در جدول posts فیلد user_id وجود دارد.\n\n3. داده‌ها در دیتابیس وجود دارند.\n\nاگر هنوز مشکل دارید، کد Model خود را بفرستید تا بررسی کنم.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(15),
            'updated_at' => $now->copy()->addMinutes(15),
        ]);

        DB::table('questions')->where('id', $question2Id)->update(['best_answer' => $answer2_1Id]);

        // پرسش 3: مربوط به الگوریتم
        $question3Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $algorithmsCategoryId ?? $generalProgrammingCategoryId ?? 1,
            'subject' => 'بهترین الگوریتم برای جستجوی باینری',
            'slug' => 'best-binary-search-algorithm',
            'question' => 'می‌خواهم الگوریتم جستجوی باینری را پیاده‌سازی کنم. آیا کسی می‌تواند یک مثال کامل با توضیحات بدهد؟',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(20),
            'updated_at' => $now->copy()->addMinutes(20),
        ]);

        $answer3_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question3Id,
            'answer' => 'الگوریتم جستجوی باینری در JavaScript:\n\n```javascript\nfunction binarySearch(arr, target) {\n  let left = 0;\n  let right = arr.length - 1;\n  \n  while (left <= right) {\n    const mid = Math.floor((left + right) / 2);\n    \n    if (arr[mid] === target) {\n      return mid;\n    } else if (arr[mid] < target) {\n      left = mid + 1;\n    } else {\n      right = mid - 1;\n    }\n  }\n  \n  return -1; // پیدا نشد\n}\n```\n\nنکته: آرایه باید مرتب شده باشد.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(25),
            'updated_at' => $now->copy()->addMinutes(25),
        ]);

        DB::table('questions')->where('id', $question3Id)->update(['best_answer' => $answer3_1Id]);

        // پرسش 4: مربوط به IDE
        $question4Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $ideEditorsCategoryId ?? $toolsTechnologyCategoryId ?? 1,
            'subject' => 'بهترین IDE برای توسعه React',
            'slug' => 'best-ide-for-react-development',
            'question' => 'می‌خواهم شروع به یادگیری React کنم. کدام IDE یا ویرایشگر را پیشنهاد می‌دهید؟',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(30),
            'updated_at' => $now->copy()->addMinutes(30),
        ]);

        $answer4_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question4Id,
            'answer' => 'برای React پیشنهاد می‌کنم:\n\n1. **VS Code** - رایگان و محبوب، پلاگین‌های عالی برای React\n2. **WebStorm** - پولی اما قدرتمند، IntelliSense عالی\n3. **Atom** - رایگان و قابل تنظیم\n\nمن شخصاً VS Code را پیشنهاد می‌کنم چون رایگان است و extension های زیادی دارد.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(35),
            'updated_at' => $now->copy()->addMinutes(35),
        ]);

        $answer4_2Id = DB::table('answers')->insertGetId([
            'user_id' => $adminId,
            'question_id' => $question4Id,
            'answer' => 'VS Code + Extension های زیر:\n- ES7+ React/Redux/React-Native snippets\n- Prettier\n- ESLint\n- Auto Rename Tag\n\nاین ترکیب برای من عالی کار می‌کند.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(40),
            'updated_at' => $now->copy()->addMinutes(40),
        ]);

        DB::table('questions')->where('id', $question4Id)->update(['best_answer' => $answer4_1Id]);

        // پرسش 5: مربوط به مصاحبه شغلی
        $question5Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $jobInterviewCategoryId ?? $careerCategoryId ?? 1,
            'subject' => 'سوالات رایج مصاحبه React',
            'slug' => 'common-react-interview-questions',
            'question' => 'به‌زودی یک مصاحبه شغلی برای موقعیت React Developer دارم. چه سوالاتی ممکن است بپرسند؟',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(45),
            'updated_at' => $now->copy()->addMinutes(45),
        ]);

        $answer5_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question5Id,
            'answer' => 'سوالات رایج:\n\n1. تفاوت بین state و props چیست؟\n2. چرخه حیات کامپوننت (Lifecycle)\n3. Virtual DOM چیست؟\n4. تفاوت بین controlled و uncontrolled components\n5. Hooks چیست و چرا استفاده می‌شود؟\n6. Context API\n7. Performance optimization در React\n8. تفاوت بین useCallback و useMemo\n\nحتماً این موضوعات را مطالعه کنید.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(50),
            'updated_at' => $now->copy()->addMinutes(50),
        ]);

        DB::table('questions')->where('id', $question5Id)->update(['best_answer' => $answer5_1Id]);

        // پرسش 6: مربوط به Debugging
        $question6Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $debuggingCategoryId ?? $codingIssuesCategoryId ?? 1,
            'subject' => 'خطای CORS در API',
            'slug' => 'cors-error-in-api',
            'question' => 'وقتی از React به Laravel API درخواست می‌فرستم، خطای CORS می‌گیرم. چطور حلش کنم؟',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(55),
            'updated_at' => $now->copy()->addMinutes(55),
        ]);

        $answer6_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question6Id,
            'answer' => 'برای حل مشکل CORS در Laravel:\n\n1. پکیج `fruitcake/laravel-cors` را نصب کنید\n2. در `config/cors.php` تنظیمات را انجام دهید:\n\n```php\n\'paths\' => [\'api/*\'],\n\'allowed_origins\' => [\'http://localhost:3000\'],\n\'allowed_methods\' => [\'*\'],\n\'allowed_headers\' => [\'*\'],\n```\n\n3. در `app/Http/Kernel.php` middleware را اضافه کنید.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(60),
            'updated_at' => $now->copy()->addMinutes(60),
        ]);

        $answer6_2Id = DB::table('answers')->insertGetId([
            'user_id' => $adminId,
            'question_id' => $question6Id,
            'answer' => 'یا می‌توانید از Laravel Sanctum استفاده کنید که CORS را به صورت خودکار مدیریت می‌کند.',
            'parent_id' => $answer6_1Id, // پاسخ به پاسخ قبلی
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(65),
            'updated_at' => $now->copy()->addMinutes(65),
        ]);

        DB::table('questions')->where('id', $question6Id)->update(['best_answer' => $answer6_1Id]);

        // پرسش 7: مربوط به JavaScript
        $question7Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $generalProgrammingCategoryId ?? 1,
            'subject' => 'تفاوت بین let، const و var',
            'slug' => 'difference-between-let-const-var',
            'question' => 'می‌خواهم بدانم تفاوت بین let، const و var در JavaScript چیست؟',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(70),
            'updated_at' => $now->copy()->addMinutes(70),
        ]);

        $answer7_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question7Id,
            'answer' => 'تفاوت‌های اصلی:\n\n**var:**\n- Function-scoped\n- Hoisted می‌شود\n- قابل redeclare\n\n**let:**\n- Block-scoped\n- Hoisted نمی‌شود\n- قابل redeclare نیست\n- قابل reassign است\n\n**const:**\n- Block-scoped\n- Hoisted نمی‌شود\n- قابل redeclare نیست\n- قابل reassign نیست (اما object/array داخلی قابل تغییر است)\n\nپیشنهاد: همیشه از const استفاده کنید مگر اینکه نیاز به تغییر داشته باشید، آنگاه از let استفاده کنید. از var استفاده نکنید.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(75),
            'updated_at' => $now->copy()->addMinutes(75),
        ]);

        DB::table('questions')->where('id', $question7Id)->update(['best_answer' => $answer7_1Id]);

        // پرسش 8: مربوط به Git
        $question8Id = DB::table('questions')->insertGetId([
            'user_id' => $studentId,
            'category_id' => $toolsTechnologyCategoryId ?? 1,
            'subject' => 'چطور commit قبلی را undo کنم؟',
            'slug' => 'how-to-undo-last-commit',
            'question' => 'یک commit اشتباه زدم و می‌خواهم آن را undo کنم اما تغییرات را نگه دارم. چطور؟',
            'publish' => true,
            'is_private' => false,
            'best_answer' => null,
            'created_at' => $now->copy()->addMinutes(80),
            'updated_at' => $now->copy()->addMinutes(80),
        ]);

        $answer8_1Id = DB::table('answers')->insertGetId([
            'user_id' => $teacherId,
            'question_id' => $question8Id,
            'answer' => 'اگر commit را push نکرده‌اید:\n\n```bash\ngit reset --soft HEAD~1\n```\n\nاین دستور commit را برمی‌گرداند اما تغییرات را در staging area نگه می‌دارد.\n\nاگر می‌خواهید تغییرات را هم حذف کنید:\n\n```bash\ngit reset --hard HEAD~1\n```\n\n⚠️ توجه: اگر commit را push کرده‌اید، از `git revert` استفاده کنید.',
            'parent_id' => 0,
            'publish' => true,
            'pinned_at' => null,
            'created_at' => $now->copy()->addMinutes(85),
            'updated_at' => $now->copy()->addMinutes(85),
        ]);

        DB::table('questions')->where('id', $question8Id)->update(['best_answer' => $answer8_1Id]);
    }
}

