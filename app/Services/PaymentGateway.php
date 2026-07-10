<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
     * DigiPay UPG Purchase (IPG, Wallet, BNPL, Credit)
     */
    protected function purchaseDigipay(int $amount, string $callbackUrl, array $options = []): array
    {
        $rawMobile = $options['mobile'] ?? null;
        if (empty($rawMobile)) {
            throw new Exception('شماره موبایل کاربر برای پرداخت دیجی‌پی الزامی است.');
        }

        $mobile = $this->normalizeDigipayMobile((string) $rawMobile);

        $providerId = $options['provider_id'] ?? $options['providerId'] ?? null;
        if (empty($providerId)) {
            throw new Exception('شناسه پرداخت (providerId) برای دیجی‌پی الزامی است.');
        }

        $ticketType = (int) ($this->config['ticketType'] ?? 11);
        $currency = $this->config['currency'] ?? 'T';
        $apiAmount = $currency === 'T' ? $amount * 10 : $amount;

        $data = [
            'cellNumber' => $mobile,
            'amount' => $apiAmount,
            'providerId' => (string) $providerId,
            'callbackUrl' => $callbackUrl,
        ];

        if (!empty($options['basketDetailsDto'])) {
            $data['basketDetailsDto'] = $options['basketDetailsDto'];
        }

        // Basket-based installment flow: let DigiPay show credit/BNPL/wallet choices.
        // Sending preferredGateway (especially 0 = wallet) forces wallet cash-in instead.
        if (
            empty($data['basketDetailsDto'])
            && isset($options['preferredGateway'])
        ) {
            $data['additionalInfo'] = [
                'preferredGateway' => (int) $options['preferredGateway'],
            ];
        }

        $apiUrl = $this->config['apiPurchaseUrl'] ?? 'https://api.mydigipay.com/digipay/api/tickets/business';
        $token = $this->digipayOauthToken();

        $response = Http::withHeaders([
            'Agent' => 'WEB',
            'Digipay-Version' => $this->config['digipayVersion'] ?? '2022-02-02',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($apiUrl . '?type=' . $ticketType, $data);

        if (!$response->successful()) {
            throw new Exception('DigiPay request failed: ' . $response->body());
        }

        $result = $response->json();
        $status = $result['result']['status'] ?? null;

        if ($status !== 0 && $status !== '0') {
            throw new Exception('DigiPay error: ' . ($result['result']['message'] ?? 'Unknown error'));
        }

        $ticket = $result['ticket'] ?? null;
        $redirectUrl = $result['redirectUrl'] ?? null;

        if (empty($ticket) || empty($redirectUrl)) {
            throw new Exception('پاسخ نامعتبر از دیجی‌پی دریافت شد.');
        }

        return [
            'success' => true,
            'authority' => $ticket,
            'transaction_id' => $ticket,
            'provider_id' => (string) $providerId,
            'action' => $redirectUrl,
        ];
    }

    /**
     * DigiPay UPG Verify
     */
    protected function verifyDigipay(int $amount, string $transactionId, array $options = []): array
    {
        $trackingCode = $options['trackingCode'] ?? $options['tracking_code'] ?? null;
        $providerId = $options['providerId'] ?? $options['provider_id'] ?? null;
        $ticketType = $options['type'] ?? $options['digipay_type'] ?? null;
        $result = $options['result'] ?? $options['payment_result'] ?? null;

        if ($result && strtoupper((string) $result) !== 'SUCCESS') {
            throw new Exception('پرداخت دیجی‌پی ناموفق بود.');
        }

        if (empty($trackingCode) || empty($providerId) || $ticketType === null) {
            throw new Exception('اطلاعات بازگشتی دیجی‌پی ناقص است.');
        }

        if (isset($options['callback_amount'])) {
            $currency = $this->config['currency'] ?? 'T';
            $expectedAmount = $currency === 'T' ? $amount * 10 : $amount;
            if ((int) $options['callback_amount'] !== (int) $expectedAmount) {
                throw new Exception('مبلغ بازگشتی دیجی‌پی با سفارش مطابقت ندارد.');
            }
        }

        $apiUrl = $this->config['apiVerificationUrl'] ?? 'https://api.mydigipay.com/digipay/api/purchases/verify';
        $token = $this->digipayOauthToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($apiUrl . '?type=' . (int) $ticketType, [
            'trackingCode' => (string) $trackingCode,
            'providerId' => (string) $providerId,
        ]);

        if (!$response->successful()) {
            throw new Exception('DigiPay verification failed: ' . $response->body());
        }

        $body = $response->json();
        $status = $body['result']['status'] ?? null;

        if ($status !== 0 && $status !== '0') {
            throw new Exception('DigiPay verification error: ' . ($body['result']['message'] ?? 'Unknown error'));
        }

        return [
            'success' => true,
            'reference_id' => $body['trackingCode'] ?? $trackingCode,
            'digipay_type' => (int) $ticketType,
            'digipay_details' => $body,
        ];
    }

    /**
     * DigiPay deliver (required for CREDIT/BNPL after verify)
     */
    public function deliverDigipay(string $trackingCode, int $type, array $products, ?string $invoiceNumber = null): array
    {
        $apiUrl = $this->config['apiDeliverUrl'] ?? 'https://api.mydigipay.com/digipay/api/purchases/deliver';
        $token = $this->digipayOauthToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($apiUrl . '?type=' . $type, [
            'deliveryDate' => (int) round(microtime(true) * 1000),
            'invoiceNumber' => $invoiceNumber ?? ('INV-' . now()->format('YmdHis')),
            'trackingCode' => $trackingCode,
            'products' => $products,
        ]);

        if (!$response->successful()) {
            throw new Exception('DigiPay deliver failed: ' . $response->body());
        }

        $body = $response->json();
        $status = $body['result']['status'] ?? null;

        if ($status !== 0 && $status !== '0') {
            throw new Exception('DigiPay deliver error: ' . ($body['result']['message'] ?? 'Unknown error'));
        }

        return $body;
    }

    protected function digipayOauthToken(): string
    {
        $cacheKey = 'digipay_oauth_token_' . md5(
            ($this->config['client_id'] ?? '') . ':' . ($this->config['username'] ?? '')
        );

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $clientId = $this->config['client_id'] ?? env('DIGIPAY_CLIENT_ID');
        $clientSecret = $this->config['client_secret'] ?? env('DIGIPAY_CLIENT_SECRET');
        $username = $this->config['username'] ?? env('DIGIPAY_USERNAME');
        $password = $this->config['password'] ?? env('DIGIPAY_PASSWORD');

        $missing = array_filter([
            empty($clientId) ? 'DIGIPAY_CLIENT_ID' : null,
            empty($clientSecret) ? 'DIGIPAY_CLIENT_SECRET' : null,
            empty($username) ? 'DIGIPAY_USERNAME' : null,
            empty($password) ? 'DIGIPAY_PASSWORD' : null,
        ]);

        if ($missing !== []) {
            $missingList = implode('، ', $missing);
            throw new Exception(
                'تنظیمات دیجی‌پی ناقص است (' . $missingList . '). '
                . 'client_id و client_secret برای هدر Authorization هستند؛ '
                . 'username و password همان نام کاربری و رمز ورود پنل پذیرندگی mydigipay.com هستند (۴ مقدار جداگانه).'
            );
        }

        $oauthUrl = $this->config['apiOauthUrl'] ?? 'https://api.mydigipay.com/digipay/api/oauth/token';

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
        ])->asForm()->post($oauthUrl, [
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            $apiMessage = $body['result']['message'] ?? $body['error'] ?? null;

            if ($response->status() === 401) {
                if (isset($body['error']) && !isset($body['result'])) {
                    throw new Exception(
                        'client_id یا client_secret دیجی‌پی اشتباه است.'
                        . ($apiMessage ? ' (' . $apiMessage . ')' : '')
                    );
                }

                throw new Exception(
                    'نام کاربری یا رمز عبور API دیجی‌پی اشتباه است. '
                    . 'username باید نام کاربری پنل (مثلاً milad4970) باشد، نه کد ملی یا موبایل.'
                    . ($apiMessage ? ' پیام دیجی‌پی: ' . $apiMessage : '')
                );
            }
            throw new Exception('خطا در احراز هویت دیجی‌پی: ' . $response->body());
        }

        $body = $response->json();
        $token = $body['access_token'] ?? null;
        $expiresIn = (int) ($body['expires_in'] ?? 3500);

        if (empty($token)) {
            throw new Exception('توکن احراز هویت دیجی‌پی دریافت نشد.');
        }

        Cache::put($cacheKey, $token, max(60, $expiresIn - 60));

        return $token;
    }

    protected function normalizeDigipayMobile(string $mobile): string
    {
        $mobile = trim($mobile);
        if ($mobile === '') {
            throw new Exception('شماره موبایل کاربر برای پرداخت دیجی‌پی الزامی است.');
        }

        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        if (!preg_match('/^09\d{9}$/', $digits)) {
            throw new Exception('فرمت شماره موبایل برای دیجی‌پی نامعتبر است. شماره باید موبایل ایران با فرمت 09xxxxxxxxx باشد.');
        }

        return $digits;
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
