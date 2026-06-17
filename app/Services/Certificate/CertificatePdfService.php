<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public function __construct(
        protected CertificateQrCodeService $qr
    ) {}

    public function buildPayload(Certificate $certificate, ?CertificateTemplate $template = null): array
    {
        $certificate->loadMissing(['course.teacher', 'template']);
        $template = $template ?? $certificate->template ?? CertificateTemplate::where('is_default', true)->first();

        $serial = $certificate->serial_number ?? $certificate->uuid;
        $verifyUrl = $this->qr->verificationUrl($serial);

        return [
            'certificate' => $certificate,
            'template' => $template,
            'layout' => $template?->resolvedLayout() ?? \App\Support\Certificate\CertificateConstants::DEFAULT_LAYOUT,
            'settings' => $template?->resolvedSettings() ?? \App\Support\Certificate\CertificateConstants::DEFAULT_SETTINGS,
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
            'background_url' => $this->assetUrl($template?->background_image),
            'logo_url' => $this->assetUrl($template?->logo_image),
            'signature_url' => $this->assetUrl($template?->signature_image),
            'verify_url' => $verifyUrl,
        ];
    }

    public function renderPdf(Certificate $certificate, ?CertificateTemplate $template = null): string
    {
        $payload = $this->buildPayload($certificate, $template);

        return Pdf::loadView('certificates.pdf', $payload)
            ->setPaper('a4', $payload['template']?->orientation === 'portrait' ? 'portrait' : 'landscape')
            ->output();
    }

    public function storePdf(Certificate $certificate, ?CertificateTemplate $template = null): string
    {
        $binary = $this->renderPdf($certificate, $template);
        $path = sprintf(
            'certificates/pdf/%s/%s.pdf',
            now()->format('Y/m'),
            $certificate->uuid
        );

        Storage::disk('public')->put($path, $binary);
        $certificate->update(['pdf_path' => $path]);

        return $path;
    }

    protected function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
