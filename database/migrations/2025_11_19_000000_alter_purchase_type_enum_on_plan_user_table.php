<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Extend enum values for purchase_type on plan_user pivot table
        DB::statement("
            ALTER TABLE `plan_user`
            MODIFY COLUMN `purchase_type`
            ENUM('online', 'wallet', 'gift', 'manual') NOT NULL DEFAULT 'online'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to the original enum definition
        DB::statement("
            ALTER TABLE `plan_user`
            MODIFY COLUMN `purchase_type`
            ENUM('online', 'wallet') NOT NULL DEFAULT 'online'
        ");
    }
};


