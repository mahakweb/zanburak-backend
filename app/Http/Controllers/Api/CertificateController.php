<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\Certificate\CertificatePdfService;
use App\Services\Certificate\CertificateQrCodeService;
use App\Services\Certificate\CertificateVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateVerificationService $verification,
        protected CertificatePdfService $pdf,
        protected CertificateQrCodeService $qr,
    ) {}

    public function index($uuid)
    {
        $certificate = Certificate::with(['user', 'course.teacher', 'template'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (! $certificate->isIssued()) {
            return response()->json(['message' => 'Certificate not available'], 404);
        }

        return response()->json([
            'message' => 'Success',
            'certificate' => $this->formatCertificate($certificate),
        ]);
    }

    public function verify(Request $request)
    {
        $serial = trim((string) $request->input('serial', ''));
        if ($serial === '') {
            return response()->json(['message' => 'Serial required', 'valid' => false], 422);
        }

        $result = $this->verification->verifyBySerial($serial);
        if (! $result) {
            return response()->json([
                'message' => 'Not found',
                'valid' => false,
                'certificate' => null,
            ]);
        }

        return response()->json([
            'message' => $result['valid'] ? 'Valid certificate' : 'Invalid certificate',
            ...$result,
        ]);
    }

    public function renderData($uuid)
    {
        $certificate = Certificate::with(['course.teacher', 'template'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (! $certificate->isIssued()) {
            return response()->json(['message' => 'Certificate not available'], 404);
        }

        $payload = $this->pdf->buildPayload($certificate);

        return response()->json([
            'message' => 'Success',
            'render' => [
                'placeholders' => $payload['placeholders'],
                'layout' => $payload['layout'],
                'settings' => $payload['settings'],
                'background_url' => $payload['background_url'],
                'logo_url' => $payload['logo_url'],
                'signature_url' => $payload['signature_url'],
                'qr_data_uri' => $payload['qr_data_uri'],
                'verify_url' => $payload['verify_url'],
                'orientation' => $payload['template']?->orientation ?? 'landscape',
            ],
        ]);
    }

    public function downloadPdf($uuid)
    {
        $certificate = Certificate::with(['course.teacher', 'template'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (! $certificate->isIssued()) {
            abort(404);
        }

        if ($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path)) {
            return Storage::disk('public')->download(
                $certificate->pdf_path,
                'certificate-'.$certificate->serial_number.'.pdf'
            );
        }

        $binary = $this->pdf->renderPdf($certificate);
        $filename = 'certificate-'.($certificate->serial_number ?? $certificate->uuid).'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function downloadQr($uuid)
    {
        $certificate = Certificate::where('uuid', $uuid)->firstOrFail();
        $serial = $certificate->serial_number ?? $certificate->uuid;
        $binary = $this->qr->pngBinary($this->qr->verificationUrl($serial));

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-'.$serial.'.png"',
        ]);
    }

    protected function formatCertificate(Certificate $certificate): array
    {
        $serial = $certificate->serial_number ?? $certificate->uuid;

        return [
            'uuid' => $certificate->uuid,
            'serial_number' => $certificate->serial_number,
            'user_name' => $certificate->user_name,
            'course_title' => $certificate->course_title,
            'instructor_name' => $certificate->instructor_name,
            'grade' => $certificate->grade,
            'duration' => $certificate->durationFormatted(),
            'time_completed' => $certificate->time_completed,
            'issued_at' => $certificate->issued_at,
            'status' => $certificate->status,
            'verification_token' => $certificate->verification_token,
            'verify_url' => $this->qr->verificationUrl($serial),
            'user' => $certificate->user?->only('first_name', 'last_name', 'username', 'profile_pic'),
            'course' => array_merge(
                $certificate->course?->only('title', 'english_title', 'slug', 'poster') ?? [],
                ['teacher' => $certificate->course?->teacher?->only('first_name', 'last_name', 'username', 'profile_pic')]
            ),
            'template' => $certificate->template?->only('id', 'uuid', 'name', 'orientation', 'background_image', 'logo_image', 'signature_image'),
        ];
    }

    // Legacy panel endpoint kept for backward compatibility
    public function certifications(Request $request)
    {
        $filter = $request->input('filter', 'online');
        $user = auth('api')->user();

        $query = match ($filter) {
            'online' => $user->certificates()->where('status', 'issued'),
            'tech' => $user->certificates()->where('id', null),
        };

        $perPage = (int) $request->input('perPage', 8);
        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'data' => collect($paginated->items())->map(fn ($item) => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'serial_number' => $item->serial_number,
                'issued_at' => $item->issued_at,
                'user_name' => $item->user_name,
                'course_title' => $item->course_title,
                'time_completed' => $item->time_completed,
                'grade' => $item->grade,
                'status' => $item->status,
            ]),
            'pagination' => [
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }
}
