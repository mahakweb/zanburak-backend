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
        Schema::create('path_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('path_id');
            $table->unsignedBigInteger('related_path_id');
            $table->enum('type', ['prerequisite', 'next'])->comment('Relation type: prerequisite or next');
            $table->timestamps();

            $table->foreign('path_id')->references('id')->on('paths')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('related_path_id')->references('id')->on('paths')->onDelete('cascade')->onUpdate('cascade');

            $table->unique(['path_id', 'related_path_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('path_relations');
    }
};
