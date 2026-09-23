<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_channel_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('telegram_enabled')->default(true);
            $table->boolean('site_enabled')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_sms')->default(true);
            $table->boolean('notify_telegram')->default(true);
            $table->boolean('notify_site')->default(true);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_email', 'notify_sms', 'notify_telegram', 'notify_site']);
        });

        Schema::dropIfExists('notification_channel_settings');
    }
};
