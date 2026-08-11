<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make Saved Messages a first-class owned self-chat:
 * - backfill owner_id from the sole participant
 * - enforce at most one saved conversation per owner
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasColumn('conversations', 'owner_id')) {
            return;
        }

        // Backfill owner_id for legacy saved rows (single participant).
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("
                UPDATE conversations c
                SET owner_id = cu.user_id
                FROM conversation_user cu
                WHERE c.type = 'saved'
                  AND c.owner_id IS NULL
                  AND cu.conversation_id = c.id
                  AND (
                    SELECT COUNT(*) FROM conversation_user cu2
                    WHERE cu2.conversation_id = c.id
                  ) = 1
            ");
        } else {
            $rows = DB::table('conversations')
                ->where('type', 'saved')
                ->whereNull('owner_id')
                ->pluck('id');
            foreach ($rows as $conversationId) {
                $userIds = DB::table('conversation_user')
                    ->where('conversation_id', $conversationId)
                    ->pluck('user_id');
                if ($userIds->count() === 1) {
                    DB::table('conversations')
                        ->where('id', $conversationId)
                        ->update(['owner_id' => $userIds->first()]);
                }
            }
        }

        // Partial unique: one Saved Messages chat per owner.
        // MySQL cannot express partial unique indexes safely here (owner_id is
        // also used for many groups/channels), so runtime lockForUpdate protects.
        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS conversations_saved_owner_unique
                ON conversations (owner_id)
                WHERE type = \'saved\' AND owner_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS conversations_saved_owner_unique');
        } catch (\Throwable $e) {
            // MySQL uses different DROP INDEX syntax.
            try {
                Schema::table('conversations', function ($table) {
                    $table->dropIndex('conversations_saved_owner_unique');
                });
            } catch (\Throwable $e2) {
                // ignore
            }
        }
    }
};
