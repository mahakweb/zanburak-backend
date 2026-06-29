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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses', 'id')->onUpdate('cascade');
            $table->uuid('uuid')->unique();
            $table->string('serial_number', 32)->nullable()->unique();
            $table->unsignedBigInteger('certificate_template_id')->nullable();
            $table->string('verification_token', 64)->nullable();
            $table->string('user_name', 255);
            $table->string('course_title', 255);
            $table->string('instructor_name')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->integer('time_completed')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->string('status', 20)->default('issued'); // pending | issued | revoked
            $table->string('pdf_path')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('revoked_at')->nullable();
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
        Schema::dropIfExists('certificates');
    }
};
