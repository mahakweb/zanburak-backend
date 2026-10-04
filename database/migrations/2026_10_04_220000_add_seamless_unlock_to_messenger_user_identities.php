<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authenticated multi-device unlock envelope.
 *
 * Client uploads an opaque multi-device secret (MDS). Server stores it encrypted
 * at rest (APP_KEY) and returns it only to the authenticated identity owner so
 * a brand-new login can restore User Identity without a typed passphrase or an
 * online sibling / peer message.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messenger_user_identities')) {
            return;
        }
        if (Schema::hasColumn('messenger_user_identities', 'seamless_unlock')) {
            return;
        }

        Schema::table('messenger_user_identities', function (Blueprint $table) {
            $table->mediumText('seamless_unlock')->nullable()->after('backup_version');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('messenger_user_identities')) {
            return;
        }
        if (! Schema::hasColumn('messenger_user_identities', 'seamless_unlock')) {
            return;
        }

        Schema::table('messenger_user_identities', function (Blueprint $table) {
            $table->dropColumn('seamless_unlock');
        });
    }
};
