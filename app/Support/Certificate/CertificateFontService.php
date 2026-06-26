<?php

namespace App\Support\Certificate;

use App\Models\CertificateFont;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CertificateFontService
{
    /** @return array<string, array{slug: string, name: string, css_family: string, url: string|null}> */
    public function catalogForLayout(array $layout): array
    {
        $slugs = $this->collectSlugsFromLayout($layout);
        if ($slugs->isEmpty()) {
            return [];
        }

        $fonts = CertificateFont::query()
            ->whereIn('slug', $slugs)
            ->where('is_active', true)
            ->get()
            ->keyBy('slug');

        $catalog = [];
        foreach ($slugs as $slug) {
            $font = $fonts->get($slug);
            if (! $font) {
                continue;
            }
            $catalog[$slug] = [
                'slug' => $font->slug,
                'name' => $font->name,
                'css_family' => $font->cssFamily(),
                'url' => $font->publicUrl(),
            ];
        }

        return $catalog;
    }

    /** @return Collection<int, string> */
    public function collectSlugsFromLayout(array $layout): Collection
    {
        return collect($layout)
            ->filter(fn ($item) => is_array($item) && ! empty($item['font_family']))
            ->pluck('font_family')
            ->unique()
            ->values();
    }

    public function ensureDefaults(): void
    {
        $dir = storage_path('app/public/certificates/fonts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach (CertificateConstants::AVAILABLE_FONTS as $font) {
            $record = CertificateFont::query()->firstOrNew(['slug' => $font['slug']]);
            $record->name = $font['name'];

            $bundledExists = Storage::disk('public')->exists($font['file_path']);
            $hasUploadedFile = $record->file_path
                && Storage::disk('public')->exists($record->file_path);

            if (! $record->exists) {
                $record->file_path = $font['file_path'];
                $record->format = $font['format'];
                $record->is_active = $bundledExists;
            } elseif (! $hasUploadedFile && $bundledExists) {
                $record->file_path = $font['file_path'];
                $record->format = $font['format'];
                $record->is_active = true;
            } elseif ($hasUploadedFile) {
                $record->is_active = true;
            } else {
                $record->is_active = false;
            }

            $record->save();
        }
    }
}
