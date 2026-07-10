<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(32) NOT NULL DEFAULT 'bank'");
        } else {
            DB::statement('ALTER TABLE payments ALTER COLUMN payment_method TYPE VARCHAR(32)');
            DB::statement("ALTER TABLE payments ALTER COLUMN payment_method SET DEFAULT 'bank'");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_variant', 64)->nullable()->after('driver');
            $table->string('digipay_mode', 32)->nullable()->after('gateway_variant');
            $table->unsignedTinyInteger('digipay_preferred_gateway')->nullable()->after('digipay_mode');
            $table->bigInteger('wallet_paid_amount')->default(0)->after('gateway_fee_percent');
            $table->bigInteger('gateway_paid_amount')->nullable()->after('wallet_paid_amount');
            $table->bigInteger('digipay_credit_amount')->default(0)->after('gateway_paid_amount');
            $table->bigInteger('digipay_cash_amount')->default(0)->after('digipay_credit_amount');
            $table->json('digipay_verify_payload')->nullable()->after('digipay_cash_amount');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('allows_installment')->default(false)->after('price');
        });

        Schema::table('paths', function (Blueprint $table) {
            $table->boolean('allows_installment')->default(false)->after('status');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allows_installment')->default(false)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('allows_installment');
        });

        Schema::table('paths', function (Blueprint $table) {
            $table->dropColumn('allows_installment');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('allows_installment');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_variant',
                'digipay_mode',
                'digipay_preferred_gateway',
                'wallet_paid_amount',
                'gateway_paid_amount',
                'digipay_credit_amount',
                'digipay_cash_amount',
                'digipay_verify_payload',
            ]);
        });
    }
};
