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
        Schema::create('mission_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('english_title');
            $table->string('slug');
            $table->timestamps();
        });


        Schema::create('missions', function (Blueprint $table) {
            $table->string('id');
            $table->string('title');
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('mission_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->text('icon');
            $table->text('description');
            $table->json('levels');
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->primary('id');
        });


        Schema::create('mission_user', function (Blueprint $table) {
            $table->id();
            $table->string('mission_id');
            $table->foreign('mission_id')->references('id')->on('missions')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('progress')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('mission_user');
        Schema::dropIfExists('missions');
        Schema::dropIfExists('mission_categories');
    }
};
