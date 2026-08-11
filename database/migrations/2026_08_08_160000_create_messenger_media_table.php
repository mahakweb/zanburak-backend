<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 16); // photo|video|audio|voice|file|sticker
            $table->string('disk', 32)->default('static');
            $table->string('path', 500);
            $table->string('thumb_path', 500)->nullable();
            $table->string('cover_path', 500)->nullable();
            $table->boolean('is_private')->default(true);
            $table->boolean('is_encrypted')->default(false);
            $table->string('url', 1000)->nullable(); // public/static URL when intentionally public
            $table->string('original_name', 255)->nullable();
            $table->string('extension', 32)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('duration', 10, 1)->nullable();
            $table->unsignedInteger('ref_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['disk', 'path']);
            $table->index(['sha256', 'size_bytes']);
            $table->index(['user_id', 'kind']);
            $table->index(['kind', 'created_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('media_id')
                ->nullable()
                ->after('type')
                ->constrained('messenger_media')
                ->nullOnDelete();
            $table->index('media_id');
        });

        Schema::table('messenger_stickers', function (Blueprint $table) {
            $table->foreignId('media_id')
                ->nullable()
                ->after('pack_id')
                ->constrained('messenger_media')
                ->nullOnDelete();
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_stickers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
        });
        Schema::dropIfExists('messenger_media');
    }
};
