<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_media', function (Blueprint $table) {
            $table->foreignId('canonical_media_id')
                ->nullable()
                ->after('id')
                ->constrained('messenger_media')
                ->nullOnDelete();
            $table->string('hls_path', 500)->nullable()->after('cover_path');
            $table->string('hls_status', 24)->default('none')->after('hls_path'); // none|pending|processing|ready|failed|skipped
            $table->string('processing_status', 24)->default('ready')->after('hls_status'); // pending|processing|ready|failed
            $table->json('variants')->nullable()->after('processing_status');
            $table->json('metadata')->nullable()->after('variants');
            $table->string('etag', 64)->nullable()->after('sha256');

            $table->index(['hls_status', 'kind']);
            $table->index(['processing_status', 'kind']);
            $table->index('canonical_media_id');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_media_id');
            $table->dropIndex(['hls_status', 'kind']);
            $table->dropIndex(['processing_status', 'kind']);
            $table->dropColumn([
                'hls_path',
                'hls_status',
                'processing_status',
                'variants',
                'metadata',
                'etag',
            ]);
        });
    }
};
