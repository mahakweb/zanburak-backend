<?php

namespace App\Database\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-database column helpers for migrations.
 */
class MigrationColumnHelpers
{
    /**
     * JSON on MySQL, JSONB on PostgreSQL (better indexing and operators).
     */
    public static function jsonColumn(Blueprint $table, string $name, bool $nullable = false): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $column = $table->jsonb($name);
        } else {
            $column = $table->json($name);
        }

        if ($nullable) {
            $column->nullable();
        }
    }
}
