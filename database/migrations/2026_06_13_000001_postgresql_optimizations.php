<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL-specific extensions, indexes, and type adjustments.
 *
 * This migration is a no-op on MySQL so the same migration set can still be
 * run against a legacy MySQL database during a gradual cutover.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');

        if (Schema::hasTable('users')) {
            DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext USING email::citext');
            DB::statement('ALTER TABLE users ALTER COLUMN username TYPE citext USING username::citext');
        }

        $jsonbIndexes = [
            ['paths', 'faqs'],
            ['plans', 'features'],
            ['missions', 'levels'],
            ['questions', 'allowed_user_ids'],
            ['messenger_events', 'payload'],
            ['discount_conditions', 'extra'],
            ['payment_attempts', 'request_payload'],
            ['payment_attempts', 'response_payload'],
            ['video_views', 'watched_times'],
        ];

        foreach ($jsonbIndexes as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $index = "{$table}_{$column}_gin";

            DB::statement("CREATE INDEX IF NOT EXISTS {$index} ON {$table} USING GIN ({$column})");
        }

        $btreeIndexes = [
            'users' => ['last_seen', 'active', 'created_at'],
            'payments' => ['status', 'paid_at', 'created_at', 'payment_method'],
            'messages' => ['created_at', 'deleted_at'],
            'views' => ['created_at', 'user_id'],
            'comments' => ['created_at', 'approved', 'user_id'],
            'course_user' => ['created_at', 'completed_at'],
            'user_logins' => ['logged_in_at', 'user_id'],
        ];

        foreach ($btreeIndexes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $index = "{$table}_{$column}_index";

                DB::statement("CREATE INDEX IF NOT EXISTS {$index} ON {$table} ({$column})");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $jsonbIndexes = [
            ['paths', 'faqs'],
            ['plans', 'features'],
            ['missions', 'levels'],
            ['questions', 'allowed_user_ids'],
            ['messenger_events', 'payload'],
            ['discount_conditions', 'extra'],
            ['payment_attempts', 'request_payload'],
            ['payment_attempts', 'response_payload'],
            ['video_views', 'watched_times'],
        ];

        foreach ($jsonbIndexes as [$table, $column]) {
            DB::statement("DROP INDEX IF EXISTS {$table}_{$column}_gin");
        }

        $btreeIndexes = [
            'users' => ['last_seen', 'active', 'created_at'],
            'payments' => ['status', 'paid_at', 'created_at', 'payment_method'],
            'messages' => ['created_at', 'deleted_at'],
            'views' => ['created_at', 'user_id'],
            'comments' => ['created_at', 'approved', 'user_id'],
            'course_user' => ['created_at', 'completed_at'],
            'user_logins' => ['logged_in_at', 'user_id'],
        ];

        foreach ($btreeIndexes as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("DROP INDEX IF EXISTS {$table}_{$column}_index");
            }
        }

        if (Schema::hasTable('users')) {
            DB::statement('ALTER TABLE users ALTER COLUMN email TYPE varchar(255) USING email::varchar');
            DB::statement('ALTER TABLE users ALTER COLUMN username TYPE varchar(255) USING username::varchar');
        }
    }
};
