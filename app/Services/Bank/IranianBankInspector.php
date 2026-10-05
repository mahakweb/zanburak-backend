<?php

namespace App\Services\Bank;

use App\Support\IranianBanks;

class IranianBankInspector
{
    /**
     * @return array{
     *     kind: string,
     *     normalized: string,
     *     formatted: string,
     *     bank: ?array,
     *     detected: bool,
     *     valid: bool,
     *     verified: bool,
     *     message: string
     * }
     */
    public function inspect(string $raw, string $kind, ?string $bankCode = null): array
    {
        $kind = in_array($kind, ['sheba', 'card', 'account'], true) ? $kind : 'sheba';
        $normalized = $this->normalize($raw, $kind);
        $bank = null;
        $detected = false;
        $valid = false;
        $message = 'شماره را کامل وارد کنید.';

        if ($kind === 'sheba') {
            $bankCodeFromNumber = strlen($normalized) >= 7 ? substr($normalized, 4, 3) : null;
            $bank = IranianBanks::find($bankCodeFromNumber);
            $detected = (bool) $bank;
            if ($normalized === '' || $normalized === 'IR') {
                $message = 'شماره شبا را وارد کنید.';
            } elseif (! preg_match('/^IR\d{0,24}$/', $normalized)) {
                $message = 'شبا فقط با IR و رقم ساخته می‌شود.';
            } elseif (strlen($normalized) < 26) {
                $message = $bank
                    ? $bank['name'].' شناسایی شد. رقم‌های باقی‌مانده را وارد کنید.'
                    : 'با کامل شدن رقم‌ها بانک مشخص می‌شود.';
            } elseif (! $this->shebaChecksum($normalized)) {
                $message = 'رقم کنترلی شبا نادرست است.';
            } elseif (! $bank) {
                $message = 'کد بانک این شبا در فهرست بانک‌ها نیست.';
            } else {
                $valid = true;
                $message = 'شبا با الگوریتم کنترل بانکی تأیید شد.';
            }
        } elseif ($kind === 'card') {
            $bin = strlen($normalized) >= 6 ? substr($normalized, 0, 6) : null;
            $bank = $bin ? IranianBanks::find(IranianBanks::cardBins()[$bin] ?? null) : null;
            $detected = (bool) $bank;
            if ($normalized === '') {
                $message = 'شماره کارت را وارد کنید.';
            } elseif (! preg_match('/^\d+$/', $normalized)) {
                $message = 'شماره کارت فقط رقم است.';
            } elseif (strlen($normalized) < 16) {
                $message = $bank
                    ? $bank['name'].' شناسایی شد. رقم‌های کارت را کامل کنید.'
                    : (strlen($normalized) >= 6 ? 'این الگو به بانک شناخته‌شده‌ای نمی‌خورد.' : 'با شش رقم اول، بانک مشخص می‌شود.');
            } elseif (strlen($normalized) > 16) {
                $message = 'شماره کارت ۱۶ رقم است.';
            } elseif (! $this->luhn($normalized)) {
                $message = 'رقم کنترلی کارت نادرست است.';
            } elseif (! $bank) {
                $message = 'الگوی این کارت در فهرست بانک‌ها نیست.';
            } else {
                $valid = true;
                $message = 'کارت با الگوریتم لان تأیید شد.';
            }
        } else {
            $bank = IranianBanks::find($bankCode);
            $detected = (bool) $bank;
            if ($normalized === '') {
                $message = 'شماره حساب را وارد کنید.';
            } elseif (! preg_match('/^\d+$/', $normalized)) {
                $message = 'شماره حساب فقط رقم است.';
            } elseif (strlen($normalized) < 6 || strlen($normalized) > 18) {
                $message = 'شماره حساب باید بین ۶ تا ۱۸ رقم باشد.';
            } elseif (! $bank) {
                $message = 'بانک این حساب را انتخاب کنید.';
            } else {
                $valid = true;
                $message = 'شماره حساب و بانک پذیرفته شد.';
            }
        }

        return [
            'kind' => $kind,
            'normalized' => $normalized,
            'formatted' => $this->format($kind, $normalized),
            'bank' => $bank,
            'detected' => $detected,
            'valid' => $valid,
            'verified' => $valid,
            'message' => $message,
        ];
    }

    public function normalize(string $raw, string $kind): string
    {
        $value = $this->toEnglishDigits($raw);
        $value = strtoupper(preg_replace('/\s+/', '', $value) ?? '');

        if ($kind === 'sheba') {
            $value = preg_replace('/[^A-Z0-9]/', '', $value) ?? '';
            if ($value !== '' && ! str_starts_with($value, 'IR')) {
                $value = 'IR'.preg_replace('/\D/', '', $value);
            }
            if (str_starts_with($value, 'IR')) {
                $value = 'IR'.substr(preg_replace('/\D/', '', substr($value, 2)) ?? '', 0, 24);
            }

            return $value;
        }

        return substr(preg_replace('/\D/', '', $value) ?? '', 0, $kind === 'card' ? 16 : 18);
    }

    public function format(string $kind, string $normalized): string
    {
        if ($kind === 'sheba') {
            return trim(implode(' ', str_split($normalized, 4)));
        }
        if ($kind === 'card') {
            return trim(implode(' ', str_split($normalized, 4)));
        }

        return $normalized;
    }

    public function shebaChecksum(string $iban): bool
    {
        if (! preg_match('/^IR\d{24}$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $converted = preg_replace_callback('/[A-Z]/', function ($match) {
            return (string) (ord($match[0]) - 55);
        }, $rearranged);

        $remainder = 0;
        $length = strlen($converted);
        for ($i = 0; $i < $length; $i++) {
            $remainder = ($remainder * 10 + (int) $converted[$i]) % 97;
        }

        return $remainder === 1;
    }

    public function luhn(string $digits): bool
    {
        if (! preg_match('/^\d+$/', $digits)) {
            return false;
        }

        $sum = 0;
        $alternate = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alternate) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }

    private function toEnglishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
