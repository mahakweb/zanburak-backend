<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paths', function (Blueprint $table) {
            $table->json('faqs')->nullable()->after('icon');
            $table->string('trailer', 255)->nullable()->after('icon');
            $table->string('poster', 255)->after('icon');
            $table->string('short_description', 255)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paths', function (Blueprint $table) {
            $table->dropColumn('short_description');
            $table->dropColumn('poster');
            $table->dropColumn('trailer');
            $table->dropColumn('faqs');
        });
    }
};
