<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attempts and per-question answers.
 *
 * These are the highest-volume tables (one attempt per student-quiz try,
 * many answers per attempt), so they are designed for millions of rows:
 *   - bigserial PKs
 *   - jsonb answer payloads (no wide nullable columns per type)
 *   - composite indexes tuned for the report and history queries
 *   - frozen question/answer order stored on the attempt for resume
 *
 * For true internet-scale volume these tables are good candidates for
 * declarative range partitioning by created_at; see the optimization
 * migration for the recommended approach.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedInteger('attempt_number')->default(1);

            // in_progress | submitted | grading | completed | abandoned | expired
            $table->string('status', 20)->default('in_progress');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // started_at + time_limit

            $table->decimal('score', 10, 2)->default(0);
            $table->decimal('max_score', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->boolean('passed')->nullable();

            $table->unsignedInteger('time_spent')->default(0); // seconds

            $table->boolean('requires_manual_review')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // Frozen presentation order so a resumed attempt looks identical.
            MigrationColumnHelpers::jsonColumn($table, 'question_order', nullable: true);
            MigrationColumnHelpers::jsonColumn($table, 'meta', nullable: true);

            $table->timestamps();

            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            // History page: a user's attempts on a quiz, newest first.
            $table->index(['quiz_id', 'user_id', 'attempt_number'], 'quiz_attempts_user_quiz_index');
            // Reports: pass/fail and score aggregation per quiz.
            $table->index(['quiz_id', 'status'], 'quiz_attempts_quiz_status_index');
            $table->index(['user_id', 'status'], 'quiz_attempts_user_status_index');
            // Manual review queue.
            $table->index(['requires_manual_review', 'status'], 'quiz_attempts_review_queue_index');

            $table->unique(['quiz_id', 'user_id', 'attempt_number'], 'quiz_attempts_unique_try');
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedBigInteger('question_id');

            // Normalised student response, shape depends on question type:
            //  choice/true_false : {"option_ids": [..]}
            //  short/long/fill   : {"text": "..."} or {"blanks": ["..", ".."]}
            //  matching          : {"matches": {"left_id": "right_value", ...}}
            //  ordering          : {"order": [option_id, option_id, ...]}
            MigrationColumnHelpers::jsonColumn($table, 'answer', nullable: true);

            $table->boolean('is_correct')->nullable();      // null until graded / pending manual
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->boolean('needs_manual_review')->default(false);
            $table->text('reviewer_comment')->nullable();
            $table->timestamp('answered_at')->nullable();

            $table->timestamps();

            $table->foreign('attempt_id')->references('id')->on('quiz_attempts')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();

            $table->unique(['attempt_id', 'question_id'], 'quiz_attempt_answers_unique');
            $table->index('question_id', 'quiz_attempt_answers_question_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
    }
};
