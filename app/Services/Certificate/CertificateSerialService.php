<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use Illuminate\Support\Str;

class CertificateSerialService
{
    /**
     * Human-readable unique serial: ZNB-20260615-A1B2C3
     */
    public function generate(): string
    {
        do {
            $serial = sprintf(
                'ZNB-%s-%s',
                now()->format('Ymd'),
                strtoupper(Str::random(6))
            );
        } while (Certificate::where('serial_number', $serial)->exists());

        return $serial;
    }
}
