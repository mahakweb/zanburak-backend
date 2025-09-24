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
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();

            $table->morphs('payable'); // payable_id, payable_type

            $table->bigInteger('price'); // original price of the item
            $table->bigInteger('discount_amount')->default(0); // discount amount applied to this item
            $table->string('discount_code')->nullable(); // discount code applied to this item
            $table->bigInteger('final_price')->nullable(); // price after discount for this item

            $table->timestamps();
        });


        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('transaction_id')->nullable();
            $table->string('attempt_reference')->nullable();
            $table->string('gateway')->nullable();
            $table->string('status')->nullable(); // pending, success, failed
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
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
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payment_items');
    }
};
