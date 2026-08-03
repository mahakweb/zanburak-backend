<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messenger_one_time_prekeys')
            && ! Schema::hasColumn('messenger_one_time_prekeys', 'signature')) {
            Schema::table('messenger_one_time_prekeys', function (Blueprint $table) {
                $table->text('signature')->nullable()->after('public_key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('messenger_one_time_prekeys')
            && Schema::hasColumn('messenger_one_time_prekeys', 'signature')) {
            Schema::table('messenger_one_time_prekeys', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
