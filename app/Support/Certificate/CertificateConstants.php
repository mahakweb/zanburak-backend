<?php

namespace App\Support\Certificate;

final class CertificateConstants
{
    public const STATUSES = ['pending', 'issued', 'revoked'];

    public const CANVAS_LANDSCAPE = ['width' => 1123, 'height' => 794];
    public const CANVAS_PORTRAIT = ['width' => 794, 'height' => 1123];

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

    /** Font files expected under storage/app/public/certificates/fonts/ */
    public const AVAILABLE_FONTS = [
        ['slug' => 'yekanbakh', 'name' => 'یکان‌بخ', 'file_path' => 'certificates/fonts/yekanbakh.woff', 'format' => 'woff'],
        ['slug' => 'bzar', 'name' => 'B Zar', 'file_path' => 'certificates/fonts/bzar.ttf', 'format' => 'ttf'],
        ['slug' => 'irannastaliq', 'name' => 'Iran Nastaliq', 'file_path' => 'certificates/fonts/irannastaliq.ttf', 'format' => 'ttf'],
        ['slug' => 'bnazanin', 'name' => 'B Nazanin', 'file_path' => 'certificates/fonts/bnazanin.ttf', 'format' => 'ttf'],
        ['slug' => 'anjoman', 'name' => 'Anjoman', 'file_path' => 'certificates/fonts/anjoman.woff', 'format' => 'woff'],
        ['slug' => 'btitr', 'name' => 'B Titr', 'file_path' => 'certificates/fonts/btitr.ttf', 'format' => 'ttf'],
    ];

    public const DEFAULT_LAYOUT = [
        'title' => ['x' => 50, 'y' => 18, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280', 'font_family' => 'yekanbakh'],
        'subtitle' => ['x' => 50, 'y' => 48, 'font_size' => 13, 'align' => 'center', 'color' => '#64748b', 'font_family' => 'yekanbakh'],
        'student_name' => ['x' => 50, 'y' => 42, 'font_size' => 36, 'align' => 'center', 'color' => '#1a1a2e', 'font_family' => 'irannastaliq'],
        'course_name' => ['x' => 50, 'y' => 55, 'font_size' => 22, 'align' => 'center', 'color' => '#374151', 'font_family' => 'bzar'],
        'completion_date' => ['x' => 25, 'y' => 78, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280', 'font_family' => 'bnazanin'],
        'certificate_serial' => ['x' => 8, 'y' => 92, 'font_size' => 11, 'align' => 'left', 'color' => '#9ca3af', 'font_family' => 'yekanbakh'],
        'certificate_id' => ['x' => 92, 'y' => 92, 'font_size' => 10, 'align' => 'right', 'color' => '#9ca3af', 'font_family' => 'yekanbakh'],
        'instructor_name' => ['x' => 75, 'y' => 78, 'font_size' => 16, 'align' => 'center', 'color' => '#374151', 'font_family' => 'bzar'],
        'duration' => ['x' => 50, 'y' => 65, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280', 'font_family' => 'bnazanin'],
        'grade' => ['x' => 50, 'y' => 70, 'font_size' => 14, 'align' => 'center', 'color' => '#059669', 'font_family' => 'bnazanin'],
        'qr_code' => ['x' => 88, 'y' => 12, 'width' => 120, 'height' => 120],
        'logo' => ['x' => 12, 'y' => 8, 'width' => 100, 'height' => 60],
        'signature' => ['x' => 75, 'y' => 72, 'width' => 140, 'height' => 50],
    ];

    public const DEFAULT_SETTINGS = [
        'primary_color' => '#facc15',
        'text_color' => '#1f2937',
        'font_family' => 'yekanbakh',
        'title' => 'گواهینامه تکمیل دوره',
        'subtitle' => 'با موفقیت دوره آموزشی زیر را به پایان رسانده است:',
        'course_label' => 'دوره آموزشی',
        'date_label' => 'تاریخ اتمام',
        'duration_label' => 'مدت زمان',
        'grade_label' => 'نمره',
        'serial_label' => 'سریال',
        'instructor_label' => 'مدرس دوره',
    ];

    public static function defaultCanvasForOrientation(string $orientation): array
    {
        return $orientation === 'portrait' ? self::CANVAS_PORTRAIT : self::CANVAS_LANDSCAPE;
    }
}
