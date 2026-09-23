<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $constraints = DB::select("
                SELECT con.conname AS name
                FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                INNER JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                WHERE rel.relname = 'payments'
                  AND nsp.nspname = current_schema()
                  AND con.contype = 'c'
                  AND pg_get_constraintdef(con.oid) ILIKE '%payment_method%'
            ");

            foreach ($constraints as $constraint) {
                $name = str_replace('"', '', $constraint->name);
                DB::statement('ALTER TABLE payments DROP CONSTRAINT "'.$name.'"');
            }

            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('wallet', 'bank', 'wallet_bank'))");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(32) NOT NULL DEFAULT 'bank'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('wallet', 'bank'))");
    }
};
