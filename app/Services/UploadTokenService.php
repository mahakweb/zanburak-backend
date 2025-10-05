<?php

namespace App\Services;

class UploadTokenService
{
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function generate(array $claims): array
    {
        $now = now();
        $exp = $now->copy()->addMinutes((int) config('upload.token_ttl_minutes', 20));

        $payload = array_merge($claims, [
            'iat' => $now->getTimestamp(),
            'exp' => $exp->getTimestamp(),
        ]);

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $headerSegment = self::base64UrlEncode(json_encode($header));
        $payloadSegment = self::base64UrlEncode(json_encode($payload));
        $signingInput = $headerSegment . '.' . $payloadSegment;
        $signature = hash_hmac('sha256', $signingInput, (string) config('upload.signing_key'), true);
        $signatureSegment = self::base64UrlEncode($signature);

        $token = $headerSegment . '.' . $payloadSegment . '.' . $signatureSegment;

        return [
            'token' => $token,
            'expires_at' => $exp->toIso8601String(),
        ];
    }
}


