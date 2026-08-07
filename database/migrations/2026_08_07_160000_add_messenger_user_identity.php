<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account-level User Identity (stable across devices) + opaque sibling
 * identity-transfer packages. Device keys remain in messenger_crypto_devices.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messenger_user_identities')) {
            Schema::create('messenger_user_identities', function (Blueprint $table) {
                $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
                $table->text('signing_public');
                $table->text('agreement_public');
                // Opaque recovery blob (client-encrypted). Server never sees plaintext privates.
                $table->mediumText('encrypted_backup')->nullable();
                $table->string('backup_salt', 128)->nullable();
                $table->unsignedInteger('backup_version')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('messenger_identity_packages')) {
            Schema::create('messenger_identity_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('sender_device_id', 64);
                $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('recipient_device_id', 64);
                $table->text('ciphertext');
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();

                $table->index(['recipient_user_id', 'recipient_device_id', 'consumed_at'], 'mip_recipient_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_identity_packages');
        Schema::dropIfExists('messenger_user_identities');
    }
};
