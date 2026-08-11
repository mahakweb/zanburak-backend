<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messenger_settings', function (Blueprint $table) {
            $table->string('privacy_last_seen', 16)->default('everybody')->after('show_email');
            $table->string('privacy_online', 16)->default('everybody')->after('privacy_last_seen');
            $table->string('privacy_profile_photo', 16)->default('everybody')->after('privacy_online');
            $table->string('privacy_bio', 16)->default('everybody')->after('privacy_profile_photo');
            $table->string('privacy_phone', 16)->default('nobody')->after('privacy_bio');
        });

        // Backfill from legacy boolean columns.
        if (Schema::hasColumn('messenger_settings', 'show_last_seen')) {
            DB::table('messenger_settings')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('messenger_settings')->where('id', $row->id)->update([
                        'privacy_last_seen' => $row->show_last_seen ? 'everybody' : 'nobody',
                        'privacy_online' => $row->show_online ? 'everybody' : 'nobody',
                        'privacy_phone' => $row->show_phone ? 'everybody' : 'nobody',
                    ]);
                }
            });
        }

        Schema::create('privacy_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('setting_key', 32); // last_seen|online|profile_photo|bio|phone
            $table->unsignedBigInteger('target_user_id');
            $table->string('rule', 8); // allow|deny
            $table->timestamps();

            $table->unique(['user_id', 'setting_key', 'target_user_id'], 'privacy_exceptions_unique');
            $table->index(['user_id', 'setting_key', 'rule'], 'privacy_exceptions_lookup');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('synced_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('phone_normalized', 20); // +98XXXXXXXXXX
            $table->string('name')->nullable();
            $table->unsignedBigInteger('matched_user_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'phone_normalized'], 'synced_contacts_user_phone_unique');
            $table->index(['matched_user_id'], 'synced_contacts_matched');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('matched_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_contacts');
        Schema::dropIfExists('privacy_exceptions');

        Schema::table('messenger_settings', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_last_seen',
                'privacy_online',
                'privacy_profile_photo',
                'privacy_bio',
                'privacy_phone',
            ]);
        });
    }
};
