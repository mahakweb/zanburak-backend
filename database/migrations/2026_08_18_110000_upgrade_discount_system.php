<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('discounts', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('discounts', 'max_discount_amount')) {
                $table->unsignedBigInteger('max_discount_amount')->nullable()->after('value');
            }
            if (!Schema::hasColumn('discounts', 'stackable')) {
                $table->boolean('stackable')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('discounts', 'apply_automatically')) {
                $table->boolean('apply_automatically')->default(false)->after('stackable');
            }
            if (!Schema::hasColumn('discounts', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('apply_automatically');
            }
            if (!Schema::hasColumn('discounts', 'banner_title')) {
                $table->string('banner_title')->nullable()->after('is_public');
            }
            if (!Schema::hasColumn('discounts', 'banner_description')) {
                $table->text('banner_description')->nullable()->after('banner_title');
            }
            if (!Schema::hasColumn('discounts', 'cta_text')) {
                $table->string('cta_text')->nullable()->after('banner_description');
            }
            if (!Schema::hasColumn('discounts', 'destination_type')) {
                $table->string('destination_type', 32)->nullable()->after('cta_text');
            }
            if (!Schema::hasColumn('discounts', 'destination_url')) {
                $table->string('destination_url')->nullable()->after('destination_type');
            }
            if (!Schema::hasColumn('discounts', 'priority')) {
                $table->unsignedInteger('priority')->default(0)->after('destination_url');
            }
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->index(['is_active', 'apply_automatically', 'starts_at', 'ends_at'], 'discounts_auto_active_idx');
            $table->index(['is_active', 'is_public', 'priority'], 'discounts_public_priority_idx');
        });

        Schema::table('discount_usages', function (Blueprint $table) {
            $table->index(['discount_id', 'user_id']);
            $table->index(['discount_id', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropIndex('discounts_auto_active_idx');
            $table->dropIndex('discounts_public_priority_idx');
        });

        Schema::table('discount_usages', function (Blueprint $table) {
            $table->dropIndex(['discount_id', 'user_id']);
            $table->dropIndex(['discount_id', 'payment_id']);
        });

        Schema::table('discounts', function (Blueprint $table) {
            $columns = [
                'description',
                'max_discount_amount',
                'stackable',
                'apply_automatically',
                'is_public',
                'banner_title',
                'banner_description',
                'cta_text',
                'destination_type',
                'destination_url',
                'priority',
            ];

            $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('discounts', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
