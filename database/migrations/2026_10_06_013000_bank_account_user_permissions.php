<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->whereIn('name', [
            'bank_accounts.view_users',
            'bank_accounts.update_users',
            'bank_accounts.delete_users',
        ])->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
    }
};
