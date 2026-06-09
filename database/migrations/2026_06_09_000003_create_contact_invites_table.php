<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks invitations sent to non-registered identifiers (email / phone),
 * so a user gets nudged to register and we can avoid spamming the same target.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('contact_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inviter_id');
            $table->string('identifier');          // email or phone the invite was sent to
            $table->string('channel', 10);         // email | sms
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('send_count')->default(0);
            $table->timestamps();

            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('identifier');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_invites');
    }
};
