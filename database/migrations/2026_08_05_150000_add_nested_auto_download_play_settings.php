<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('messenger_settings', 'auto_download')) {
                $table->json('auto_download')->nullable()->after('forward_tap_to_chat');
            }
            if (! Schema::hasColumn('messenger_settings', 'auto_play')) {
                $table->json('auto_play')->nullable()->after('auto_download');
            }
        });

        // Migrate legacy flat booleans into nested JSON when present.
        if (Schema::hasColumn('messenger_settings', 'auto_download_photos')) {
            $rows = DB::table('messenger_settings')->select([
                'id',
                'auto_download_photos',
                'auto_download_videos',
                'auto_download_files',
                'auto_download_voice',
                'auto_download_audio',
            ])->get();

            foreach ($rows as $row) {
                $media = [
                    'photos' => (bool) ($row->auto_download_photos ?? false),
                    'videos' => (bool) ($row->auto_download_videos ?? false),
                    'files' => (bool) ($row->auto_download_files ?? false),
                    'voice' => (bool) ($row->auto_download_voice ?? false),
                    'audio' => (bool) ($row->auto_download_audio ?? false),
                ];
                DB::table('messenger_settings')->where('id', $row->id)->update([
                    'auto_download' => json_encode([
                        'private' => $media,
                        'groups' => $media,
                        'channels' => $media,
                    ]),
                    'auto_play' => json_encode([
                        'gifs' => true,
                        'videos' => false,
                    ]),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            foreach (['auto_play', 'auto_download'] as $col) {
                if (Schema::hasColumn('messenger_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
