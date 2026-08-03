<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('type');
            }
            if (! Schema::hasColumn('conversations', 'e2e_key_version')) {
                $table->unsignedInteger('e2e_key_version')->default(0)->after('is_encrypted');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('type');
            }
            if (! Schema::hasColumn('messages', 'sender_device_id')) {
                $table->string('sender_device_id', 64)->nullable()->after('is_encrypted');
            }
            if (! Schema::hasColumn('messages', 'e2e')) {
                $table->json('e2e')->nullable()->after('sender_device_id');
            }
        });

        Schema::create('messenger_crypto_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 64);
            $table->string('label', 120)->nullable();
            $table->text('identity_public_key');
            $table->unsignedInteger('signed_prekey_id')->default(1);
            $table->text('signed_prekey_public');
            $table->text('signed_prekey_signature');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('messenger_one_time_prekeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_row_id')->constrained('messenger_crypto_devices')->cascadeOnDelete();
            $table->unsignedInteger('prekey_id');
            $table->text('public_key');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->unique(['device_row_id', 'prekey_id']);
            $table->index(['device_row_id', 'consumed_at']);
        });

        Schema::create('messenger_e2e_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_device_id', 64);
            $table->string('recipient_device_id', 64);
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('key_version');
            $table->text('ciphertext');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_device_id', 'consumed_at']);
            $table->index(['conversation_id', 'recipient_device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_e2e_packages');
        Schema::dropIfExists('messenger_one_time_prekeys');
        Schema::dropIfExists('messenger_crypto_devices');

        Schema::table('messages', function (Blueprint $table) {
            foreach (['e2e', 'sender_device_id', 'is_encrypted'] as $col) {
                if (Schema::hasColumn('messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            foreach (['e2e_key_version', 'is_encrypted'] as $col) {
                if (Schema::hasColumn('conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
