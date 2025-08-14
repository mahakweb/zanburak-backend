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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('videoable_id');
            $table->string('videoable_type');
            $table->string('disk')->default('dl');
            $table->enum('type', ['trailer', 'download', 'stream']);
            $table->string('path');
            $table->unique(['videoable_id', 'videoable_type', 'disk', 'type', 'path']);
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
        Schema::dropIfExists('videos');
    }
};
