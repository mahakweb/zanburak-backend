<?php

namespace App\Services\Certificate;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;

class CertificateQrCodeService
{
    public function verificationUrl(string $serial, ?string $token = null): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $url = $base.'/verify-certificate?serial='.urlencode($serial);

        if ($token) {
            $url .= '&token='.urlencode($token);
        }

        return $url;
    }

    public function pngDataUri(string $data, int $size = 240): string
    {
        try {
            return $this->buildPng($data, $size)->getDataUri();
        } catch (\Throwable) {
            return $this->svgDataUri($data, $size);
        }
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
        try {
            return $this->buildPng($data, $size)->getString();
        } catch (\Throwable) {
            return (new SvgWriter())->write(new QrCode(
                data: $data,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: $size,
                margin: 10,
            ))->getString();
        }
    }

    protected function buildPng(string $data, int $size): ResultInterface
    {
        $qr = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
        );

        return (new PngWriter())->write($qr);
    }
}
