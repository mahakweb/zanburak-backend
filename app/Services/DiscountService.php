<?php

namespace App\Services;

use App\Exceptions\DiscountException;
use App\Models\Cart;
use App\Models\Course;
use App\Models\Discount;
use App\Models\Path;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Collection;

class DiscountService
{
    public function __construct(protected PriceCalculator $priceCalculator)
    {
    }

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

    protected function getOperatorText($operator): string
    {
        return match ($operator) {
            '=' => 'مساوی',
            '!=' => 'نامساوی',
            '>' => 'بزرگتر',
            '<' => 'کوچکتر',
            '>=' => 'بزرگتر یا مساوی',
            '<=' => 'کوچکتر یا مساوی',
            default => (string) $operator,
        };
    }

    public function normalizeTargetType($targetType): string
    {
        if (in_array($targetType, ['user', 'course', 'path', 'vip', 'category'], true)) {
            return $targetType;
        }

        return match ($targetType) {
            User::class, 'App\\Models\\User' => 'user',
            Course::class, 'App\\Models\\Course' => 'course',
            Path::class, 'App\\Models\\Path' => 'path',
            Plan::class, 'App\\Models\\Plan' => 'vip',
            'App\\Models\\Category' => 'category',
            default => (string) $targetType,
        };
    }

    protected function normalizeItemType($itemType): ?string
    {
        if (!$itemType) {
            return null;
        }

        if (in_array($itemType, ['course', 'path', 'vip'], true)) {
            return $itemType;
        }

        return match ($itemType) {
            Course::class, 'App\\Models\\Course' => 'course',
            Path::class, 'App\\Models\\Path' => 'path',
            Plan::class, 'App\\Models\\Plan' => 'vip',
            default => $itemType,
        };
    }

    /**
     * Lightweight cart snapshot using original prices (no coupon).
     */
    public function getCartContext(User $user): array
    {
        $carts = $user->carts()->with(['cartable'])->get();
        $items = [];

        foreach ($carts as $cart) {
            $item = $cart->cartable;
            if (!$item) {
                continue;
            }

            $type = $this->cartItemType($cart);
            if (!$type) {
                continue;
            }

            $pricing = $this->priceCalculator->forCartItem($cart, null, $user);
            $categories = [];
            if ($type === 'course' && method_exists($item, 'category')) {
                $categories = $item->category()->pluck('categories.id')->all();
            } elseif ($type === 'path' && method_exists($item, 'category')) {
                $categories = $item->category()->pluck('categories.id')->all();
            }

            $items[] = [
                'cart' => $cart,
                'type' => $type,
                'id' => $item->id,
                'price' => $pricing['original_price'],
                'current_price' => $pricing['final_price'],
                'course_discount_amount' => $pricing['course_discount_amount'],
                'categories' => $categories,
                'course' => $type === 'course' ? ['id' => $item->id, 'categories' => $categories] : null,
                'path' => $type === 'path' ? ['id' => $item->id, 'categories' => $categories] : null,
                'vip' => $type === 'vip' ? ['id' => $item->id] : null,
            ];
        }

        return [
            'items' => $items,
            'total_price' => collect($items)->sum('price'),
            'total_current' => collect($items)->sum('current_price'),
        ];
    }

    public function findActiveByCode(string $code): Discount
    {
        $discount = Discount::query()
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
            ->with(['eligibilities', 'conditions'])
            ->first();

        if (!$discount) {
            throw new DiscountException('not_found', 'کد تخفیف پیدا نشد.');
        }

        if (!$discount->is_active) {
            throw new DiscountException('inactive', 'این کد تخفیف غیرفعال است.');
        }

        return $discount;
    }

    public function assertSchedule(Discount $discount): void
    {
        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new DiscountException('not_started', 'زمان استفاده از این کد هنوز شروع نشده است.');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new DiscountException('expired', 'این کد تخفیف منقضی شده است.');
        }
    }

    public function assertUsageLimits(Discount $discount, User $user, bool $lock = false): void
    {
        $query = $lock
            ? Discount::query()->whereKey($discount->id)->lockForUpdate()
            : Discount::query()->whereKey($discount->id);

        $locked = $query->first() ?: $discount;

        $pendingQuery = Payment::query()
            ->where('discount_code', $locked->code)
            ->where(function ($q) {
                $q->where('status', 0)->orWhere('status', false);
            })
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            });

        if ($locked->usage_limit !== null) {
            $used = $locked->usages()->count() + (clone $pendingQuery)->count();
            if ($used >= $locked->usage_limit) {
                throw new DiscountException('usage_limit', 'سقف استفاده از این کد تکمیل شده است.');
            }
        }

        if ($locked->per_user_limit !== null) {
            $usedByUser = $locked->usages()->where('user_id', $user->id)->count()
                + (clone $pendingQuery)->where('user_id', $user->id)->count();
            if ($usedByUser >= $locked->per_user_limit) {
                throw new DiscountException('per_user_limit', 'شما قبلاً از این کد استفاده کرده‌اید.');
            }
        }
    }

    public function checkEligibilities(Discount $discount, User $user): void
    {
        $this->assertUsageLimits($discount, $user);

        $cartContext = $this->getCartContext($user);
        $items = collect($cartContext['items']);

        if ($items->isEmpty()) {
            throw new DiscountException('empty_cart', 'سبد خرید خالی است.');
        }

        $eligibilities = $discount->eligibilities;
        $userInclusions = $eligibilities->filter(fn ($el) => $this->normalizeTargetType($el->target_type) === 'user' && $el->type === 'inclusion');
        $userExclusions = $eligibilities->filter(fn ($el) => $this->normalizeTargetType($el->target_type) === 'user' && $el->type === 'exclusion');

        if ($userInclusions->isNotEmpty() && !$userInclusions->contains(fn ($el) => (int) $el->target_id === (int) $user->id)) {
            throw new DiscountException('not_eligible_user', 'این کد فقط برای کاربران مجاز تعریف شده است.');
        }

        if ($userExclusions->contains(fn ($el) => (int) $el->target_id === (int) $user->id)) {
            throw new DiscountException('not_eligible_user', 'این کد برای این کاربر غیرفعال است.');
        }

        foreach (['course', 'path', 'vip'] as $scope) {
            $inclusions = $eligibilities->filter(fn ($el) => $this->normalizeTargetType($el->target_type) === $scope && $el->type === 'inclusion');
            if ($inclusions->isEmpty()) {
                continue;
            }

            $exists = $items->contains(function ($item) use ($inclusions, $scope) {
                if ($item['type'] !== $scope) {
                    return false;
                }

                return $inclusions->contains(fn ($el) => (int) $el->target_id === (int) $item['id']);
            });

            if (!$exists) {
                $message = match ($scope) {
                    'path' => 'این کد برای مسیر انتخاب‌شده قابل استفاده نیست.',
                    'vip' => 'این کد برای اشتراک انتخاب‌شده قابل استفاده نیست.',
                    default => 'این کد برای دوره انتخاب‌شده قابل استفاده نیست.',
                };
                throw new DiscountException('not_eligible_item', $message);
            }
        }

        $categoryInclusions = $eligibilities->filter(fn ($el) => $this->normalizeTargetType($el->target_type) === 'category' && $el->type === 'inclusion');
        if ($categoryInclusions->isNotEmpty()) {
            $exists = $items->contains(function ($item) use ($categoryInclusions) {
                $categories = $item['categories'] ?? [];
                return $categoryInclusions->contains(fn ($el) => in_array((int) $el->target_id, array_map('intval', $categories), true));
            });
            if (!$exists) {
                throw new DiscountException('not_eligible_category', 'این کد فقط برای دسته‌بندی‌های مشخصی معتبر است.');
            }
        }
    }

    public function isCartItemEligible(Discount $discount, Cart $cart, User $user): bool
    {
        $item = $cart->cartable;
        if (!$item) {
            return false;
        }

        $eligibilities = $discount->relationLoaded('eligibilities')
            ? $discount->eligibilities
            : $discount->eligibilities()->get();

        if ($item instanceof Course) {
            return $this->matchesCourse($discount, $item, $eligibilities);
        }

        if ($item instanceof Path) {
            return $this->matchesScopedItem($eligibilities, 'path', $item->id, []);
        }

        if ($item instanceof Plan) {
            return $this->matchesScopedItem($eligibilities, 'vip', $item->id, []);
        }

        return false;
    }

    public function matchesCourse(Discount $discount, Course $course, ?Collection $eligibilities = null): bool
    {
        $eligibilities = $eligibilities ?: ($discount->relationLoaded('eligibilities')
            ? $discount->eligibilities
            : $discount->eligibilities()->get());

        $categories = [];
        if ($course->relationLoaded('category')) {
            $categories = $course->category->pluck('id')->map(fn ($id) => (int) $id)->all();
        } elseif (method_exists($course, 'category')) {
            $categories = $course->category()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->matchesScopedItem($eligibilities, 'course', $course->id, $categories);
    }

    protected function matchesScopedItem(Collection $eligibilities, string $itemType, int $itemId, array $categoryIds): bool
    {
        $normalized = $eligibilities->map(function ($el) {
            return [
                'kind' => $el->type,
                'target' => $this->normalizeTargetType($el->target_type),
                'id' => (int) $el->target_id,
            ];
        });

        $exclusions = $normalized->where('kind', 'exclusion');
        if ($exclusions->contains(fn ($el) => $el['target'] === $itemType && $el['id'] === $itemId)) {
            return false;
        }
        if ($itemType === 'course' && $exclusions->contains(fn ($el) => $el['target'] === 'category' && in_array($el['id'], $categoryIds, true))) {
            return false;
        }

        $typeInclusions = $normalized->where('kind', 'inclusion')->where('target', $itemType);
        $categoryInclusions = $normalized->where('kind', 'inclusion')->where('target', 'category');
        $hasItemTargeting = $normalized->contains(fn ($el) => in_array($el['target'], ['course', 'path', 'vip', 'category'], true));

        if (!$hasItemTargeting) {
            return true;
        }

        $matchesType = $typeInclusions->isEmpty()
            ? !$normalized->contains(fn ($el) => $el['kind'] === 'inclusion' && in_array($el['target'], ['course', 'path', 'vip'], true))
            : $typeInclusions->contains(fn ($el) => $el['id'] === $itemId);

        if ($itemType !== 'course') {
            return $matchesType;
        }

        $matchesCategory = $categoryInclusions->isEmpty()
            || $categoryInclusions->contains(fn ($el) => in_array($el['id'], $categoryIds, true));

        if ($typeInclusions->isNotEmpty() && $categoryInclusions->isNotEmpty()) {
            return $matchesType || $matchesCategory;
        }

        if ($typeInclusions->isNotEmpty()) {
            return $matchesType;
        }

        if ($categoryInclusions->isNotEmpty()) {
            return $matchesCategory;
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
        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return false;
        }
        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return false;
        }
        if ($discount->usage_limit !== null && $discount->usages()->count() >= $discount->usage_limit) {
            return false;
        }

        return true;
    }

    public function validateConditionsOrFail(Discount $discount, User $user, Cart $cart): void
    {
        $cartSummary = $this->getCartContext($user);

        foreach ($discount->conditions as $condition) {
            $value = $condition->value;
            $operator = $condition->operator;
            $extra = $condition->extra ?? [];

            switch ($condition->condition_type) {
                case 'min_cart_total':
                case 'max_cart_total':
                    if (!$this->compare($cartSummary['total_price'], $operator, $value)) {
                        $label = $condition->condition_type === 'min_cart_total' ? 'حداقل' : 'حداکثر';
                        throw new DiscountException(
                            'min_purchase',
                            "{$label} مبلغ خرید برای استفاده از این کد {$value} تومان است."
                        );
                    }
                    break;

                case 'min_item_price':
                    $minItemPrice = collect($cartSummary['items'])->min('price');
                    if (!$this->compare($minItemPrice, $operator, $value)) {
                        throw new DiscountException('condition', 'حداقل قیمت آیتم با شرط این کد سازگار نیست.');
                    }
                    break;

                case 'max_item_price':
                    $maxItemPrice = collect($cartSummary['items'])->max('price');
                    if (!$this->compare($maxItemPrice, $operator, $value)) {
                        throw new DiscountException('condition', 'حداکثر قیمت آیتم با شرط این کد سازگار نیست.');
                    }
                    break;

                case 'min_item_count':
                case 'max_item_count':
                    $count = count($cartSummary['items']);
                    if (!$this->compare($count, $operator, $value)) {
                        throw new DiscountException('condition', 'تعداد آیتم‌های سبد با شرط این کد سازگار نیست.');
                    }
                    break;

                case 'first_purchase':
                    if ($user->payments()->whereNotNull('paid_at')->where('status', true)->exists()) {
                        throw new DiscountException('first_purchase', 'این کد فقط برای اولین خرید قابل استفاده است.');
                    }
                    break;

                case 'no_purchase_since':
                    $lastPayment = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)
                        ->latest()
                        ->first();

                    if ($lastPayment && $lastPayment->created_at->gt(now()->subDays($value))) {
                        throw new DiscountException('condition', 'از آخرین خرید شما به اندازه کافی زمان نگذشته است.');
                    }
                    break;

                case 'min_orders_count':
                case 'max_orders_count':
                    $count = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)
                        ->count();
                    if (!$this->compare($count, $operator, $value)) {
                        throw new DiscountException('condition', 'تعداد سفارش‌های کاربر با شرط تعیین‌شده سازگار نیست.');
                    }
                    break;

                case 'day_of_week':
                    $days = $extra['days'] ?? [];
                    if (is_string($days)) {
                        $decoded = json_decode($days, true);
                        $days = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                            ? $decoded
                            : array_map('intval', explode(',', $days));
                    } elseif (is_numeric($days)) {
                        $days = [(int) $days];
                    } elseif (!is_array($days)) {
                        $days = [];
                    }
                    if (!in_array(now()->dayOfWeek, $days)) {
                        throw new DiscountException('condition', 'این کد در روز فعلی هفته قابل استفاده نیست.');
                    }
                    break;

                case 'time_range':
                    $start = $extra['start'] ?? null;
                    $end = $extra['end'] ?? null;
                    $now = now()->format('H:i');
                    if ($start && $end && !($now >= $start && $now <= $end)) {
                        throw new DiscountException('condition', 'این کد در بازه زمانی فعلی قابل استفاده نیست.');
                    }
                    break;

                case 'date_range':
                    $start = $extra['start_date'] ?? null;
                    $end = $extra['end_date'] ?? null;
                    if ($start && is_string($start)) {
                        $start = \Carbon\Carbon::parse($start);
                    }
                    if ($end && is_string($end)) {
                        $end = \Carbon\Carbon::parse($end);
                    }
                    if ($start && now()->lt($start)) {
                        throw new DiscountException('not_started', 'زمان شروع استفاده از این کد هنوز شروع نشده است.');
                    }
                    if ($end && now()->gt($end)) {
                        throw new DiscountException('expired', 'این کد تخفیف منقضی شده است.');
                    }
                    break;

                case 'required_item':
                    $itemId = $condition->target_id;
                    $itemType = $this->normalizeItemType($condition->item_type);
                    $exists = collect($cartSummary['items'])->contains(
                        fn ($item) => (!$itemType || $item['type'] === $itemType) && (int) $item['id'] === (int) $itemId
                    );
                    if (!$exists) {
                        throw new DiscountException('condition', 'آیتم الزامی در سبد خرید شما وجود ندارد.');
                    }
                    break;

                case 'forbidden_item':
                    $itemId = $condition->target_id;
                    $itemType = $this->normalizeItemType($condition->item_type);
                    $exists = collect($cartSummary['items'])->contains(
                        fn ($item) => (!$itemType || $item['type'] === $itemType) && (int) $item['id'] === (int) $itemId
                    );
                    if ($exists) {
                        throw new DiscountException('condition', 'وجود یک آیتم ممنوعه در سبد استفاده از کد را محدود کرده است.');
                    }
                    break;

                case 'required_category':
                    $catId = (int) $value;
                    $exists = collect($cartSummary['items'])->contains(
                        fn ($item) => in_array($catId, array_map('intval', $item['categories'] ?? []), true)
                    );
                    if (!$exists) {
                        throw new DiscountException('condition', 'دسته‌بندی الزامی در سبد شما وجود ندارد.');
                    }
                    break;

                case 'forbidden_category':
                    $catId = (int) $value;
                    $exists = collect($cartSummary['items'])->contains(
                        fn ($item) => in_array($catId, array_map('intval', $item['categories'] ?? []), true)
                    );
                    if ($exists) {
                        throw new DiscountException('condition', 'وجود یک دسته‌بندی ممنوعه در سبد استفاده از کد را محدود کرده است.');
                    }
                    break;

                case 'min_total_spent':
                case 'max_total_spent':
                    $spent = $user->payments()
                        ->whereNotNull('paid_at')
                        ->where('status', true)->sum('amount');
                    if (!$this->compare($spent, $operator, $value)) {
                        throw new DiscountException('condition', 'مجموع مبالغ خرید شما با شرط تعیین‌شده سازگار نیست.');
                    }
                    break;

                case 'purchased_product_before':
                case 'not_purchased_product_before':
                    $itemId = $condition->target_id;
                    $itemType = $this->normalizeItemType($condition->item_type);
                    $exists = $user->payments()->whereNotNull('paid_at')->where('status', true)->get()->contains(function ($payment) use ($itemId, $itemType) {
                        $item = $payment->payable;
                        return ($itemType === 'course' && $item instanceof Course && $item->id == $itemId) ||
                            ($itemType === 'path' && $item instanceof Path && $item->id == $itemId) ||
                            ($itemType === 'vip' && $item instanceof Plan && $item->id == $itemId);
                    });
                    if ($condition->condition_type === 'purchased_product_before' && !$exists) {
                        throw new DiscountException('condition', 'تا کنون این آیتم را خریداری نکرده‌اید.');
                    }
                    if ($condition->condition_type === 'not_purchased_product_before' && $exists) {
                        throw new DiscountException('condition', 'پیش‌تر این آیتم را خریداری کرده‌اید.');
                    }
                    break;

                case 'new_user':
                    $days = $value ?? 7;
                    if ($user->created_at->lt(now()->subDays($days))) {
                        throw new DiscountException('new_user', 'این کد فقط برای کاربران جدید قابل استفاده است.');
                    }
                    break;
            }
        }
    }

    public function calculateDiscountAmount(Discount $discount, Cart $cart): int
    {
        $user = $cart->user;
        $pricing = $this->priceCalculator->forCartItem($cart, $discount, $user);

        return (int) $pricing['coupon_discount_amount'];
    }

    /**
     * Fully validate a coupon against the current cart without mutating it.
     */
    public function validateForCart(Discount $discount, User $user): void
    {
        $this->assertSchedule($discount);
        $this->checkEligibilities($discount, $user);

        $carts = $user->carts()->with('cartable')->get();
        if ($carts->isEmpty()) {
            throw new DiscountException('empty_cart', 'سبد خرید خالی است.');
        }

        $firstCart = $carts->first();
        $this->validateConditionsOrFail($discount, $user, $firstCart);

        $applied = false;
        foreach ($carts as $cart) {
            if ($this->isCartItemEligible($discount, $cart, $user)) {
                $applied = true;
                break;
            }
        }

        if (!$applied) {
            throw new DiscountException('not_applicable', 'این کد برای هیچ‌یک از آیتم‌های سبد شما قابل اعمال نیست.');
        }
    }

    public function apply(Discount $discount, Cart $cart, User $user): array
    {
        $this->assertSchedule($discount);
        $this->checkEligibilities($discount, $user);
        $this->validateConditionsOrFail($discount, $user, $cart);

        $pricing = $this->priceCalculator->forCartItem($cart, $discount, $user);

        return [
            'discount_id' => $discount->id,
            'discount_code' => $discount->code,
            'amount' => $pricing['coupon_discount_amount'],
            'course_discount_amount' => $pricing['course_discount_amount'],
            'total_discount' => $pricing['total_discount'],
            'final_price' => $pricing['final_price'],
            'original_price' => $pricing['original_price'],
        ];
    }

    /**
     * Persist coupon + recalculated prices on all cart rows.
     * Caller must already have validated the coupon.
     */
    public function persistCouponOnCart(User $user, Discount $discount): void
    {
        $carts = $user->carts()->with('cartable')->get();

        foreach ($carts as $cart) {
            $eligibleCoupon = $this->isCartItemEligible($discount, $cart, $user) ? $discount : null;
            $pricing = $this->priceCalculator->forCartItem($cart, $eligibleCoupon, $user);

            $cart->update([
                'discount_id' => $eligibleCoupon?->id,
                'discount_amount' => $pricing['total_discount'],
                'price' => $pricing['final_price'],
            ]);
        }
    }

    public function persistWithoutCoupon(User $user): void
    {
        $carts = $user->carts()->with('cartable')->get();

        foreach ($carts as $cart) {
            $pricing = $this->priceCalculator->forCartItem($cart, null, $user);
            $cart->update([
                'discount_id' => null,
                'discount_amount' => $pricing['total_discount'],
                'price' => $pricing['final_price'],
            ]);
        }
    }

    public function currentCartCoupon(User $user): ?Discount
    {
        $discountId = $user->carts()->whereNotNull('discount_id')->value('discount_id');
        if (!$discountId) {
            return null;
        }

        return Discount::with(['eligibilities', 'conditions'])->find($discountId);
    }

    public function recordUsage(Discount $discount, User $user, $payment): void
    {
        $already = $discount->usages()
            ->where('payment_id', $payment->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($already) {
            return;
        }

        $locked = Discount::query()->whereKey($discount->id)->lockForUpdate()->first();
        if (!$locked) {
            return;
        }

        if ($locked->usage_limit !== null && $locked->usages()->count() >= $locked->usage_limit) {
            throw new DiscountException('usage_limit', 'سقف استفاده از این کد تکمیل شده است.');
        }

        if ($locked->per_user_limit !== null && $locked->usages()->where('user_id', $user->id)->count() >= $locked->per_user_limit) {
            throw new DiscountException('per_user_limit', 'شما قبلاً از این کد استفاده کرده‌اید.');
        }

        $locked->usages()->create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'used_at' => now(),
        ]);
    }

    protected function cartItemType(Cart $cart): ?string
    {
        return match ($cart->cartable_type) {
            Course::class => 'course',
            Path::class => 'path',
            Plan::class => 'vip',
            default => null,
        };
    }
}
