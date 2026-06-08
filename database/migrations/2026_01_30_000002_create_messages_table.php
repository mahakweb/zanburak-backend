<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->string('client_id', 64)->nullable(); // optimistic UI + idempotent sends
            $table->text('body');
            $table->string('type', 20)->default('text');
            $table->unsignedBigInteger('reply_to_id')->nullable();       // quoted/replied message
            $table->boolean('reply_show_title')->default(true);          // quote with vs. without title
            $table->unsignedBigInteger('forwarded_from_user_id')->nullable(); // original author when forwarded
            $table->timestamp('read_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reply_to_id')->references('id')->on('messages')->nullOnDelete();
            $table->foreign('forwarded_from_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['conversation_id', 'id'], 'messages_conversation_id_id_index');
            $table->index(['conversation_id', 'user_id', 'read_at'], 'messages_unread_index');
            $table->unique(['conversation_id', 'user_id', 'client_id'], 'messages_client_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
