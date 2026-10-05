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

        $now = now();
        $rows = [];
        foreach (['users.view'] as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            if (! $permissionId) {
                continue;
            }
            $rows[] = [
                'permission_id' => $permissionId,
                'route_name' => 'api.admin.user.bank-accounts',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows === []) {
            return;
        }
        DB::table('permission_routes')->upsert($rows, ['permission_id', 'route_name'], ['updated_at']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permission_routes')) {
            return;
        }
        DB::table('permission_routes')->where('route_name', 'api.admin.user.bank-accounts')->delete();
    }
};
