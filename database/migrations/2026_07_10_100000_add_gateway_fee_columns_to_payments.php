<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->bigInteger('base_amount')->nullable()->after('amount');
            $table->bigInteger('gateway_fee_amount')->default(0)->after('base_amount');
            $table->decimal('gateway_fee_percent', 5, 2)->default(0)->after('gateway_fee_amount');
        });

        Schema::table('payment_items', function (Blueprint $table) {
            $table->bigInteger('gateway_fee_amount')->default(0)->after('final_price');
            $table->bigInteger('charged_price')->nullable()->after('gateway_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_items', function (Blueprint $table) {
            $table->dropColumn(['gateway_fee_amount', 'charged_price']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'gateway_fee_amount', 'gateway_fee_percent']);
        });
    }
};
