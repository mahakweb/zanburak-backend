<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            // null = inherit global users_default_access; true/false = override
            $table->boolean('access_enabled')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            $table->dropColumn('access_enabled');
        });
    }
};
