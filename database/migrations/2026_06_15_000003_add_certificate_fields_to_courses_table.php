<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('certificate_enabled')->default(false)->after('publish');
            $table->foreignId('certificate_template_id')->nullable()->after('certificate_enabled')
                ->constrained('certificate_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['certificate_template_id']);
            $table->dropColumn(['certificate_enabled', 'certificate_template_id']);
        });
    }
};
