<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGateway
{
    protected string $driver;
    protected array $config;

    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? config('payment.default', 'zarinpal');
        $this->config = config("payment.drivers.{$this->driver}", []);
    }

    /**
     * Create a payment request and return redirect URL
     */
    public function purchase(int $amount, string $callbackUrl, array $options = []): array
    {
        $method = 'purchase' . ucfirst($this->driver);
        
        if (!method_exists($this, $method)) {
            throw new Exception("Gateway '{$this->driver}' is not supported yet.");
        }

        return $this->$method($amount, $callbackUrl, $options);
    }

    /**
     * Verify a payment transaction
     */
    public function verify(int $amount, string $transactionId, array $options = []): array
    {
        $method = 'verify' . ucfirst($this->driver);
        
        if (!method_exists($this, $method)) {
            throw new Exception("Gateway '{$this->driver}' verification is not supported yet.");
        }

        return $this->$method($amount, $transactionId, $options);
    }

    /**
     * Zarinpal Purchase
     */
    protected function purchaseZarinpal(int $amount, string $callbackUrl, array $options = []): array
    {
        $mode = $this->config['mode'] ?? 'normal';
        $merchantId = $this->config['merchantId'] ?? env('ZARINPAL_TOKEN');
        
        $apiUrl = match($mode) {
            'sandbox' => $this->config['sandboxApiPurchaseUrl'] ?? 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl',
            'zaringate' => $this->config['zaringateApiPurchaseUrl'] ?? 'https://ir.zarinpal.com/pg/services/WebGate/wsdl',
            default => $this->config['apiPurchaseUrl'] ?? 'https://api.zarinpal.com/pg/v4/payment/request.json',
        };

        $description = $options['description'] ?? $this->config['description'] ?? 'پرداخت';
        $mobile = $options['mobile'] ?? null;
        $email = $options['email'] ?? null;

        // For JSON API (v4)
        if (str_contains($apiUrl, '.json')) {
            $data = [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'callback_url' => $callbackUrl,
                'description' => $description,
            ];

            if ($mobile) {
                $data['mobile'] = $mobile;
            }
            if ($email) {
                $data['email'] = $email;
            }

            $response = Http::asJson()->post($apiUrl, $data);

            if (!$response->successful()) {
                throw new Exception('Zarinpal request failed: ' . $response->body());
            }

            $result = $response->json();

            if ($result['data']['code'] != 100) {
                throw new Exception('Zarinpal error: ' . ($result['data']['message'] ?? 'Unknown error'));
            }

            $authority = $result['data']['authority'];
            $paymentUrl = match($mode) {
                'sandbox' => ($this->config['sandboxApiPaymentUrl'] ?? 'https://sandbox.zarinpal.com/pg/StartPay/') . $authority,
                'zaringate' => str_replace(':authority', $authority, $this->config['zaringateApiPaymentUrl'] ?? 'https://www.zarinpal.com/pg/StartPay/:authority/ZarinGate'),
                default => ($this->config['apiPaymentUrl'] ?? 'https://www.zarinpal.com/pg/StartPay/') . $authority,
            };

            return [
                'success' => true,
                'authority' => $authority,
                'transaction_id' => $authority,
                'action' => $paymentUrl,
            ];
        }

        // For SOAP API (legacy)
        throw new Exception('SOAP API for Zarinpal is not implemented. Please use JSON API.');
    }

    /**
     * Zarinpal Verify
     */
    protected function verifyZarinpal(int $amount, string $authority, array $options = []): array
    {
        $mode = $this->config['mode'] ?? 'normal';
        $merchantId = $this->config['merchantId'] ?? env('ZARINPAL_TOKEN');
        
        $apiUrl = match($mode) {
            'sandbox' => $this->config['sandboxApiVerificationUrl'] ?? 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl',
            'zaringate' => $this->config['zaringateApiVerificationUrl'] ?? 'https://ir.zarinpal.com/pg/services/WebGate/wsdl',
            default => $this->config['apiVerificationUrl'] ?? 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        };

        // For JSON API (v4)
        if (str_contains($apiUrl, '.json')) {
            $data = [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'authority' => $authority,
            ];

            $response = Http::asJson()->post($apiUrl, $data);

            if (!$response->successful()) {
                throw new Exception('Zarinpal verification failed: ' . $response->body());
            }

            $result = $response->json();

            if ($result['data']['code'] != 100 && $result['data']['code'] != 101) {
                throw new Exception('Zarinpal verification error: ' . ($result['data']['message'] ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'reference_id' => $result['data']['ref_id'] ?? null,
                'card_hash' => $result['data']['card_hash'] ?? null,
                'card_pan' => $result['data']['card_pan'] ?? null,
            ];
        }

        // For SOAP API (legacy)
        throw new Exception('SOAP API for Zarinpal is not implemented. Please use JSON API.');
    }

    /**
     * Zibal Purchase
     */
    protected function purchaseZibal(int $amount, string $callbackUrl, array $options = []): array
    {
        $merchantId = $this->config['merchantId'] ?? env('ZIBAL_TOKEN');
        $apiUrl = $this->config['apiPurchaseUrl'] ?? 'https://gateway.zibal.ir/v1/request';
        
        $data = [
            'merchant' => $merchantId,
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
        ];

        if (isset($options['description'])) {
            $data['description'] = $options['description'];
        }
        if (isset($options['mobile'])) {
            $data['mobile'] = $options['mobile'];
        }

        $response = Http::asJson()->post($apiUrl, $data);

        if (!$response->successful()) {
            throw new Exception('Zibal request failed: ' . $response->body());
        }

        $result = $response->json();

        if ($result['result'] != 100) {
            throw new Exception('Zibal error: ' . ($result['message'] ?? 'Unknown error'));
        }

        $trackId = $result['trackId'];
        $paymentUrl = ($this->config['apiPaymentUrl'] ?? 'https://gateway.zibal.ir/start/') . $trackId;

        return [
            'success' => true,
            'authority' => $trackId,
            'transaction_id' => $trackId,
            'action' => $paymentUrl,
        ];
    }

    /**
     * Zibal Verify
     */
    protected function verifyZibal(int $amount, string $trackId, array $options = []): array
    {
        $merchantId = $this->config['merchantId'] ?? env('ZIBAL_TOKEN');
        $apiUrl = $this->config['apiVerificationUrl'] ?? 'https://gateway.zibal.ir/v1/verify';
        
        $data = [
            'merchant' => $merchantId,
            'trackId' => $trackId,
        ];

        $response = Http::asJson()->post($apiUrl, $data);

        if (!$response->successful()) {
            throw new Exception('Zibal verification failed: ' . $response->body());
        }

        $result = $response->json();

        if ($result['result'] != 100) {
            throw new Exception('Zibal verification error: ' . ($result['message'] ?? 'Unknown error'));
        }

        return [
            'success' => true,
            'reference_id' => $result['refNumber'] ?? null,
            'card_number' => $result['cardNumber'] ?? null,
        ];
    }

    /**
     * Get payment URL for redirect
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl ?? '';
    }

    /**
     * Get transaction ID
     */
    public function getTransactionId(): string
    {
        return $this->transactionId ?? '';
    }
}
