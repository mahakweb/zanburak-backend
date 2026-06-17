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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title')->nullable();
            $table->enum('type', ['percent', 'fixed', 'free'])->default('percent');
            $table->integer('value')->nullable();

            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });



        Schema::create('discount_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['inclusion', 'exclusion']);
            $table->string('target_type')->nullable(); // user, product, course, path, subscription, role ...
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamps();
        });


        Schema::create('discount_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamp('used_at');
            $table->timestamps();
        });


        Schema::create('discount_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->string('item_type')->nullable(); // course | path | vip | null for all
            $table->string('condition_type'); // min_cart_total, max_cart_total, first_purchase, ...
            $table->string('operator')->nullable(); // >=, <=, =, != ...
            $table->string('value')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            MigrationColumnHelpers::jsonColumn($table, 'extra', nullable: true); // برای شرایط پیچیده (مثلا چند روز هفته)
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
        Schema::dropIfExists('discount_conditions');
        Schema::dropIfExists('discount_usages');
        Schema::dropIfExists('discount_eligibilities');
        Schema::dropIfExists('discounts');
    }
};
