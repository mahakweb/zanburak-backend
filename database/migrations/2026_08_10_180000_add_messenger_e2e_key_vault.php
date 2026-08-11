<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity-wrapped conversation-key vault.
 *
 * Opaque ciphertexts only — server never sees plaintext keys.
 * Each entry is ECDH-wrapped to the account User Identity agreement key so any
 * newly authorized device that holds that identity can recover historical keys
 * without waiting for a sibling/peer to come online or send a new message.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messenger_e2e_key_vault')) {
            return;
        }

        Schema::create('messenger_e2e_key_vault', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->unsignedInteger('key_version');
            $table->string('sender_device_id', 64);
            $table->text('ciphertext');
            $table->timestamps();

            $table->unique(['user_id', 'conversation_id', 'key_version'], 'e2e_vault_user_conv_kid');
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_e2e_key_vault');
    }
};
