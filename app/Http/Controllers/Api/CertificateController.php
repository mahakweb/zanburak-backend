<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Services\Certificate\CertificateQrCodeService;
use App\Services\Certificate\CertificateRenderService;
use App\Services\Certificate\CertificateVerificationService;
use App\Support\Certificate\CertificateAssetHelper;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateVerificationService $verification,
        protected CertificateRenderService $render,
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

    public function renderData(Request $request, $uuid)
    {
        $certificate = Certificate::with(['course.teacher', 'template'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (! $certificate->isIssued()) {
            return response()->json(['message' => 'Certificate not available'], 404);
        }

        $template = null;
        if ($request->filled('template_id')) {
            $user = auth('api')->user();
            if (! $user || $user->id !== $certificate->user_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $template = CertificateTemplate::query()
                ->where('id', $request->input('template_id'))
                ->where('is_active', true)
                ->first();
        }

        $payload = $this->render->buildPayload($certificate, $template);

        return response()->json([
            'message' => 'Success',
            'render' => $this->render->toFrontendPayload($payload),
        ]);
    }

    public function verify(Request $request)
    {
        $serial = trim((string) $request->input('serial', ''));
        $token = $request->input('token');
        if ($serial === '') {
            return response()->json(['message' => 'Serial required', 'valid' => false], 422);
        }

        $result = $this->verification->verifyBySerial($serial, $token);
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

    public function downloadQr($uuid)
    {
        $certificate = Certificate::where('uuid', $uuid)->firstOrFail();
        $serial = $certificate->serial_number ?? $certificate->uuid;
        $binary = $this->qr->pngBinary(
            $this->qr->verificationUrl($serial, $certificate->verification_token)
        );

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-'.$serial.'.png"',
        ]);
    }

    /** Legacy proxy for old local assets; new uploads are served from static.zanburak.ir. */
    public function asset(Request $request)
    {
        $path = (string) $request->query('path', '');
        if ($path === '') {
            abort(404);
        }

        $relativePath = CertificateAssetHelper::relativePath($path) ?? CertificateAssetHelper::validateRelativePath($path);

        if (CertificateAssetHelper::disk()->exists($relativePath)) {
            return redirect(CertificateAssetHelper::publicUrl($relativePath), 302, [
                'Cache-Control' => 'public, max-age=86400',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $full = CertificateAssetHelper::absolutePath($relativePath);
        $mime = mime_content_type($full) ?: 'application/octet-stream';

        return response()->file($full, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Access-Control-Allow-Origin' => '*',
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
            'verify_url' => $this->qr->verificationUrl($serial, $certificate->verification_token),
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
            'online' => $user->certificates()
                ->where('status', 'issued')
                ->whereNotNull('issued_at')
                ->whereNull('revoked_at'),
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
