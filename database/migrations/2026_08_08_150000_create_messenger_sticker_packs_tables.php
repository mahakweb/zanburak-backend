<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_sticker_packs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 120);
            $table->string('title_fa', 120)->nullable();
            $table->string('icon', 32)->default('⭐');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_builtin')->default(false);
            $table->unsignedInteger('sticker_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('messenger_stickers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pack_id')->constrained('messenger_sticker_packs')->cascadeOnDelete();
            $table->string('emoji', 32)->nullable();
            $table->string('kind', 16)->default('image'); // image | emoji
            $table->string('path', 500)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('mime', 80)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['pack_id', 'sort_order']);
            $table->index(['emoji']);
        });

        Schema::create('messenger_user_sticker_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pack_id')->constrained('messenger_sticker_packs')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'pack_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_user_sticker_packs');
        Schema::dropIfExists('messenger_stickers');
        Schema::dropIfExists('messenger_sticker_packs');
    }
};
