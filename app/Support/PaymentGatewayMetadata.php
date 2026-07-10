<?php

namespace App\Support;

class PaymentGatewayMetadata
{
    public static function resolveVariantId(array $data): ?string
    {
        $driver = $data['driver'] ?? $data['gateway'] ?? null;

        if ($driver !== 'digipay') {
            return $driver ?: null;
        }

        if (!empty($data['digipay_unified'])) {
            return 'digipay-installment';
        }

        if (!empty($data['digipay_mode'])) {
            return 'digipay-' . $data['digipay_mode'];
        }

        if (array_key_exists('digipay_preferred_gateway', $data) && $data['digipay_preferred_gateway'] !== null) {
            $preferred = (int) $data['digipay_preferred_gateway'];

            return match ($preferred) {
                0 => 'digipay-wallet',
                2 => 'digipay-ipg',
                default => 'digipay-' . $preferred,
            };
        }

        return 'digipay-installment';
    }

    public static function applyToPaymentArray(array $data): array
    {
        $driver = $data['driver'] ?? $data['gateway'] ?? null;
        $isUnified = !empty($data['digipay_unified']);

        $payload = [
            'driver' => $driver,
            'gateway_variant' => self::resolveVariantId($data),
            'digipay_mode' => $isUnified ? null : ($data['digipay_mode'] ?? null),
            'digipay_preferred_gateway' => $isUnified
                ? null
                : (array_key_exists('digipay_preferred_gateway', $data)
                    ? ($data['digipay_preferred_gateway'] !== null ? (int) $data['digipay_preferred_gateway'] : null)
                    : null),
        ];

        if ($payload['digipay_mode']) {
            $payload['digipay_preferred_gateway'] = null;
        }

        return $payload;
    }

    public static function isInstallmentGatewayData(array $data): bool
    {
        if (!empty($data['digipay_unified'])) {
            return true;
        }

        if (!empty($data['digipay_mode']) && in_array($data['digipay_mode'], ['credit', 'facilities'], true)) {
            return true;
        }

        $variant = self::resolveVariantId($data);

        return in_array($variant, ['digipay-installment', 'digipay-credit', 'digipay-facilities'], true);
    }

    public static function resolveVariantFromDigipayVerify(array $details): ?string
    {
        $paymentGateway = isset($details['paymentGateway']) ? (int) $details['paymentGateway'] : null;
        $additional = $details['additionalInfo'] ?? [];

        if ($paymentGateway === 0 || (!empty($additional['creditAmount']) && empty($additional['cashAmount']))) {
            // wallet-heavy or explicit wallet path
        }

        if (!empty($additional['creditAmount']) && (int) $additional['creditAmount'] > 0) {
            $cash = (int) ($additional['cashAmount'] ?? 0);
            if ($cash > 0) {
                return 'digipay-installment';
            }

            return 'digipay-credit';
        }

        if ($paymentGateway === 0) {
            return 'digipay-wallet';
        }

        if ($paymentGateway === 2) {
            return 'digipay-ipg';
        }

        return 'digipay-installment';
    }
}
