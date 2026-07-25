<?php

use App\Database\Schema\MigrationColumnHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'messages_locked')) {
                $table->boolean('messages_locked')->default(false);
            }
            if (! Schema::hasColumn('conversations', 'messages_locked_until')) {
                $table->timestamp('messages_locked_until')->nullable();
            }
            if (! Schema::hasColumn('conversations', 'lock_schedule')) {
                MigrationColumnHelpers::jsonColumn($table, 'lock_schedule', nullable: true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            foreach (['messages_locked', 'messages_locked_until', 'lock_schedule'] as $col) {
                if (Schema::hasColumn('conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
