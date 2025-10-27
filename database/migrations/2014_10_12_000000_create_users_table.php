<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Fortify;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();

            if (Fortify::confirmsTwoFactorAuthentication()) {
                $table->timestamp('two_factor_confirmed_at')
                    ->nullable();
            }
            
            $table->string('mobile')->nullable()->unique();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('username')->unique();
            $table->bigInteger('wallet_balance')->default(0);
            $table->boolean('is_superuser')->default(0);
            $table->boolean('is_staff')->default(0);
            $table->text('profile_pic')->nullable();
            $table->text('cover_pic')->nullable();
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamp('last_seen')->nullable();
            $table->boolean('active')->default(true); 
            // $table->unsignedBigInteger('deactivated_by')->nullable(); 
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deactivation_reason')->nullable(); 
            $table->timestamp('deactivated_until')->nullable();
            $table->integer('failed_login_attempts')->default(0);
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
        Schema::dropIfExists('users');
    }
};
