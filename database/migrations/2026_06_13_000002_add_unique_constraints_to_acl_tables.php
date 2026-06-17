<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL requires a unique/exclusion constraint for ON CONFLICT (upsert).
 * PermissionsAndRolesSeeder upserts on roles.name and permissions.name.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUnique('permissions', 'name');
        $this->ensureUnique('roles', 'name');
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('roles', 'name');
        $this->dropUniqueIfExists('permissions', 'name');
    }

    private function ensureUnique(string $table, string $column): void
    {
        $index = "{$table}_{$column}_unique";

        if (Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->unique($column);
        });
    }

    private function dropUniqueIfExists(string $table, string $column): void
    {
        $index = "{$table}_{$column}_unique";

        if (! Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropUnique([$column]);
        });
    }
};
