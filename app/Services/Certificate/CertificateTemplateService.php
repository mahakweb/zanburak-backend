<?php

namespace App\Services\Certificate;

use App\Models\CertificateTemplate;
use Illuminate\Support\Str;

class CertificateTemplateService
{
    public function create(array $data, ?int $userId = null): CertificateTemplate
    {
        if (! empty($data['is_default'])) {
            CertificateTemplate::query()->update(['is_default' => false]);
        }

        return CertificateTemplate::create([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'created_by' => $userId,
        ]);
    }

    public function update(CertificateTemplate $template, array $data): CertificateTemplate
    {
        if (! empty($data['is_default'])) {
            CertificateTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        $template->update($data);

        return $template->fresh();
    }

    public function duplicate(CertificateTemplate $template, ?int $userId = null): CertificateTemplate
    {
        $copy = $template->replicate(['uuid', 'slug', 'is_default']);
        $copy->uuid = (string) Str::uuid();
        $copy->name = $template->name.' (کپی)';
        $copy->slug = Str::slug($copy->name).'-'.Str::random(4);
        $copy->is_default = false;
        $copy->created_by = $userId;
        $copy->save();

        return $copy;
    }
}
