<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->bigInteger('amount');
            $table->unsignedInteger('payment_count')->default(0);
            $table->string('status', 32)->default('pending');
            $table->string('tracking_number')->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('account_holder')->nullable();
            $table->text('description')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('current_receipt_id')->nullable();
            $table->timestamps();

            $table->index('teacher_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index('created_at');
            $table->index('created_by');
            $table->index('paid_by');
            $table->unique(['created_by', 'idempotency_key']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('payment_item_id')->constrained('payment_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('charged_amount')->default(0);
            $table->unsignedBigInteger('active_payment_item_id')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index('settlement_id');
            $table->index('payment_id');
            $table->index('payment_item_id');
            $table->index('teacher_id');
            $table->index(['payment_id', 'released_at']);
            $table->unique('active_payment_item_id');
        });

        Schema::create('settlement_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 127)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replaced_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['settlement_id', 'created_at']);
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->foreign('current_receipt_id')
                ->references('id')
                ->on('settlement_receipts')
                ->nullOnDelete();
        });

        Schema::create('settlement_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['settlement_id', 'created_at']);
            $table->index('user_id');
            $table->index('action');
        });

        $this->grantPermissions();
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropForeign(['current_receipt_id']);
        });

        Schema::dropIfExists('settlement_audits');
        Schema::dropIfExists('settlement_receipts');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');

        $names = array_keys($this->permissionLabels());
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
            DB::table('permission_routes')->whereIn('permission_id', $ids)->delete();
            DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }

    private function grantPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        foreach ($this->permissionLabels() as $name => $label) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['label' => $label, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($this->permissionLabels()))->pluck('id', 'name');
        $roles = DB::table('roles')->pluck('id', 'name');

        $grants = [
            'administrator' => array_keys($this->permissionLabels()),
            'finance_manager' => array_keys($this->permissionLabels()),
            'payments_operator' => [
                'settlements.view',
                'settlements.view.any',
                'settlements.create',
                'settlements.update',
                'settlements.change_status',
                'settlements.upload_receipt',
                'settlements.view_receipt',
                'settlements.view_receipt.any',
                'settlements.view_all_teachers',
            ],
            'payments_auditor' => [
                'settlements.view',
                'settlements.view.any',
                'settlements.view_receipt',
                'settlements.view_receipt.any',
                'settlements.view_all_teachers',
            ],
            'support' => [
                'settlements.view',
                'settlements.view.any',
            ],
            'teacher' => [
                'settlements.view.own',
                'settlements.view_receipt.own',
            ],
            'teacher_finance_viewer' => [
                'settlements.view.own',
                'settlements.view_receipt.own',
            ],
        ];

        $rows = [];
        foreach ($grants as $roleName => $names) {
            if (! isset($roles[$roleName])) {
                continue;
            }
            foreach ($names as $name) {
                if (! isset($permissionIds[$name])) {
                    continue;
                }
                $rows[] = [
                    'role_id' => $roles[$roleName],
                    'permission_id' => $permissionIds[$name],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('permission_role')->upsert($rows, ['role_id', 'permission_id'], ['updated_at']);
        }

        if (! Schema::hasTable('permission_routes')) {
            return;
        }

        $routes = [
            'api.admin.settlements.summary' => ['settlements.view', 'settlements.view.own', 'settlements.view.any', 'payments.view', 'payments.view.own', 'payments.view.any'],
            'api.admin.settlements.index' => ['settlements.view', 'settlements.view.own', 'settlements.view.any', 'payments.view', 'payments.view.own', 'payments.view.any'],
            'api.admin.settlements.teachers' => ['settlements.view', 'settlements.view.own', 'settlements.view.any', 'payments.view.any'],
            'api.admin.settlements.teachers.show' => ['settlements.view', 'settlements.view.own', 'settlements.view.any', 'payments.view.any'],
            'api.admin.settlements.show' => ['settlements.view', 'settlements.view.own', 'settlements.view.any', 'payments.view', 'payments.view.own', 'payments.view.any'],
            'api.admin.settlements.audits' => ['settlements.view', 'settlements.view.own', 'settlements.view.any', 'payments.view', 'payments.view.own', 'payments.view.any'],
            'api.admin.settlements.preview' => ['settlements.create'],
            'api.admin.settlements.create' => ['settlements.create'],
            'api.admin.settlements.update' => ['settlements.update'],
            'api.admin.settlements.status' => ['settlements.change_status'],
            'api.admin.settlements.receipt.upload' => ['settlements.upload_receipt'],
            'api.admin.settlements.receipt.delete' => ['settlements.delete_receipt'],
            'api.admin.settlements.receipt.show' => ['settlements.view_receipt', 'settlements.view_receipt.any', 'settlements.view_receipt.own'],
        ];

        $routeRows = [];
        foreach ($routes as $routeName => $names) {
            foreach ($names as $name) {
                if (! isset($permissionIds[$name])) {
                    continue;
                }
                $routeRows[] = [
                    'permission_id' => $permissionIds[$name],
                    'route_name' => $routeName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($routeRows !== []) {
            DB::table('permission_routes')->upsert($routeRows, ['permission_id', 'route_name'], ['updated_at']);
        }
    }

    private function permissionLabels(): array
    {
        return [
            'settlements.view' => 'مشاهده تسویه‌ها',
            'settlements.view.any' => 'مشاهده همه تسویه‌ها',
            'settlements.view.own' => 'مشاهده تسویه‌های خود',
            'settlements.create' => 'ایجاد تسویه',
            'settlements.update' => 'ویرایش اطلاعات تسویه',
            'settlements.change_status' => 'تغییر وضعیت تسویه',
            'settlements.upload_receipt' => 'آپلود و جایگزینی رسید تسویه',
            'settlements.view_receipt' => 'مشاهده رسید تسویه',
            'settlements.view_receipt.any' => 'مشاهده رسید همه تسویه‌ها',
            'settlements.view_receipt.own' => 'مشاهده رسید تسویه‌های خود',
            'settlements.delete_receipt' => 'حذف رسید تسویه',
            'settlements.view_all_teachers' => 'مشاهده وضعیت مالی همه مدرسین',
        ];
    }
};
