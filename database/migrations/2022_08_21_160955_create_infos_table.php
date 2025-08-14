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
        Schema::create('infos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
                $table->string('birth_date',255)->nullable();
                $table->string('job',255)->nullable();
                $table->text('about')->nullable();
                $table->string('website')->nullable();
                $table->string('github')->nullable();
                $table->string('linkedin')->nullable();
                $table->string('telegram')->nullable();
                $table->string('instagram')->nullable();
                $table->string('twitter')->nullable();
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
        Schema::dropIfExists('infos');
    }
};
