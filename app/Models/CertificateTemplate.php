<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Support\Certificate\CertificateConstants;

class CertificateTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'slug', 'description',
        'background_image', 'logo_image', 'signature_image',
        'orientation', 'layout', 'settings',
        'is_default', 'is_active', 'created_by',
    ];

    protected $casts = [
        'layout' => 'array',
        'settings' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $template) {
            if (empty($template->uuid)) {
                $template->uuid = (string) Str::uuid();
            }
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
            if (empty($template->layout)) {
                $template->layout = CertificateConstants::DEFAULT_LAYOUT;
            }
            if (empty($template->settings)) {
                $template->settings = CertificateConstants::DEFAULT_SETTINGS;
            }
        });

        static::saved(function (self $template) {
            if ($template->is_default) {
                static::where('id', '!=', $template->id)->update(['is_default' => false]);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'certificate_template_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'certificate_template_id');
    }

    public function resolvedLayout(): array
    {
        return array_merge(
            CertificateConstants::DEFAULT_LAYOUT,
            $this->layout ?? []
        );
    }

    public function resolvedSettings(): array
    {
        return array_merge(
            CertificateConstants::DEFAULT_SETTINGS,
            $this->settings ?? []
        );
    }
}
