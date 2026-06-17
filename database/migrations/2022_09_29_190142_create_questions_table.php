<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('english_title');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
//            $table->foreign('parent_id')->references('id')->on('question_categories')->onUpdate('cascade');
            $table->text('description')->nullable();
            $table->text('icon')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('question_categories')->onUpdate('cascade');
            $table->string('subject');
            $table->string('slug');
            $table->text('question');
            $table->text('meta_keywords')->nullable();
            $table->unsignedBigInteger('best_answer')->nullable();
            $table->boolean('publish')->default(1);
            $table->boolean('is_private')->default(false);
            MigrationColumnHelpers::jsonColumn($table, 'allowed_user_ids', nullable: true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_categories');
    }
};
