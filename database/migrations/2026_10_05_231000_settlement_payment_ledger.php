<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE settlements ALTER COLUMN teacher_id DROP NOT NULL');
        DB::statement('ALTER TABLE settlement_items ALTER COLUMN teacher_id DROP NOT NULL');

        Schema::table('settlements', function (Blueprint $table) {
            $table->unsignedBigInteger('active_covered_payment_id')->nullable()->unique();
            $table->foreign('active_covered_payment_id')
                ->references('id')
                ->on('payments')
                ->nullOnDelete();
        });

        if (! Schema::hasTable('permission_routes') || ! Schema::hasTable('permissions')) {
            return;
        }

        $routes = [
            'api.admin.settlements.ledger' => ['settlements.view', 'settlements.view.own', 'settlements.view.any'],
            'api.admin.settlements.payments.show' => ['settlements.view', 'settlements.view.own', 'settlements.view.any'],
            'api.admin.settlements.payments.mark' => ['settlements.change_status'],
            'api.admin.settlements.payments.receipt' => ['settlements.view_receipt', 'settlements.view_receipt.any', 'settlements.view_receipt.own'],
        ];

        $names = collect($routes)->flatten()->unique()->values()->all();
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
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique(['active_covered_payment_id']);
            $table->dropForeign(['active_covered_payment_id']);
            $table->dropColumn('active_covered_payment_id');
        });
    }
};
