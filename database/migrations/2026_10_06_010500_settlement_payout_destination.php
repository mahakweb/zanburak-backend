<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->string('payout_kind', 16)->nullable()->after('account_holder');
            $table->string('payout_number', 34)->nullable()->after('payout_kind');
            $table->string('payout_bank_code', 3)->nullable()->after('payout_number');
            $table->foreignId('user_bank_account_id')->nullable()->after('payout_bank_code')->constrained('user_bank_accounts')->nullOnDelete();
        });

        if (! Schema::hasTable('permission_routes') || ! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('name', 'settlements.change_status')->value('id');
        if (! $permissionId) {
            return;
        }

        $now = now();
        DB::table('permission_routes')->upsert([[
            'permission_id' => $permissionId,
            'route_name' => 'api.admin.settlements.payments.payout-options',
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['permission_id', 'route_name'], ['updated_at']);
    }

    public function down(): void
    {
        if (Schema::hasTable('permission_routes')) {
            DB::table('permission_routes')->where('route_name', 'api.admin.settlements.payments.payout-options')->delete();
        }

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_bank_account_id');
            $table->dropColumn(['payout_kind', 'payout_number', 'payout_bank_code']);
        });
    }
};
