<?php

namespace App\Support\Certificate;

final class CertificateConstants
{
    public const STATUSES = ['pending', 'issued', 'revoked'];

    public const PLACEHOLDERS = [
        'student_name',
        'course_name',
        'completion_date',
        'certificate_serial',
        'certificate_id',
        'qr_code',
        'instructor_name',
        'duration',
        'grade',
    ];

    public const DEFAULT_LAYOUT = [
        'student_name' => ['x' => 50, 'y' => 42, 'font_size' => 36, 'align' => 'center', 'color' => '#1a1a2e'],
        'course_name' => ['x' => 50, 'y' => 55, 'font_size' => 22, 'align' => 'center', 'color' => '#374151'],
        'completion_date' => ['x' => 25, 'y' => 78, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280'],
        'certificate_serial' => ['x' => 8, 'y' => 92, 'font_size' => 11, 'align' => 'left', 'color' => '#9ca3af'],
        'certificate_id' => ['x' => 92, 'y' => 92, 'font_size' => 10, 'align' => 'right', 'color' => '#9ca3af'],
        'instructor_name' => ['x' => 75, 'y' => 78, 'font_size' => 16, 'align' => 'center', 'color' => '#374151'],
        'duration' => ['x' => 50, 'y' => 65, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280'],
        'grade' => ['x' => 50, 'y' => 70, 'font_size' => 14, 'align' => 'center', 'color' => '#059669'],
        'qr_code' => ['x' => 88, 'y' => 12, 'size' => 120],
        'logo' => ['x' => 12, 'y' => 8, 'width' => 100, 'height' => 60],
        'signature' => ['x' => 75, 'y' => 72, 'width' => 140, 'height' => 50],
    ];

    public const DEFAULT_SETTINGS = [
        'primary_color' => '#facc15',
        'text_color' => '#1f2937',
        'font_family' => 'DejaVu Sans',
    ];
}
