<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('bank_code', 3);
            $table->string('bank_name');
            $table->string('number', 34);
            $table->string('owner_name', 80);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'number']);
            $table->index(['user_id', 'is_default']);
        });

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        DB::table('permissions')->upsert([
            [
                'name' => 'bank_accounts.manage',
                'label' => 'مدیریت حساب، شبا و کارت بانکی خود',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['name'], ['label', 'updated_at']);

        $permissionId = DB::table('permissions')->where('name', 'bank_accounts.manage')->value('id');
        if (! $permissionId || ! Schema::hasTable('permission_role') || ! Schema::hasTable('roles')) {
            return;
        }

        $dashboardIds = DB::table('permissions')
            ->whereIn('name', ['dashboard.view', 'dashboard.view.own', 'dashboard.view.any'])
            ->pluck('id');
        $roleIds = DB::table('permission_role')
            ->whereIn('permission_id', $dashboardIds)
            ->pluck('role_id')
            ->unique();

        $rows = $roleIds->map(fn ($roleId) => [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if ($rows !== []) {
            DB::table('permission_role')->upsert($rows, ['role_id', 'permission_id'], ['updated_at']);
        }

        if (! Schema::hasTable('permission_routes')) {
            return;
        }

        $routes = [
            'api.admin.bank-accounts.index',
            'api.admin.bank-accounts.inspect',
            'api.admin.bank-accounts.store',
            'api.admin.bank-accounts.default',
            'api.admin.bank-accounts.destroy',
        ];
        $routeRows = [];
        foreach ($routes as $routeName) {
            $routeRows[] = [
                'permission_id' => $permissionId,
                'route_name' => $routeName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('permission_routes')->upsert($routeRows, ['permission_id', 'route_name'], ['updated_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bank_accounts');
        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')->where('name', 'bank_accounts.manage')->pluck('id');
            if ($ids->isNotEmpty()) {
                if (Schema::hasTable('permission_role')) {
                    DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
                }
                if (Schema::hasTable('permission_routes')) {
                    DB::table('permission_routes')->whereIn('permission_id', $ids)->delete();
                }
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }
    }
};
