<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quizzes and the pivot that links bank questions into a quiz.
 *
 * A quiz attaches polymorphically to a Course, Section, or Episode (lesson)
 * via the `quizzable` morph, and may also be standalone (nullable morph).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Polymorphic owner: Course | Section | Episode (nullable = standalone).
            $table->string('quizzable_type')->nullable();
            $table->unsignedBigInteger('quizzable_id')->nullable();

            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();

            // Pass thresholds. Either an absolute score or a percentage (or both).
            $table->decimal('passing_score', 8, 2)->nullable();
            $table->decimal('passing_percentage', 5, 2)->nullable();

            // Time limit in seconds; null = no limit.
            $table->unsignedInteger('time_limit')->nullable();

            // Attempts. null = unlimited, otherwise the max allowed.
            $table->unsignedInteger('max_attempts')->nullable();

            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_answers')->default(false);

            // immediately | after_review | after_end
            $table->string('result_display', 20)->default('immediately');

            // Negative marking for wrong answers (auto-graded types).
            $table->boolean('negative_scoring')->default(false);
            $table->decimal('negative_scoring_factor', 5, 2)->default(0); // fraction of score deducted

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // Force every attempt through human review before completion.
            $table->boolean('manual_review_required')->default(false);

            // Show correct answers / explanations on the review page.
            $table->boolean('show_correct_answers')->default(true);

            $table->boolean('is_published')->default(false);

            // Denormalised aggregates for fast list/report rendering at scale.
            $table->unsignedInteger('questions_count')->default(0);
            $table->decimal('total_score', 10, 2)->default(0);

            MigrationColumnHelpers::jsonColumn($table, 'settings', nullable: true);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['quizzable_type', 'quizzable_id'], 'quizzes_quizzable_index');
            $table->index(['is_published', 'start_at', 'end_at'], 'quizzes_availability_index');
        });

        // Questions selected into a quiz, with per-quiz ordering and score override.
        Schema::create('quiz_quiz_question', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedInteger('position')->default(0);
            $table->decimal('score', 8, 2)->nullable(); // overrides question.default_score when set
            $table->timestamps();

            $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();

            $table->unique(['quiz_id', 'question_id'], 'quiz_quiz_question_unique');
            $table->index(['quiz_id', 'position'], 'quiz_quiz_question_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_quiz_question');
        Schema::dropIfExists('quizzes');
    }
};
