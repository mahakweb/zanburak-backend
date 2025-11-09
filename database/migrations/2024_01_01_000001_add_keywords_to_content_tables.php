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
        // Add keywords to courses table
        if (!Schema::hasColumn('courses', 'meta_keywords')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->text('meta_keywords')->nullable()->after('description');
            });
        }

        // Add keywords to episodes table
        if (!Schema::hasColumn('episodes', 'meta_keywords')) {
            Schema::table('episodes', function (Blueprint $table) {
                $table->text('meta_keywords')->nullable()->after('description');
            });
        }

        // Add keywords to questions table
        if (!Schema::hasColumn('questions', 'meta_keywords')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->text('meta_keywords')->nullable()->after('question');
            });
        }

        // Add keywords to paths table
        if (!Schema::hasColumn('paths', 'meta_keywords')) {
            Schema::table('paths', function (Blueprint $table) {
                $table->text('meta_keywords')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });

        Schema::table('paths', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });
    }
};

