<?php

namespace App\Services\Certificate;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class CertificateQrCodeService
{
    public function verificationUrl(string $serial): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');

        return $base.'/verify-certificate?serial='.urlencode($serial);
    }

    public function pngDataUri(string $data, int $size = 240): string
    {
        $qr = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 8,
        );

        return (new PngWriter())->write($qr)->getDataUri();
    }

    public function svgDataUri(string $data, int $size = 240): string
    {
        $qr = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 8,
        );

        return (new SvgWriter())->write($qr)->getDataUri();
    }

    public function pngBinary(string $data, int $size = 400): string
    {
        $qr = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
        );

        return (new PngWriter())->write($qr)->getString();
    }
}
