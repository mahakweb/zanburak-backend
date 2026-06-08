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
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messenger_settings');
    }
};
