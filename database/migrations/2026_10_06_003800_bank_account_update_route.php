<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permission_routes') || ! Schema::hasTable('permissions')) {
            return;
        }
        $permissionId = DB::table('permissions')->where('name', 'bank_accounts.manage')->value('id');
        if (! $permissionId) {
            return;
        }
        $now = now();
        DB::table('permission_routes')->upsert([[
            'permission_id' => $permissionId,
            'route_name' => 'api.admin.bank-accounts.update',
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['permission_id', 'route_name'], ['updated_at']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permission_routes')) {
            return;
        }
        DB::table('permission_routes')->where('route_name', 'api.admin.bank-accounts.update')->delete();
    }
};
