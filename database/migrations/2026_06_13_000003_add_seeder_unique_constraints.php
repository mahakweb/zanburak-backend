<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL requires unique constraints on upsert conflict targets.
 * Seeders upsert on these natural keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUnique('levels', 'english_title');
        $this->ensureUnique('levels', 'slug');
        $this->ensureUnique('statuses', 'english_title');
        $this->ensureUnique('statuses', 'slug');
        $this->ensureUnique('plans', 'english_title');
        $this->ensureUnique('paths', 'slug');
        $this->ensureUnique('categories', 'slug');
        $this->ensureUnique('question_categories', 'slug');
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('question_categories', 'slug');
        $this->dropUniqueIfExists('categories', 'slug');
        $this->dropUniqueIfExists('paths', 'slug');
        $this->dropUniqueIfExists('plans', 'english_title');
        $this->dropUniqueIfExists('statuses', 'slug');
        $this->dropUniqueIfExists('statuses', 'english_title');
        $this->dropUniqueIfExists('levels', 'slug');
        $this->dropUniqueIfExists('levels', 'english_title');
    }

    private function ensureUnique(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

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
