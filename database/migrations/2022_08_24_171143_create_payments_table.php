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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            // $table->enum('type', ['wallet', 'course', 'vip', 'path']);
            // $table->json('payment_info')->nullable();
            $table->string('driver')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('resnumber');
            $table->bigInteger('amount');
            $table->bigInteger('discount_amount')->nullable();
            $table->string('discount_code')->nullable();
            $table->timestamp('visited_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->unsignedBigInteger('verified_by_admin')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('payments');
    }
};
