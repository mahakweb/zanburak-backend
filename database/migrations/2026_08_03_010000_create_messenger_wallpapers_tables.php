<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('messenger_wallpapers')) {
            Schema::create('messenger_wallpapers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('path', 500);
                $table->string('url', 1000);
                $table->string('thumb_url', 1000)->nullable();
                $table->string('mime', 80)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->unsignedSmallInteger('width')->nullable();
                $table->unsignedSmallInteger('height')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('messenger_conversation_wallpapers')) {
            Schema::create('messenger_conversation_wallpapers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('wallpaper_id')->nullable()->index();
                MigrationColumnHelpers::jsonColumn($table, 'config', nullable: true);
                $table->boolean('for_both')->default(false);
                $table->unsignedBigInteger('shared_from_user_id')->nullable();
                $table->timestamps();

                $table->unique(['conversation_id', 'user_id'], 'msg_conv_wall_user_unique');
                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('wallpaper_id')->references('id')->on('messenger_wallpapers')->onDelete('set null');
            });
        }

        if (Schema::hasTable('messenger_settings') && ! Schema::hasColumn('messenger_settings', 'wallpaper_config')) {
            Schema::table('messenger_settings', function (Blueprint $table) {
                MigrationColumnHelpers::jsonColumn($table, 'wallpaper_config', nullable: true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('messenger_settings') && Schema::hasColumn('messenger_settings', 'wallpaper_config')) {
            Schema::table('messenger_settings', function (Blueprint $table) {
                $table->dropColumn('wallpaper_config');
            });
        }
        Schema::dropIfExists('messenger_conversation_wallpapers');
        Schema::dropIfExists('messenger_wallpapers');
    }
};
