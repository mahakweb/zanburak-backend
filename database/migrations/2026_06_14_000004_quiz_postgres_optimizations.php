<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL-only indexes for the quiz subsystem.
 *
 * - GIN indexes on jsonb payloads so answer/settings can be queried by key.
 * - Partial indexes for the hottest filtered paths (in-progress attempts,
 *   manual-review queue, published-quiz availability) which keep the indexes
 *   tiny relative to the full tables at millions of rows.
 *
 * No-op on MySQL so the schema still applies during a gradual cutover.
 *
 * Scaling note: `quiz_attempts` and `quiz_attempt_answers` are the natural
 * partition candidates. To range-partition by month, recreate them with
 * `PARTITION BY RANGE (created_at)` and attach monthly partitions; the
 * application code and indexes below are all partition-compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // jsonb GIN indexes.
        DB::statement('CREATE INDEX IF NOT EXISTS quiz_questions_settings_gin ON quiz_questions USING GIN (settings)');
        DB::statement('CREATE INDEX IF NOT EXISTS quizzes_settings_gin ON quizzes USING GIN (settings)');
        DB::statement('CREATE INDEX IF NOT EXISTS quiz_attempt_answers_answer_gin ON quiz_attempt_answers USING GIN (answer)');

        // Partial index: only the small set of live, resumable attempts.
        DB::statement("CREATE INDEX IF NOT EXISTS quiz_attempts_in_progress ON quiz_attempts (user_id, quiz_id) WHERE status = 'in_progress'");

        // Partial index: manual-review work queue.
        DB::statement("CREATE INDEX IF NOT EXISTS quiz_attempts_pending_review ON quiz_attempts (quiz_id) WHERE requires_manual_review = true AND status IN ('submitted','grading')");

        // Partial index: answers still awaiting a grader.
        DB::statement('CREATE INDEX IF NOT EXISTS quiz_attempt_answers_pending ON quiz_attempt_answers (attempt_id) WHERE needs_manual_review = true');

        // Partial index: currently-available published quizzes.
        DB::statement('CREATE INDEX IF NOT EXISTS quizzes_published ON quizzes (id) WHERE is_published = true');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'quiz_questions_settings_gin',
            'quizzes_settings_gin',
            'quiz_attempt_answers_answer_gin',
            'quiz_attempts_in_progress',
            'quiz_attempts_pending_review',
            'quiz_attempt_answers_pending',
            'quizzes_published',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
