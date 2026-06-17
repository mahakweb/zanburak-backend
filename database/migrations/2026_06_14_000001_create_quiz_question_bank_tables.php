<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question bank: categories, tags, questions and their options/answers.
 *
 * All quiz tables are prefixed with `quiz_` to avoid colliding with the
 * existing Q&A `questions` / `question_categories` tables (the discuss feature).
 *
 * Designed to scale to millions of rows: bigserial PKs, jsonb for flexible
 * type-specific payloads, and composite/partial indexes added in a later
 * optimization migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hierarchical categories for the question bank.
        Schema::create('quiz_question_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('quiz_question_categories')->nullOnDelete();
            $table->index('parent_id', 'quiz_qcat_parent_index');
        });

        // Free-form tags.
        Schema::create('quiz_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // The question bank itself.
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('category_id')->nullable();

            // single_choice | multiple_choice | true_false | short_answer |
            // long_answer | fill_blank | matching | ordering
            $table->string('type', 30);

            $table->text('text');                      // the prompt / stem
            $table->text('explanation')->nullable();   // shown after answering

            // easy | medium | hard
            $table->string('difficulty', 10)->default('medium');

            $table->decimal('default_score', 8, 2)->default(1);

            // Type-specific configuration that does not warrant its own columns,
            // e.g. number of blanks, matching layout hints, case-sensitivity.
            MigrationColumnHelpers::jsonColumn($table, 'settings', nullable: true);

            // Questions that always need a human grader (long_answer, optionally short_answer).
            $table->boolean('requires_manual_review')->default(false);

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('quiz_question_categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index('type', 'quiz_questions_type_index');
            $table->index('difficulty', 'quiz_questions_difficulty_index');
            $table->index(['category_id', 'is_active'], 'quiz_questions_category_active_index');
        });

        // Options / accepted answers / matching pairs / ordering items.
        // Reused across types via nullable, type-specific columns.
        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('question_id');

            $table->text('text')->nullable();          // option label / accepted answer text
            $table->boolean('is_correct')->default(false); // choice & true/false & accepted short/fill answers

            // fill_blank: which blank (0-based) this accepted answer belongs to.
            $table->unsignedSmallInteger('blank_index')->nullable();

            // matching: left side key + right side value.
            $table->text('match_key')->nullable();
            $table->text('match_value')->nullable();

            // ordering: the correct 0-based position of this item.
            $table->unsignedSmallInteger('correct_position')->nullable();

            $table->text('feedback')->nullable();
            $table->unsignedInteger('position')->default(0); // display order

            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();
            $table->index(['question_id', 'position'], 'quiz_question_options_q_pos_index');
        });

        // Question <-> Tag pivot.
        Schema::create('quiz_question_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('tag_id');

            $table->primary(['question_id', 'tag_id']);
            $table->foreign('question_id')->references('id')->on('quiz_questions')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('quiz_tags')->cascadeOnDelete();
            $table->index('tag_id', 'quiz_question_tag_tag_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_question_tag');
        Schema::dropIfExists('quiz_question_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quiz_tags');
        Schema::dropIfExists('quiz_question_categories');
    }
};
