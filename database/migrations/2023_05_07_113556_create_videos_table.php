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
            $table->string('quality')->nullable();
            $table->string('disk')->default('dl');
            $table->enum('type', ['raw', 'trailer', 'download', 'stream']);
            $table->string('path');
            $table->integer('duration')->default(0);
            $table->unique(['videoable_id', 'videoable_type', 'disk', 'type', 'path']);
            $table->string('status', 20)->nullable();
            $table->index('status', 'videos_status_index');
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
