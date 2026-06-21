<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('canvas_width')->default(1123)->after('orientation');
            $table->unsignedSmallInteger('canvas_height')->default(794)->after('canvas_width');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_fonts');

        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn(['canvas_width', 'canvas_height']);
        });
    }
};
