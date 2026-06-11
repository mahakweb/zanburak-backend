<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('messenger_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('enter_to_send')->default(true);     // Enter sends vs. newline
            $table->boolean('quote_with_title')->default(true);  // default quote style
            $table->string('wallpaper', 40)->nullable();         // chat background preset
            $table->string('theme', 20)->nullable();             // system | dark | light (synced)
            $table->string('locale', 5)->nullable();             // fa | en (synced)
            // Privacy: control what other users can see about me.
            $table->boolean('show_online')->default(true);       // broadcast my online status
            $table->boolean('show_last_seen')->default(true); // expose my last_seen time
            $table->boolean('show_phone')->default(false); // expose my mobile in profile
            $table->boolean('show_email')->default(false);     // expose my email in profile
            $table->boolean('forward_tap_to_chat')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messenger_settings');
    }
};
