<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_template_id',
        'uuid',
        'serial_number',
        'verification_token',
        'user_name',
        'course_title',
        'instructor_name',
        'grade',
        'time_completed',
        'issued_at',
        'status',
        'pdf_path',
        'image_path',
        'revoked_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'grade' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued' && $this->issued_at !== null && $this->revoked_at === null;
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked' || $this->revoked_at !== null;
    }

    public function durationFormatted(): string
    {
        $seconds = (int) ($this->time_completed ?? 0);
        if ($seconds <= 0) {
            return '—';
        }
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        }
        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$minutes}m";
    }
}
