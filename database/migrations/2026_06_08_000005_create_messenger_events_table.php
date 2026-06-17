<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable, append-only realtime event log. This is the canonical source of truth
 * for the realtime stream so the messenger works fully even when Redis is absent.
 * The auto-increment id doubles as the per-user stream cursor (Last-Event-ID).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('messenger_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');        // recipient
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('type', 40);                   // message.new, message.updated, ...
            MigrationColumnHelpers::jsonColumn($table, 'payload', nullable: true);
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'id'], 'messenger_events_user_cursor_index');
            $table->index('created_at', 'messenger_events_created_at_index');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_events');
    }
};
