<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('serial_number', 32)->nullable()->unique()->after('uuid');
            $table->foreignId('certificate_template_id')->nullable()->after('course_id')
                ->constrained('certificate_templates')->nullOnDelete();
            $table->string('verification_token', 64)->nullable()->after('serial_number');
            $table->string('instructor_name')->nullable()->after('course_title');
            $table->decimal('grade', 5, 2)->nullable()->after('instructor_name');
            $table->string('status', 20)->default('issued')->after('issued_at'); // pending | issued | revoked
            $table->string('pdf_path')->nullable()->after('status');
            $table->string('image_path')->nullable()->after('pdf_path');
            $table->timestamp('revoked_at')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['certificate_template_id']);
            $table->dropColumn([
                'serial_number',
                'certificate_template_id',
                'verification_token',
                'instructor_name',
                'grade',
                'status',
                'pdf_path',
                'image_path',
                'revoked_at',
            ]);
        });
    }
};
