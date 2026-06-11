<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('message_pins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('message_id');
            // The participant this pin is visible to.
            $table->unsignedBigInteger('user_id');
            // Who performed the pin (may differ from the beneficiary).
            $table->unsignedBigInteger('pinned_by_id')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index(['conversation_id', 'user_id']);

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pinned_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_pins');
    }
};
