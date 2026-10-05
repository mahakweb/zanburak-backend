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

        $routes = [
            'api.admin.payments.chart' => ['payments.view', 'payments.view.own', 'payments.view.any'],
            'api.admin.settlements.chart' => ['settlements.view', 'settlements.view.own', 'settlements.view.any'],
        ];
        $names = collect($routes)->flatten()->unique()->all();
        $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id', 'name');
        $now = now();
        $rows = [];
        foreach ($routes as $routeName => $permissionNames) {
            foreach ($permissionNames as $name) {
                if (! isset($permissionIds[$name])) {
                    continue;
                }
                $rows[] = [
                    'permission_id' => $permissionIds[$name],
                    'route_name' => $routeName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($rows !== []) {
            DB::table('permission_routes')->upsert($rows, ['permission_id', 'route_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permission_routes')) {
            return;
        }
        DB::table('permission_routes')->whereIn('route_name', [
            'api.admin.payments.chart',
            'api.admin.settlements.chart',
        ])->delete();
    }
};
