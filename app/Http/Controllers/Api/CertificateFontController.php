<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CertificateFont;
use App\Support\Certificate\CertificateAssetHelper;
use App\Support\Certificate\CertificateConstants;
use App\Support\Certificate\CertificateFontService;
use Illuminate\Http\Request;

class CertificateFontController extends Controller
{
    public function __construct(protected CertificateFontService $fonts) {}

    public function index()
    {
        $this->fonts->ensureDefaults();

        $categories = collect(CertificateConstants::AVAILABLE_FONTS)->keyBy('slug');

        $items = CertificateFont::query()
            ->orderBy('name')
            ->get()
            ->map(fn (CertificateFont $font) => [
                'slug' => $font->slug,
                'name' => $font->name,
                'css_family' => $font->cssFamily(),
                'url' => $font->publicUrl(),
                'available' => $font->file_path && CertificateAssetHelper::exists($font->file_path),
                'category' => $categories->get($font->slug)['category'] ?? 'fa',
            ]);

        return response()->json(['message' => 'Success', 'fonts' => $items]);
    }

    public function upload(Request $request, CertificateFont $font)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:woff,woff2,ttf,otf', 'max:8192'],
        ], [
            'file.extensions' => 'فرمت فونت باید woff، woff2، ttf یا otf باشد.',
            'file.max' => 'حداکثر حجم فایل فونت ۸ مگابایت است.',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $path = 'certificates/fonts/'.$font->slug.'.'.$ext;

        if ($font->file_path && $font->file_path !== $path) {
            CertificateAssetHelper::delete($font->file_path);
        }

        CertificateAssetHelper::put($path, file_get_contents($request->file('file')->getRealPath()));

        $font->update([
            'file_path' => $path,
            'format' => $ext,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Font uploaded',
            'font' => [
                'slug' => $font->slug,
                'name' => $font->name,
                'css_family' => $font->cssFamily(),
                'url' => $font->publicUrl(),
                'available' => true,
                'category' => collect(CertificateConstants::AVAILABLE_FONTS)->firstWhere('slug', $font->slug)['category'] ?? 'fa',
            ],
        ]);
    }

    public function catalog()
    {
        return response()->json([
            'message' => 'Success',
            'defaults' => CertificateConstants::AVAILABLE_FONTS,
        ]);
    }
}
