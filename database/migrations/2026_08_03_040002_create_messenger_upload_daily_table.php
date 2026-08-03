<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_upload_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('day');
            $table->unsignedBigInteger('bytes_used')->default(0);
            $table->unsignedInteger('files_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'day']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_upload_daily');
    }
};
