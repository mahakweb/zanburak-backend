<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('messenger_settings', 'auto_download_photos')) {
                $table->boolean('auto_download_photos')->default(false)->after('forward_tap_to_chat');
            }
            if (! Schema::hasColumn('messenger_settings', 'auto_download_videos')) {
                $table->boolean('auto_download_videos')->default(false)->after('auto_download_photos');
            }
            if (! Schema::hasColumn('messenger_settings', 'auto_download_files')) {
                $table->boolean('auto_download_files')->default(false)->after('auto_download_videos');
            }
            if (! Schema::hasColumn('messenger_settings', 'auto_download_voice')) {
                $table->boolean('auto_download_voice')->default(false)->after('auto_download_files');
            }
            if (! Schema::hasColumn('messenger_settings', 'auto_download_audio')) {
                $table->boolean('auto_download_audio')->default(false)->after('auto_download_voice');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            foreach ([
                'auto_download_photos',
                'auto_download_videos',
                'auto_download_files',
                'auto_download_voice',
                'auto_download_audio',
            ] as $col) {
                if (Schema::hasColumn('messenger_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
