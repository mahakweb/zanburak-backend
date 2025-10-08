<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Discount;
use App\Models\Plan;
use App\Models\User;
use App\Models\Cart;
use App\Models\Path;

class DiscountService
{
    /**
     * Helper to compare scalar values using a simple operator.
     */
    protected function compare($left, $operator, $right): bool
    {
        switch ($operator) {
            case '=':
                return $left == $right;
            case '!=':
                return $left != $right;
            case '>':
                return $left > $right;
            case '<':
                return $left < $right;
            case '>=':
                return $left >= $right;
            case '<=':
                return $left <= $right;
            default:
                return false;
        }
    }

    /**
     * Get Persian text for operator symbols.
     */
    protected function getOperatorText($operator): string
    {
        switch ($operator) {
            case '=':
                return 'مساوی';
            case '!=':
                return 'نامساوی';
            case '>':
                return 'بزرگتر';
            case '<':
                return 'کوچکتر';
            case '>=':
                return 'بزرگتر یا مساوی';
            case '<=':
                return 'کوچکتر یا مساوی';
            default:
                return $operator;
        }
    }

    /**
     * Normalize target type to handle both short names and full model names.
     */
    protected function normalizeTargetType($targetType): string
    {
        // If it's already a short name, return as is
        if (in_array($targetType, ['user', 'course', 'path', 'vip', 'category'])) {
            return $targetType;
        }

        // Convert full model names to short names
        switch ($targetType) {
            case 'App\\Models\\User':
                return 'user';
            case 'App\\Models\\Course':
                return 'course';
            case 'App\\Models\\Path':
                return 'path';
            case 'App\\Models\\Plan':
                return 'vip';
            case 'App\\Models\\Category':
                return 'category';
            default:
                return $targetType;
        }
    }

    /**
     * Build cart summary once to avoid multiple service resolutions.
     */
    protected function getCartSummary(User $user): array
    {
        return app(\App\Services\CartService::class)->getCartItemsResponse($user);
    }

    /**
     * New: Validate eligibilities and throw precise messages per failure.
     */
    public function checkEligibilities(Discount $discount, User $user): void
    {
        if ($discount->per_user_limit !== null) {
            $usedCount = $discount->usages()->where('user_id', $user->id)->count();
            if ($usedCount >= $discount->per_user_limit) {
                throw new \Exception('حد مجاز استفاده برای این کاربر به پایان رسیده است.');
            }
        }

        $cartSummary = $this->getCartSummary($user);

        // Check if there are any user eligibilities
        $userEligibilities = $discount->eligibilities->filter(function ($eligibility) {
            return $this->normalizeTargetType($eligibility->target_type) === 'user';
        });

        // If there are user eligibilities, check if user is included
        if ($userEligibilities->isNotEmpty()) {
            $isUserIncluded = $userEligibilities->contains(function ($eligibility) use ($user) {
                return $eligibility->type === 'inclusion' && $eligibility->target_id == $user->id;
            });

            if (!$isUserIncluded) {
                throw new \Exception('این کد فقط برای کاربران مجاز تعریف شده است.');
            }
        }

        foreach ($discount->eligibilities as $eligibility) {
            $targetType = $this->normalizeTargetType($eligibility->target_type);
            
            switch ($targetType) {

                case 'user':
                    // Check if user is excluded
                    if ($eligibility->type === 'exclusion' && $eligibility->target_id == $user->id) {
                        throw new \Exception('این کد برای این کاربر غیرفعال است.');
                    }
                    break;

                case 'course':
                    $exists = collect($cartSummary['items'])->contains(
                        fn($item) => $item['type'] === 'course' && $item['course']['id'] == $eligibility->target_id
                    );

                    if ($eligibility->type === 'inclusion' && !$exists) {
                        throw new \Exception('این کد فقط برای دوره مشخصی معتبر است.');
                    }
                    if ($eligibility->type === 'exclusion' && $exists) {
                        throw new \Exception('این کد برای یکی از دوره‌های سبد خرید شما معتبر نیست.');
                    }
                    break;

                case 'path':
                    $exists = collect($cartSummary['items'])->contains(
                        fn($item) => $item['type'] === 'path' && $item['path']['id'] == $eligibility->target_id
                    );

                    if ($eligibility->type === 'inclusion' && !$exists) {
                        throw new \Exception('این کد فقط برای مسیر مشخصی معتبر است.');
                    }
                    if ($eligibility->type === 'exclusion' && $exists) {
                        throw new \Exception('این کد برای مسیر انتخابی شما معتبر نیست.');
                    }
                    break;

                case 'vip':
                    $exists = collect($cartSummary['items'])->contains(
                        fn($item) => $item['type'] === 'vip' && $item['vip']['id'] == $eligibility->target_id
                    );

                    if ($eligibility->type === 'inclusion' && !$exists) {
                        throw new \Exception('این کد فقط برای اشتراک مشخصی معتبر است.');
                    }
                    if ($eligibility->type === 'exclusion' && $exists) {
                        throw new \Exception('این کد برای اشتراک انتخابی شما معتبر نیست.');
                    }
                    break;

                case 'category':
                    $exists = collect($cartSummary['items'])->contains(function ($item) use ($eligibility) {
                        $categories = match ($item['type']) {
                            'course' => $item['course']['categories'] ?? [],
                            'path'   => $item['path']['categories'] ?? [],
                            default  => []
                        };
                        return in_array($eligibility->target_id, $categories);
                    });

                    if ($eligibility->type === 'inclusion' && !$exists) {
                        throw new \Exception('این کد فقط برای دسته‌بندی‌های مشخصی معتبر است.');
                    }
                    if ($eligibility->type === 'exclusion' && $exists) {
                        throw new \Exception('این کد برای یکی از دسته‌بندی‌های سبد شما معتبر نیست.');
                    }
                    break;
            }
        }
    }

    /**
     * Check if a specific cart item is eligible based on discount eligibilities.
     * Item-level filtering so we don't apply discount to unrelated items.
     */
    public function isCartItemEligible(Discount $discount, Cart $cart, User $user): bool
    {
        $cartItemType = null;
        $cartItemId = null;

        if ($cart->cartable instanceof Course) {
            $cartItemType = 'course';
            $cartItemId = $cart->cartable->id;
        } elseif ($cart->cartable instanceof Path) {
            $cartItemType = 'path';
            $cartItemId = $cart->cartable->id;
        } elseif ($cart->cartable instanceof Plan) {
            $cartItemType = 'vip';
            $cartItemId = $cart->cartable->id;
        }

        // If there is no eligibility restricting items, consider item eligible
        $hasItemTargetingEligibility = $discount->eligibilities->contains(function ($el) {
            $normalizedType = $this->normalizeTargetType($el->target_type);
            return in_array($normalizedType, ['course', 'path', 'vip', 'category']);
        });

        if (!$hasItemTargetingEligibility) {
            return true;
        }

        // Apply inclusion/exclusion logic
        foreach ($discount->eligibilities as $eligibility) {
            $targetType = $this->normalizeTargetType($eligibility->target_type);
            
            switch ($targetType) {
                case 'course':
                    if ($cartItemType === 'course') {
                        if ($eligibility->type === 'inclusion' && $eligibility->target_id != $cartItemId) {
                            return false;
                        }
                        if ($eligibility->type === 'exclusion' && $eligibility->target_id == $cartItemId) {
                            return false;
                        }
                    }
                    break;

                case 'path':
                    if ($cartItemType === 'path') {
                        if ($eligibility->type === 'inclusion' && $eligibility->target_id != $cartItemId) {
                            return false;
                        }
                        if ($eligibility->type === 'exclusion' && $eligibility->target_id == $cartItemId) {
                            return false;
                        }
                    }
                    break;

                case 'vip':
                    if ($cartItemType === 'vip') {
                        if ($eligibility->type === 'inclusion' && $eligibility->target_id != $cartItemId) {
                            return false;
                        }
                        if ($eligibility->type === 'exclusion' && $eligibility->target_id == $cartItemId) {
                            return false;
                        }
                    }
                    break;

                case 'category':
                    if ($cartItemType === 'course') {
                        $categories = $cart->cartable->category->pluck('id')->toArray();
                        $inCategory = in_array($eligibility->target_id, $categories);
                        if ($eligibility->type === 'inclusion' && !$inCategory) {
                            return false;
                        }
                        if ($eligibility->type === 'exclusion' && $inCategory) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

    public function isValidForUser(Discount $discount, User $user): bool
    {
        try {
            $this->checkEligibilities($discount, $user);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isValidForCart(Discount $discount, Cart $cart): bool
    {
        // time condition
        if ($discount->starts_at && $discount->starts_at->isFuture()) return false;
        if ($discount->ends_at && $discount->ends_at->isPast()) return false;

        // global capacity condition
        if ($discount->usage_limit !== null && $discount->usages()->count() >= $discount->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Validate discount conditions and throw precise messages when a condition fails.
     */
    public function validateConditionsOrFail(Discount $discount, User $user, Cart $cart): void
    {
        $cartSummary = $this->getCartSummary($user);

        foreach ($discount->conditions as $condition) {
            $value     = $condition->value;
            $operator  = $condition->operator;
            $extra     = $condition->extra ?? [];

            switch ($condition->condition_type) {
                /* ==============================
             * 1. مبلغ سبد خرید
             * ============================== */
                case 'min_cart_total':
                case 'max_cart_total':
                    if (!$this->compare($cartSummary['total_price'], $operator, $value)) {
                        $label = $condition->condition_type === 'min_cart_total' ? 'حداقل' : 'حداکثر';
                        $operatorText = $this->getOperatorText($operator);
                        throw new \Exception("{$label} مبلغ سبد باید {$operatorText} {$value} باشد.");
                    }
                    break;

                case 'min_item_price':
                    $minItemPrice = collect($cartSummary['items'])->min('price');
                    if (!$this->compare($minItemPrice, $operator, $value)) {
                        $operatorText = $this->getOperatorText($operator);
                        throw new \Exception("حداقل قیمت آیتم باید {$operatorText} {$value} باشد.");
                    }
                    break;

                case 'max_item_price':
                    $maxItemPrice = collect($cartSummary['items'])->max('price');
                    if (!$this->compare($maxItemPrice, $operator, $value)) {
                        $operatorText = $this->getOperatorText($operator);
                        throw new \Exception("حداکثر قیمت آیتم باید {$operatorText} {$value} باشد.");
                    }
                    break;

                /* ==============================
             * 2. تعداد آیتم‌ها
             * ============================== */
                case 'min_item_count':
                case 'max_item_count':
                    $count = count($cartSummary['items']);
                    if (!$this->compare($count, $operator, $value)) {
                        $label = $condition->condition_type === 'min_item_count' ? 'حداقل' : 'حداکثر';
                        $operatorText = $this->getOperatorText($operator);
                        throw new \Exception("{$label} تعداد آیتم‌های سبد باید {$operatorText} {$value} باشد.");
                    }
                    break;


                /* ==============================
             * 3. کاربر
             * ============================== */
                case 'first_purchase':
                    if ($user->payments()->whereNotNull('paid_at')->where('status', true)->exists()) {
                        throw new \Exception('این کد فقط برای اولین خرید قابل استفاده است.');
                    }
                    break;

                case 'no_purchase_since':
                    $lastPayment = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)
                        ->latest()
                        ->first();

                    if ($lastPayment && $lastPayment->created_at->gt(now()->subDays($value))) {
                        throw new \Exception('از آخرین خرید شما به اندازه کافی زمان نگذشته است.');
                    }
                    break;

                case 'min_orders_count':
                    $count = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)
                        ->count();

                    if (!$this->compare($count, $operator, $value)) {
                        throw new \Exception('تعداد سفارش‌های کاربر با شرط تعیین‌شده سازگار نیست.');
                    }
                    break;

                case 'max_orders_count':
                    $count = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)
                        ->count();

                    if (!$this->compare($count, $operator, $value)) {
                        throw new \Exception('تعداد سفارش‌های کاربر با شرط تعیین‌شده سازگار نیست.');
                    }
                    break;



                /* ==============================
             * 4. زمان
             * ============================== */
                case 'day_of_week':
                    $days = $extra['days'] ?? [];
                    
                    // اگر days رشته است، ابتدا سعی کن آن را به آرایه تبدیل کن
                    if (is_string($days)) {
                        // ابتدا سعی کن JSON decode کن
                        $decoded = json_decode($days, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $days = $decoded;
                        } else {
                            // اگر JSON نیست، سعی کن با کاما جدا کن
                            $days = array_map('intval', explode(',', $days));
                        }
                    }
                    // اگر days عدد است، آن را به آرایه تبدیل کن
                    elseif (is_numeric($days)) {
                        $days = [(int)$days];
                    }
                    // اگر days آرایه نیست، آن را به آرایه خالی تبدیل کن
                    elseif (!is_array($days)) {
                        $days = [];
                    }
                    
                    if (!in_array(now()->dayOfWeek, $days)) {
                        throw new \Exception('این کد در روز فعلی هفته قابل استفاده نیست.');
                    }
                    break;

                case 'time_range':
                    $start = $extra['start'] ?? null;
                    $end   = $extra['end'] ?? null;
                    $now   = now()->format('H:i');
                    if ($start && $end && !($now >= $start && $now <= $end)) {
                        throw new \Exception('این کد در بازه زمانی فعلی قابل استفاده نیست.');
                    }
                    break;

                case 'date_range':
                    $start = $extra['start_date'] ?? null;
                    $end   = $extra['end_date'] ?? null;
                    
                    // اگر تاریخ‌ها رشته هستند، آن‌ها را به Carbon تبدیل کن
                    if ($start && is_string($start)) {
                        $start = \Carbon\Carbon::parse($start);
                    }
                    if ($end && is_string($end)) {
                        $end = \Carbon\Carbon::parse($end);
                    }
                    
                    if ($start && now()->lt($start)) throw new \Exception('زمان شروع استفاده از این کد هنوز نرسیده است.');
                    if ($end && now()->gt($end)) throw new \Exception('مهلت استفاده از این کد به پایان رسیده است.');
                    break;

                /* ==============================
             * 5. محصولات و دسته‌بندی‌ها
             * ============================== */
                case 'required_item':
                    $itemId = $condition->target_id; // Use target_id instead of value
                    $itemType = $this->normalizeItemType($condition->item_type); // Normalize item_type
                    $exists = collect($cartSummary['items'])
                        ->contains(
                            fn($item) => (!$itemType || $item['type'] === $itemType) &&
                                (
                                    ($item['type'] === 'course' && $item['course']['id'] == $itemId) ||
                                    ($item['type'] === 'path' && $item['path']['id'] == $itemId) ||
                                    ($item['type'] === 'vip' && $item['vip']['id'] == $itemId)
                                )
                        );
                    if (!$exists) throw new \Exception('آیتم الزامی در سبد خرید شما وجود ندارد.');
                    break;

                case 'forbidden_item':
                    $itemId = $condition->target_id; // Use target_id instead of value
                    $itemType = $this->normalizeItemType($condition->item_type); // Normalize item_type
                    $exists = collect($cartSummary['items'])
                        ->contains(
                            fn($item) => (!$itemType || $item['type'] === $itemType) &&
                                (
                                    ($item['type'] === 'course' && $item['course']['id'] == $itemId) ||
                                    ($item['type'] === 'path' && $item['path']['id'] == $itemId) ||
                                    ($item['type'] === 'vip' && $item['vip']['id'] == $itemId)
                                )
                        );
                    if ($exists) throw new \Exception('وجود یک آیتم ممنوعه در سبد استفاده از کد را محدود کرده است.');
                    break;


                case 'required_category':
                    $catId = $value;
                    $exists = collect($cartSummary['items'])
                        ->contains(
                            fn($item) =>
                            $item['type'] === 'course' && in_array($catId, $item['course']['categories'] ?? [])
                        );
                    if (!$exists) throw new \Exception('دسته‌بندی الزامی در سبد شما وجود ندارد.');
                    break;

                case 'forbidden_category':
                    $catId = $value;
                    $exists = collect($cartSummary['items'])
                        ->contains(
                            fn($item) =>
                            $item['type'] === 'course' && in_array($catId, $item['course']['categories'] ?? [])
                        );
                    if ($exists) throw new \Exception('وجود یک دسته‌بندی ممنوعه در سبد استفاده از کد را محدود کرده است.');
                    break;


                /* ==============================
             * 6. سفارشات قبلی
             * ============================== */
                case 'min_total_spent':
                    $spent = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)->sum('amount');
                    if (!$this->compare($spent, $operator, $value)) {
                        throw new \Exception('مجموع مبالغ خرید شما با شرط تعیین‌شده سازگار نیست.');
                    }
                    break;

                case 'max_total_spent':
                    $spent = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)->sum('amount');
                    if (!$this->compare($spent, $operator, $value)) {
                        throw new \Exception('مجموع مبالغ خرید شما با شرط تعیین‌شده سازگار نیست.');
                    }
                    break;

                case 'purchased_product_before':
                    $itemId = $condition->target_id; // Use target_id instead of value
                    $itemType = $this->normalizeItemType($condition->item_type); // Normalize item_type
                    $exists = $user->payments()->whereNotNull('paid_at')->where('status', true)->get()->contains(function ($payment) use ($itemId, $itemType) {
                        $item = $payment->payable;
                        return ($itemType === 'course' && $item instanceof Course && $item->id == $itemId) ||
                            ($itemType === 'path' && $item instanceof Path && $item->id == $itemId) ||
                            ($itemType === 'vip' && $item instanceof Plan && $item->id == $itemId);
                    });
                    if (!$exists) throw new \Exception('تا کنون این آیتم را خریداری نکرده‌اید.');
                    break;

                case 'not_purchased_product_before':
                    $itemId = $condition->target_id; // Use target_id instead of value
                    $itemType = $this->normalizeItemType($condition->item_type); // Normalize item_type
                    $exists = $user->payments()->whereNotNull('paid_at')->where('status', true)->get()->contains(function ($payment) use ($itemId, $itemType) {
                        $item = $payment->payable;
                        return ($itemType === 'course' && $item instanceof Course && $item->id == $itemId) ||
                            ($itemType === 'path' && $item instanceof Path && $item->id == $itemId) ||
                            ($itemType === 'vip' && $item instanceof Plan && $item->id == $itemId);
                    });
                    if ($exists) throw new \Exception('پیش‌تر این آیتم را خریداری کرده‌اید.');
                    break;


                /* ==============================
             * 7. شرط‌های خاص
             * ============================== */

                case 'new_user':
                    $days = $value ?? 7;
                    if ($user->created_at->lt(now()->subDays($days))) {
                        throw new \Exception('این کد فقط برای کاربران جدید قابل استفاده است.');
                    }
                    break;
            }
        }
        // all conditions satisfied
        return;
    }


    public function calculateDiscountAmount(Discount $discount, Cart $cart): int
    {
        // محاسبه قیمت اصلی آیتم
        $originalPrice = app(\App\Services\CartService::class)->calculateOriginalPrice($cart);
        
        switch ($discount->type) {
            case 'percent':
                return intval($originalPrice * ($discount->value / 100));
            case 'fixed':
                return intval($discount->value);
            case 'free':
                return intval($originalPrice);
            default:
                return 0;
        }
    }

    public function apply(Discount $discount, Cart $cart, User $user): array
    {
        // 1) Eligibilities
        $this->checkEligibilities($discount, $user);

        // 2) Cart level validations with precise messages
        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new \Exception('زمان شروع استفاده از این کد هنوز نرسیده است.');
        }
        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new \Exception('مهلت استفاده از این کد به پایان رسیده است.');
        }
        if ($discount->usage_limit !== null && $discount->usages()->count() >= $discount->usage_limit) {
            throw new \Exception('ظرفیت استفاده از این کد به پایان رسیده است.');
        }

        // 3) Conditions
        $this->validateConditionsOrFail($discount, $user, $cart);

        $amount = $this->calculateDiscountAmount($discount, $cart);
        $originalPrice = app(\App\Services\CartService::class)->calculateOriginalPrice($cart);

        return [
            'discount_id'   => $discount->id,
            'discount_code' => $discount->code,
            'amount'        => $amount,
            'final_price'   => max(0, $originalPrice - $amount),
        ];
    }

    /**
     * Normalize item type to handle both short names and full model names.
     */
    protected function normalizeItemType($itemType): ?string
    {
        if (!$itemType) {
            return null;
        }

        // If it's already a short name, return as is
        if (in_array($itemType, ['course', 'path', 'vip'])) {
            return $itemType;
        }

        // Convert full model names to short names
        switch ($itemType) {
            case 'App\\Models\\Course':
                return 'course';
            case 'App\\Models\\Path':
                return 'path';
            case 'App\\Models\\Plan':
                return 'vip';
            default:
                return $itemType;
        }
    }
}
