<?php

namespace Tests\Feature\Settlement;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Permission;
use App\Models\User;
use App\Services\Settlement\PaymentSettlementPresenter;
use App\Services\Settlement\SettlementService;
use App\Services\Settlement\SettlementSummary;
use App\Support\SettlementStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettlementFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'null']);
        Storage::fake('local');
    }

    public function test_paid_course_payment_starts_as_pending_settlement(): void
    {
        [$teacher, $buyer, $course] = $this->courseSale();
        $payment = $this->paidPayment($buyer, $course, 1000000, now());

        $summary = (new PaymentSettlementPresenter(null))->summarize($payment->fresh(['items.payable.teacher', 'items.activeSettlementItem.settlement']));
        $this->assertSame('paid', $summary['payment_status']);
        $this->assertSame(SettlementStatus::PENDING, $summary['status']);
        $this->assertSame(1000000, $summary['teacher_share']);

        $finance = $this->financeUser();
        $service = app(SettlementService::class);
        $created = $service->create($finance, [
            'payment_ids' => [$payment->id],
            'status' => SettlementStatus::SETTLED,
            'tracking_number' => 'TRX-1',
            'idempotency_key' => 'batch-1',
        ]);

        $settlement = $created['settlements']->first();
        $this->assertSame(SettlementStatus::SETTLED, $settlement->status);
        $this->assertSame(1, $settlement->audits()->where('action', 'created')->count());
        $this->assertSame(1000000, (int) $settlement->amount);
    }

    public function test_duplicate_settlement_is_rejected(): void
    {
        [$teacher, $buyer, $course] = $this->courseSale();
        $payment = $this->paidPayment($buyer, $course, 800000, now());
        $finance = $this->financeUser();
        $service = app(SettlementService::class);
        $service->create($finance, [
            'payment_ids' => [$payment->id],
            'status' => SettlementStatus::PENDING,
            'idempotency_key' => 'once',
        ]);

        $this->expectException(\App\Exceptions\SettlementException::class);
        $service->create($finance, [
            'payment_ids' => [$payment->id],
            'status' => SettlementStatus::PENDING,
            'idempotency_key' => 'twice',
        ]);
    }

    public function test_teacher_cannot_open_another_teachers_receipt(): void
    {
        [$teacher, $buyer, $course] = $this->courseSale();
        $other = User::factory()->create();
        $payment = $this->paidPayment($buyer, $course, 500000, now());
        $finance = $this->financeUser();
        $created = app(SettlementService::class)->create($finance, [
            'payment_ids' => [$payment->id],
            'status' => SettlementStatus::SETTLED,
            'idempotency_key' => 'receipt-batch',
        ]);
        $settlement = $created['settlements']->first();
        app(SettlementService::class)->storeReceipt($finance, $settlement, UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'));

        $this->grant($teacher, ['payments.view.own', 'settlements.view.own', 'settlements.view_receipt.own']);
        $this->grant($other, ['payments.view.own', 'settlements.view.own', 'settlements.view_receipt.own']);

        $this->actingAs($teacher, 'sanctum')->getJson('/api/admin/settlements/'.$settlement->uuid.'/receipt')->assertOk();
        $this->actingAs($other, 'sanctum')->getJson('/api/admin/settlements/'.$settlement->uuid.'/receipt')->assertForbidden();
        $this->actingAs($other, 'sanctum')->getJson('/api/admin/settlements/'.$settlement->uuid)->assertForbidden();
    }

    public function test_status_transition_is_audited_and_outstanding_ignores_today_filter(): void
    {
        [$teacher, $buyer, $course] = $this->courseSale();
        $yesterday = $this->paidPayment($buyer, $course, 400000, now()->subDay());
        $today = $this->paidPayment($buyer, $course, 250000, now());
        $finance = $this->financeUser();

        $summary = app(SettlementSummary::class)->dashboard($finance, now()->startOfDay(), now()->endOfDay());
        $this->assertSame(1, $summary['sales_count']);
        $this->assertSame(650000, $summary['outstanding_amount']);
        $this->assertSame(2, $summary['outstanding_count']);

        $created = app(SettlementService::class)->create($finance, [
            'payment_ids' => [$yesterday->id],
            'status' => SettlementStatus::PENDING,
            'idempotency_key' => 'status-batch',
        ]);
        $settlement = $created['settlements']->first();
        app(SettlementService::class)->changeStatus($finance, $settlement, SettlementStatus::PROCESSING);
        app(SettlementService::class)->changeStatus($finance, $settlement->fresh(), SettlementStatus::SETTLED);

        $this->assertSame(SettlementStatus::SETTLED, $settlement->fresh()->status);
        $this->assertGreaterThanOrEqual(2, $settlement->audits()->where('action', 'status_changed')->count());
    }

    public function test_platform_share_is_applied_and_snapshotted(): void
    {
        [$teacher, $buyer, $course] = $this->courseSale();
        $payment = $this->paidPayment($buyer, $course, 1000000, now());
        $finance = $this->financeUser();

        app(\App\Services\Settlement\SettlementCommission::class)->update(20, 80, $finance->id);

        $created = app(SettlementService::class)->create($finance, [
            'payment_ids' => [$payment->id],
            'status' => SettlementStatus::SETTLED,
            'idempotency_key' => 'share-batch',
        ]);
        $settlement = $created['settlements']->first();

        $this->assertSame(800000, (int) $settlement->amount);
        $this->assertSame(1000000, (int) $settlement->gross_amount);
        $this->assertSame(200000, (int) $settlement->platform_amount);
        $this->assertSame(20.0, (float) $settlement->site_percent);
        $this->assertSame(80.0, (float) $settlement->teacher_percent);

        $item = $settlement->items()->first();
        $this->assertSame(800000, (int) $item->amount);
        $this->assertSame(200000, (int) $item->platform_amount);

        $settings = $this->actingAs($finance, 'sanctum')
            ->getJson('/api/admin/settlements/settings')
            ->assertOk()
            ->json('settings');
        $this->assertSame(20.0, (float) $settings['site_percent']);
        $this->assertSame(80.0, (float) $settings['teacher_percent']);

        $teacherUser = User::factory()->create(['is_superuser' => false]);
        $this->actingAs($teacherUser, 'sanctum')
            ->postJson('/api/admin/settlements/settings', ['site_percent' => 50])
            ->assertForbidden();
    }

    public function test_unpaid_payment_is_not_settleable(): void
    {
        [$teacher, $buyer, $course] = $this->courseSale();
        $payment = $this->paidPayment($buyer, $course, 100000, now());
        $payment->update(['status' => false, 'paid_at' => null]);

        $this->expectException(\App\Exceptions\SettlementException::class);
        app(SettlementService::class)->create($this->financeUser(), [
            'payment_ids' => [$payment->id],
            'idempotency_key' => 'unpaid',
        ]);
    }

    private function courseSale(): array
    {
        $teacher = User::factory()->create();
        $buyer = User::factory()->create();
        $course = Course::withoutSyncingToSearch(fn () => Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'دوره تست',
            'english_title' => 'test-course-'.uniqid(),
            'description' => 'description',
            'price' => 1000000,
            'type' => 'cash',
            'publish' => true,
        ]));

        return [$teacher, $buyer, $course];
    }

    private function paidPayment(User $buyer, Course $course, int $amount, $paidAt): Payment
    {
        $payment = Payment::create([
            'user_id' => $buyer->id,
            'payment_method' => 'bank',
            'amount' => $amount,
            'base_amount' => $amount,
            'status' => true,
            'paid_at' => $paidAt,
        ]);
        PaymentItem::create([
            'payment_id' => $payment->id,
            'payable_type' => Course::class,
            'payable_id' => $course->id,
            'price' => $amount,
            'discount_amount' => 0,
            'final_price' => $amount,
            'gateway_fee_amount' => 0,
            'charged_price' => $amount,
        ]);

        return $payment->fresh('items');
    }

    private function financeUser(): User
    {
        $user = User::factory()->create(['is_superuser' => true]);

        return $user;
    }

    private function grant(User $user, array $names): void
    {
        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['label' => $name]);
            $user->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
};
