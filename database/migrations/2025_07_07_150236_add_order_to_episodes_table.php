<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes', 'order')) {
                $table->unsignedInteger('order')->default(1)->after('section_id');
            }

            // $table->unique(['section_id', 'order'], 'section_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('episodes', function (Blueprint $table) {

            // $table->dropForeign(['section_id']);

            // $table->dropUnique('section_order_unique');

            // $table->foreign('section_id')
            //     ->references('id')->on('sections')
            //     ->onDelete('cascade');

            if (Schema::hasColumn('episodes', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};
