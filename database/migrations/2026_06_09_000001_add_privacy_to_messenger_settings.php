<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            // Privacy: control what other users can see about me.
            $table->boolean('show_online')->default(true)->after('locale');       // broadcast my online status
            $table->boolean('show_last_seen')->default(true)->after('show_online'); // expose my last_seen time
            $table->boolean('show_phone')->default(false)->after('show_last_seen'); // expose my mobile in profile
            $table->boolean('show_email')->default(false)->after('show_phone');     // expose my email in profile
        });
    }

    public function down()
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            $table->dropColumn(['show_online', 'show_last_seen', 'show_phone', 'show_email']);
        });
    }
};
