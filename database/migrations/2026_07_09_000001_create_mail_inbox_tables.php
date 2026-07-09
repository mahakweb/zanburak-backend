<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('address');
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('last_synced_uid')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_account_id')->constrained('mail_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('imap_uid');
            $table->string('message_id')->nullable()->index();
            $table->string('in_reply_to')->nullable();
            $table->text('references')->nullable();
            $table->string('thread_id')->nullable()->index();
            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->json('to_addresses')->nullable();
            $table->json('cc_addresses')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mail_account_id', 'imap_uid']);
            $table->index(['mail_account_id', 'is_read', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_messages');
        Schema::dropIfExists('mail_accounts');
    }
};
