<?php

namespace App\Models;

use App\Support\Certificate\CertificateAssetHelper;
use Illuminate\Database\Eloquent\Model;

class CertificateFont extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'file_path',
        'format',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function cssFamily(): string
    {
        return 'CertFont-'.$this->slug;
    }

    public function publicUrl(): ?string
    {
        return CertificateAssetHelper::publicUrl($this->file_path);
    }
}
