<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTagName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim((string) $value);

        if ($name === '') {
            $fail('نام تگ نمی‌تواند خالی باشد.');

            return;
        }

        if (mb_strlen($name) < 2) {
            $fail('نام تگ باید حداقل ۲ کاراکتر باشد.');

            return;
        }

        if (mb_strlen($name) > 20) {
            $fail('نام تگ نباید بیشتر از ۲۰ کاراکتر باشد.');

            return;
        }

        if (preg_match('/\s/u', $name)) {
            $fail('نام تگ نمی‌تواند شامل فاصله باشد. از خط‌تیره (-) استفاده کنید.');

            return;
        }

        if (!preg_match('/^[\p{L}\p{N}_-]+$/u', $name)) {
            $fail('نام تگ فقط می‌تواند شامل حروف، اعداد، خط‌تیره و زیرخط باشد.');

            return;
        }

        if (preg_match('/^[-_]|[-_]$/u', $name)) {
            $fail('نام تگ نمی‌تواند با خط‌تیره یا زیرخط شروع یا تمام شود.');
        }
    }
}
