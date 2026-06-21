<?php

namespace App\Services\Certificate;

use App\Models\Certificate;

class CertificateVerificationService
{
    public function generateToken(Certificate $certificate): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [
                $certificate->uuid,
                $certificate->serial_number,
                $certificate->user_id,
                $certificate->course_id,
            ]),
            config('app.key')
        );
    }

    public function validate(Certificate $certificate, ?string $token = null): bool
    {
        if (! $certificate->isIssued()) {
            return false;
        }

        if ($token === null) {
            return true;
        }

        return hash_equals($certificate->verification_token ?? '', $token);
    }

    public function verifyBySerial(string $serial, ?string $token = null): ?array
    {
        $certificate = Certificate::with(['course:id,title,slug,poster', 'template'])
            ->where('serial_number', $serial)
            ->first();

        if (! $certificate) {
            return null;
        }

        $valid = $this->validate($certificate, $token);

        return [
            'valid' => $valid,
            'forgery_check' => $token !== null ? hash_equals($certificate->verification_token ?? '', $token) : null,
            'certificate' => [
                'serial_number' => $certificate->serial_number,
                'uuid' => $certificate->uuid,
                'certificate_id' => $certificate->id,
                'student_name' => $certificate->user_name,
                'course_name' => $certificate->course_title,
                'instructor_name' => $certificate->instructor_name,
                'completion_date' => $certificate->issued_at?->toIso8601String(),
                'grade' => $certificate->grade,
                'duration' => $certificate->durationFormatted(),
                'status' => $certificate->status,
                'revoked' => $certificate->isRevoked(),
            ],
        ];
    }
}
