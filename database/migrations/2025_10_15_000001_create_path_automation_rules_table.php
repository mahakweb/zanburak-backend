<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('path_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('path_id');
            $table->enum('target', ['self', 'course']);
            $table->enum('match_type', ['all', 'any'])->default('any');
            $table->string('field');
            $table->string('operator');
            $table->string('value');
            $table->timestamps();

            $table->foreign('path_id')->references('id')->on('paths')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('path_automation_rules');
    }
};


