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

        $permissionId = DB::table('permissions')->where('name', 'users.update')->value('id');
        if (! $permissionId) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ([
            'api.admin.user.bank-accounts.update',
            'api.admin.user.bank-accounts.destroy',
            'api.admin.bank-accounts.inspect',
        ] as $route) {
            $rows[] = [
                'permission_id' => $permissionId,
                'route_name' => $route,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('permission_routes')->upsert($rows, ['permission_id', 'route_name'], ['updated_at']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permission_routes')) {
            return;
        }
        DB::table('permission_routes')
            ->where('route_name', 'api.admin.bank-accounts.inspect')
            ->where('permission_id', DB::table('permissions')->where('name', 'users.update')->value('id'))
            ->delete();
        DB::table('permission_routes')->whereIn('route_name', [
            'api.admin.user.bank-accounts.update',
            'api.admin.user.bank-accounts.destroy',
        ])->delete();
    }
};
