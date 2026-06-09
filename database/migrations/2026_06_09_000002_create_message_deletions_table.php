<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user "delete for me": a message hidden only from a single user's view.
 * "Delete for everyone" still uses the soft-delete on the messages table.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('message_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['message_id', 'user_id'], 'message_deletions_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_deletions');
    }
};
