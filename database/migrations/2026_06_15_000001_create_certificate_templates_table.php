<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('background_image')->nullable();
            $table->string('logo_image')->nullable();
            $table->string('signature_image')->nullable();
            $table->string('orientation')->default('landscape'); // landscape | portrait
            $table->unsignedSmallInteger('canvas_width')->default(1123);
            $table->unsignedSmallInteger('canvas_height')->default(794);
            $table->json('layout')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('certificate_fonts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->string('format', 10)->default('woff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('certificate_template_id')
                ->references('id')
                ->on('certificate_templates')
                ->nullOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('certificate_template_id')
                ->references('id')
                ->on('certificate_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['certificate_template_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['certificate_template_id']);
        });

        Schema::dropIfExists('certificate_fonts');
        Schema::dropIfExists('certificate_templates');
    }
};
