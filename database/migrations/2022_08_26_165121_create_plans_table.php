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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('english_title');
            $table->integer('period_time');
            $table->bigInteger('price');
            $table->string('icon')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('popular')->default(0);
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->timestamps();
        });


        Schema::create('plan_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->foreign('plan_id')->references('id')->on('plans')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreign('payment_id')->references('id')->on('payments')->onUpdate('cascade');
            $table->bigInteger('price')->nullable();
            $table->timestamp('expired_at')->useCurrent();
            $table->enum('purchase_type', ['online', 'wallet', 'gift', 'manual'])->default('online');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('plan_user');
        Schema::dropIfExists('plans');
    }
};
