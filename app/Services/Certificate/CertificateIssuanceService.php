<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateIssuanceService
{
    public function __construct(
        protected CertificateSerialService $serials,
        protected CertificateVerificationService $verification,
    ) {}

    public function issueForCourse(User $user, Course $course, ?float $grade = null): ?Certificate
    {
        if (! $course->certificate_enabled) {
            return null;
        }

        $existing = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', '!=', 'revoked')
            ->first();

        if ($existing) {
            return $existing;
        }

        $course->loadMissing('teacher');
        $template = $this->resolveTemplate($course);

        do {
            $uuid = (string) Str::uuid();
        } while (Certificate::where('uuid', $uuid)->exists());

        $serial = $this->serials->generate();
        $instructor = trim(($course->teacher?->first_name ?? '').' '.($course->teacher?->last_name ?? ''));

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template?->id,
            'uuid' => $uuid,
            'serial_number' => $serial,
            'user_name' => trim($user->first_name.' '.$user->last_name) ?: $user->username,
            'course_title' => $course->title,
            'instructor_name' => $instructor ?: null,
            'grade' => $grade,
            'time_completed' => $course->totalTime(),
            'issued_at' => now(),
            'status' => 'issued',
        ]);

        $certificate->verification_token = $this->verification->generateToken($certificate);
        $certificate->save();

        return $certificate->fresh(['template', 'course']);
    }

    public function issueManually(User $user, Course $course, ?float $grade = null, ?int $timeCompleted = null, ?int $templateId = null): Certificate
    {
        $existing = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', '!=', 'revoked')
            ->first();

        if ($existing) {
            return $this->finalizeIssuance($existing, $course, $grade, $timeCompleted, $templateId);
        }

        $course->loadMissing('teacher');
        $template = $this->resolveTemplateForIssuance($course, $templateId);

        do {
            $uuid = (string) Str::uuid();
        } while (Certificate::where('uuid', $uuid)->exists());

        $serial = $this->serials->generate();
        $instructor = trim(($course->teacher?->first_name ?? '').' '.($course->teacher?->last_name ?? ''));

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template?->id,
            'uuid' => $uuid,
            'serial_number' => $serial,
            'user_name' => trim($user->first_name.' '.$user->last_name) ?: $user->username,
            'course_title' => $course->title,
            'instructor_name' => $instructor ?: null,
            'grade' => $grade,
            'time_completed' => $timeCompleted ?? $course->totalTime(),
            'issued_at' => now(),
            'status' => 'issued',
        ]);

        $certificate->verification_token = $this->verification->generateToken($certificate);
        $certificate->save();

        return $certificate->fresh(['template', 'course']);
    }

    public function finalizeIssuance(Certificate $certificate, Course $course, ?float $grade, ?int $timeCompleted, ?int $templateId = null): Certificate
    {
        $template = $this->resolveTemplateForIssuance($course, $templateId, $certificate);
        $updates = [
            'issued_at' => $certificate->issued_at ?? now(),
            'status' => 'issued',
            'revoked_at' => null,
        ];

        if (! $certificate->serial_number) {
            $updates['serial_number'] = $this->serials->generate();
        }
        if ($grade !== null) {
            $updates['grade'] = $grade;
        }
        if ($timeCompleted !== null) {
            $updates['time_completed'] = $timeCompleted;
        }
        if ($templateId !== null) {
            $updates['certificate_template_id'] = $template?->id;
        } elseif ($template && ! $certificate->certificate_template_id) {
            $updates['certificate_template_id'] = $template->id;
        }

        $certificate->fill($updates);
        if (! $certificate->verification_token) {
            $updates['verification_token'] = $this->verification->generateToken($certificate);
        }

        $certificate->update($updates);

        return $certificate->fresh(['template', 'course']);
    }

    public function revoke(Certificate $certificate): Certificate
    {
        $certificate->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        return $certificate->fresh(['template', 'course']);
    }

    public function backfill(Certificate $certificate): Certificate
    {
        $certificate->loadMissing(['course.teacher', 'template']);
        $course = $certificate->course;
        $template = $this->resolveTemplate($course);

        $updates = [];
        if (! $certificate->serial_number) {
            $updates['serial_number'] = $this->serials->generate();
        }
        if (! $certificate->status) {
            $updates['status'] = $certificate->issued_at ? 'issued' : 'pending';
        }
        if ($certificate->issued_at && $certificate->status !== 'revoked') {
            $updates['status'] = 'issued';
        }
        if (! $certificate->instructor_name && $course?->teacher) {
            $updates['instructor_name'] = trim($course->teacher->first_name.' '.$course->teacher->last_name) ?: null;
        }
        if ($template && ! $certificate->certificate_template_id) {
            $updates['certificate_template_id'] = $template->id;
        }

        if ($updates) {
            $certificate->fill($updates);
        }
        if (! $certificate->verification_token) {
            $updates['verification_token'] = $this->verification->generateToken($certificate);
        }

        if ($updates) {
            $certificate->update($updates);
        }

        return $certificate->fresh(['template', 'course']);
    }

    public function resolveTemplate(Course $course): ?CertificateTemplate
    {
        if ($course->certificate_template_id) {
            return CertificateTemplate::where('id', $course->certificate_template_id)
                ->where('is_active', true)
                ->first();
        }

        return CertificateTemplate::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function resolveTemplateForIssuance(Course $course, ?int $templateId = null, ?Certificate $certificate = null): ?CertificateTemplate
    {
        if ($templateId !== null) {
            return CertificateTemplate::where('id', $templateId)
                ->where('is_active', true)
                ->first();
        }

        if ($certificate?->certificate_template_id) {
            return CertificateTemplate::where('id', $certificate->certificate_template_id)
                ->where('is_active', true)
                ->first();
        }

        return $this->resolveTemplate($course);
    }
}
