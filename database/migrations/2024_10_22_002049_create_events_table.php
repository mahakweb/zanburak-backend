<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('english_title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('icon')->nullable();
            $table->timestamps();
        });


        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('english_title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('icon')->nullable();
            $table->foreignId('event_group_id')->constrained('event_groups')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('is_email_enabled')->default(true); 
            $table->boolean('is_sms_enabled')->default(true);  
            $table->boolean('is_telegram_enabled')->default(true); 
            $table->boolean('is_site_enabled')->default(true);  
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
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_groups');
    }
};
