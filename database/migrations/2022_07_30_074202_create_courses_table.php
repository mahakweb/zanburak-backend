<?php

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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->foreign('teacher_id')->references('id')->on('users')->onUpdate('cascade');
            $table->string('title', 255);
            $table->string('english_title', 255);
            $table->string('slug');
            $table->string('short_description', 400)->nullable();
            $table->text('description');
            $table->text('meta_keywords')->nullable();
            $table->string('total_time')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('publish')->default(0);
            $table->boolean('certificate_enabled')->default(false);
            $table->unsignedBigInteger('certificate_template_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')->references('id')->on('statuses')->onUpdate('cascade');
            $table->unsignedBigInteger('level_id')->nullable();
            $table->foreign('level_id')->references('id')->on('levels')->onUpdate('cascade');
            $table->enum('type', ['free', 'cash', 'cash-vip']);
            $table->string('disk')->nullable();
            $table->text('trailer')->nullable();
            $table->text('poster')->nullable();
            $table->text('attached_file')->nullable();
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
        Schema::dropIfExists('courses');
    }
};
