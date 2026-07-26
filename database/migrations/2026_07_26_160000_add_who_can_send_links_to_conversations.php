<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'who_can_send_links')) {
                $table->string('who_can_send_links', 20)->default('all')->after('who_can_edit_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'who_can_send_links')) {
                $table->dropColumn('who_can_send_links');
            }
        });
    }
};
