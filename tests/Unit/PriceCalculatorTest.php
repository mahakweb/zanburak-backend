<?php

namespace Tests\Unit;

use App\Models\Discount;
use App\Services\PriceCalculator;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    protected PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator;
    }

    public function test_percent_discount_is_calculated_from_original_price(): void
    {
        $this->assertSame(200000, $this->calculator->amountForType('percent', 20, 1000000));
    }

    public function test_percent_discount_respects_max_cap(): void
    {
        $this->assertSame(50000, $this->calculator->amountForType('percent', 20, 1000000, 50000));
    }

    public function test_fixed_discount_cannot_exceed_original(): void
    {
        $this->assertSame(1000000, $this->calculator->amountForType('fixed', 2000000, 1000000));
        $this->assertSame(150000, $this->calculator->amountForType('fixed', 150000, 1000000));
    }

    public function test_free_discount_zeroes_the_price(): void
    {
        $this->assertSame(800000, $this->calculator->amountForType('free', 0, 800000));
    }

    public function test_stackable_coupon_applies_both_from_original(): void
    {
        $combined = $this->calculator->combine(1000000, 200000, 100000, true);

        $this->assertSame(200000, $combined['course_discount_amount']);
        $this->assertSame(100000, $combined['coupon_discount_amount']);
        $this->assertSame(300000, $combined['total_discount']);
        $this->assertSame(700000, $combined['final_price']);
        $this->assertSame(30, $combined['discount_percentage']);
    }

    public function test_non_stackable_applies_the_larger_discount_only(): void
    {
        $preferDirect = $this->calculator->combine(1000000, 200000, 100000, false);
        $this->assertSame(200000, $preferDirect['course_discount_amount']);
        $this->assertSame(0, $preferDirect['coupon_discount_amount']);
        $this->assertSame(800000, $preferDirect['final_price']);

        $preferCoupon = $this->calculator->combine(1000000, 200000, 300000, false);
        $this->assertSame(0, $preferCoupon['course_discount_amount']);
        $this->assertSame(300000, $preferCoupon['coupon_discount_amount']);
        $this->assertSame(700000, $preferCoupon['final_price']);
    }

    public function test_amount_for_discount_uses_coupon_fields(): void
    {
        $discount = new Discount([
            'type' => 'percent',
            'value' => 10,
            'max_discount_amount' => 80000,
        ]);

        $this->assertSame(80000, $this->calculator->amountForDiscount($discount, 1000000));
    }

    public function test_empty_pricing_has_no_discount(): void
    {
        $pricing = $this->calculator->emptyPricing(250000);

        $this->assertFalse($pricing['has_discount']);
        $this->assertSame(250000, $pricing['final_price']);
        $this->assertSame(250000, $pricing['current_price']);
    }
}
