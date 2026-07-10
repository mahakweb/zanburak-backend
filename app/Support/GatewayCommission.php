<?php

namespace App\Support;

class GatewayCommission
{
    public static function resolvePercent(array $data): float
    {
        if (($data['payment_method'] ?? 'bank') === 'wallet') {
            return 0.0;
        }

        $driver = $data['driver'] ?? $data['gateway'] ?? null;
        if (!$driver) {
            return 0.0;
        }

        if ($driver !== 'digipay') {
            return (float) config('payment.gateway_commissions.drivers.' . $driver, 0);
        }

        $digipay = config('payment.gateway_commissions.digipay', []);

        if (!empty($data['digipay_unified'])) {
            return (float) ($digipay['unified'] ?? 15);
        }

        if (!empty($data['digipay_mode'])) {
            return (float) ($digipay['modes'][$data['digipay_mode']] ?? 0);
        }

        if (array_key_exists('digipay_preferred_gateway', $data) && $data['digipay_preferred_gateway'] !== null) {
            $preferred = (string) (int) $data['digipay_preferred_gateway'];

            return (float) ($digipay['preferred_gateways'][$preferred] ?? 0);
        }

        if ($driver === 'digipay') {
            return (float) ($digipay['unified'] ?? 15);
        }

        return 0.0;
    }

    public static function applyToAmount(int $baseAmount, float $percent): array
    {
        if ($percent <= 0 || $baseAmount <= 0) {
            return [
                'base_amount' => $baseAmount,
                'fee_amount' => 0,
                'charged_amount' => $baseAmount,
            ];
        }

        $feeAmount = (int) round($baseAmount * $percent / 100);

        return [
            'base_amount' => $baseAmount,
            'fee_amount' => $feeAmount,
            'charged_amount' => $baseAmount + $feeAmount,
        ];
    }

    public static function percentForPayable(float $commissionPercent, $payable, array $options): float
    {
        if ($commissionPercent <= 0) {
            return 0.0;
        }

        if (PaymentGatewayMetadata::isInstallmentGatewayData($options)) {
            return ($payable && (bool) ($payable->allows_installment ?? false))
                ? $commissionPercent
                : 0.0;
        }

        return $commissionPercent;
    }
}
