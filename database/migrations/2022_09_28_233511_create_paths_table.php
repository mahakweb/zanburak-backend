<?php

use App\Database\Schema\MigrationColumnHelpers;
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
        Schema::create('paths', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('english_title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('meta_keywords')->nullable();
            MigrationColumnHelpers::jsonColumn($table, 'faqs', nullable: true);
            $table->string('icon', 255)->nullable();
            $table->string('trailer', 255)->nullable();
            $table->string('poster', 255)->nullable();
            $table->string('short_description', 255)->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paths');
    }
};
