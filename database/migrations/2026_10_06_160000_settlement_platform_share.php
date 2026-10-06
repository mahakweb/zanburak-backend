<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->bigInteger('gross_amount')->default(0)->after('amount');
            $table->bigInteger('platform_amount')->default(0)->after('gross_amount');
            $table->decimal('site_percent', 5, 2)->default(0)->after('platform_amount');
            $table->decimal('teacher_percent', 5, 2)->default(100)->after('site_percent');
        });

        Schema::table('settlement_items', function (Blueprint $table) {
            $table->bigInteger('gross_amount')->default(0)->after('amount');
            $table->bigInteger('platform_amount')->default(0)->after('gross_amount');
            $table->decimal('site_percent', 5, 2)->default(0)->after('platform_amount');
            $table->decimal('teacher_percent', 5, 2)->default(100)->after('site_percent');
        });

        DB::table('settlements')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('settlements')->where('id', $row->id)->update([
                    'gross_amount' => (int) $row->amount,
                    'platform_amount' => 0,
                    'site_percent' => 0,
                    'teacher_percent' => 100,
                ]);
            }
        });

        DB::table('settlement_items')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('settlement_items')->where('id', $row->id)->update([
                    'gross_amount' => (int) $row->amount,
                    'platform_amount' => 0,
                    'site_percent' => 0,
                    'teacher_percent' => 100,
                ]);
            }
        });

        if (! DB::table('site_settings')->where('key', 'settlement.site_percent')->exists()) {
            $now = now();
            DB::table('site_settings')->insert([
                [
                    'key' => 'settlement.site_percent',
                    'value' => '0',
                    'type' => 'float',
                    'group' => 'settlement',
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'key' => 'settlement.teacher_percent',
                    'value' => '100',
                    'type' => 'float',
                    'group' => 'settlement',
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'platform_amount', 'site_percent', 'teacher_percent']);
        });
        Schema::table('settlement_items', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'platform_amount', 'site_percent', 'teacher_percent']);
        });
        DB::table('site_settings')->whereIn('key', [
            'settlement.site_percent',
            'settlement.teacher_percent',
        ])->delete();
    }
};
