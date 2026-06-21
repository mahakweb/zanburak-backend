<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Support\Certificate\CertificateAssetHelper;
use App\Support\Certificate\CertificateConstants;
use App\Support\Certificate\CertificateFontService;

class CertificateRenderService
{
    public function __construct(
        protected CertificateQrCodeService $qr,
        protected CertificateFontService $fonts,
    ) {}

    public function buildPayload(Certificate $certificate, ?CertificateTemplate $template = null): array
    {
        $certificate->loadMissing(['course.teacher', 'template']);
        $template = $template ?? $certificate->template ?? CertificateTemplate::where('is_default', true)->first();

        $serial = $certificate->serial_number ?? $certificate->uuid;
        $verifyUrl = $this->qr->verificationUrl($serial, $certificate->verification_token);
        $layout = $template?->resolvedLayout() ?? CertificateConstants::DEFAULT_LAYOUT;

        return [
            'certificate' => $certificate,
            'template' => $template,
            'layout' => $layout,
            'settings' => $template?->resolvedSettings() ?? CertificateConstants::DEFAULT_SETTINGS,
            'placeholders' => [
                'student_name' => $certificate->user_name,
                'course_name' => $certificate->course_title,
                'completion_date' => $certificate->issued_at
                    ? $certificate->issued_at->format('Y/m/d')
                    : now()->format('Y/m/d'),
                'certificate_serial' => $serial,
                'certificate_id' => (string) $certificate->id,
                'instructor_name' => $certificate->instructor_name
                    ?? trim(($certificate->course?->teacher?->first_name ?? '').' '.($certificate->course?->teacher?->last_name ?? '')),
                'duration' => $certificate->durationFormatted(),
                'grade' => $certificate->grade !== null ? (string) $certificate->grade : null,
            ],
            'qr_data_uri' => $this->qr->pngDataUri($verifyUrl, 200),
            'background_url' => $this->storageAssetUrl($template?->background_image),
            'logo_url' => $this->storageAssetUrl($template?->logo_image),
            'signature_url' => $this->storageAssetUrl($template?->signature_image),
            'verify_url' => $verifyUrl,
        ];
    }

    /** Payload shape consumed by CertificateRenderer.vue on the frontend. */
    public function toFrontendPayload(array $payload): array
    {
        $template = $payload['template'];
        $orientation = $template?->orientation ?? 'landscape';
        $defaults = CertificateConstants::defaultCanvasForOrientation($orientation);

        return [
            'placeholders' => $payload['placeholders'],
            'layout' => $payload['layout'],
            'settings' => $payload['settings'],
            'background_url' => $payload['background_url'],
            'logo_url' => $payload['logo_url'],
            'signature_url' => $payload['signature_url'],
            'qr_data_uri' => $payload['qr_data_uri'],
            'verify_url' => $payload['verify_url'],
            'orientation' => $orientation,
            'canvas_width' => (int) ($template?->canvas_width ?: $defaults['width']),
            'canvas_height' => (int) ($template?->canvas_height ?: $defaults['height']),
            'fonts' => $this->fonts->catalogForLayout($payload['layout']),
        ];
    }

    protected function storageAssetUrl(?string $path): ?string
    {
        return CertificateAssetHelper::publicUrl($path);
    }
}
