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

    /** Font files under storage/app/public/certificates/fonts/ (tracked in git). */
    public const AVAILABLE_FONTS = [
        // Persian
        ['slug' => 'yekanbakh', 'name' => 'یکان‌بخ', 'file_path' => 'certificates/fonts/yekanbakh.woff', 'format' => 'woff', 'category' => 'fa'],
        ['slug' => 'bzar', 'name' => 'B Zar', 'file_path' => 'certificates/fonts/bzar.ttf', 'format' => 'ttf', 'category' => 'fa'],
        ['slug' => 'irannastaliq', 'name' => 'Iran Nastaliq', 'file_path' => 'certificates/fonts/irannastaliq.ttf', 'format' => 'ttf', 'category' => 'fa'],
        ['slug' => 'bnazanin', 'name' => 'B Nazanin', 'file_path' => 'certificates/fonts/bnazanin.ttf', 'format' => 'ttf', 'category' => 'fa'],
        ['slug' => 'anjoman', 'name' => 'Anjoman', 'file_path' => 'certificates/fonts/anjoman.woff', 'format' => 'woff', 'category' => 'fa'],
        ['slug' => 'btitr', 'name' => 'B Titr', 'file_path' => 'certificates/fonts/btitr.ttf', 'format' => 'ttf', 'category' => 'fa'],
        ['slug' => 'vazirmatn', 'name' => 'وزیرمتن', 'file_path' => 'certificates/fonts/vazirmatn.woff2', 'format' => 'woff2', 'category' => 'fa'],
        ['slug' => 'samim', 'name' => 'سمیم', 'file_path' => 'certificates/fonts/samim.woff2', 'format' => 'woff2', 'category' => 'fa'],
        // English
        ['slug' => 'roboto', 'name' => 'Roboto', 'file_path' => 'certificates/fonts/roboto.woff2', 'format' => 'woff2', 'category' => 'en'],
        ['slug' => 'open_sans', 'name' => 'Open Sans', 'file_path' => 'certificates/fonts/open_sans.woff2', 'format' => 'woff2', 'category' => 'en'],
        ['slug' => 'montserrat', 'name' => 'Montserrat', 'file_path' => 'certificates/fonts/montserrat.woff2', 'format' => 'woff2', 'category' => 'en'],
        ['slug' => 'playfair', 'name' => 'Playfair Display', 'file_path' => 'certificates/fonts/playfair.woff2', 'format' => 'woff2', 'category' => 'en'],
        ['slug' => 'merriweather', 'name' => 'Merriweather', 'file_path' => 'certificates/fonts/merriweather.woff2', 'format' => 'woff2', 'category' => 'en'],
    ];

    public const DEFAULT_LAYOUT = [
        'title' => ['x' => 50, 'y' => 18, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280', 'font_family' => 'yekanbakh', 'visible' => true],
        'subtitle' => ['x' => 50, 'y' => 48, 'font_size' => 13, 'align' => 'center', 'color' => '#64748b', 'font_family' => 'yekanbakh', 'visible' => true],
        'student_name' => ['x' => 50, 'y' => 42, 'font_size' => 36, 'align' => 'center', 'color' => '#1a1a2e', 'font_family' => 'irannastaliq', 'visible' => true],
        'course_name' => ['x' => 50, 'y' => 55, 'font_size' => 22, 'align' => 'center', 'color' => '#374151', 'font_family' => 'bzar', 'visible' => true],
        'completion_date' => ['x' => 25, 'y' => 78, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280', 'font_family' => 'bnazanin', 'visible' => true],
        'certificate_serial' => ['x' => 8, 'y' => 92, 'font_size' => 11, 'align' => 'left', 'color' => '#9ca3af', 'font_family' => 'yekanbakh', 'visible' => true],
        'certificate_id' => ['x' => 92, 'y' => 92, 'font_size' => 10, 'align' => 'right', 'color' => '#9ca3af', 'font_family' => 'yekanbakh', 'visible' => true],
        'instructor_name' => ['x' => 75, 'y' => 78, 'font_size' => 16, 'align' => 'center', 'color' => '#374151', 'font_family' => 'bzar', 'visible' => true],
        'duration' => ['x' => 50, 'y' => 65, 'font_size' => 14, 'align' => 'center', 'color' => '#6b7280', 'font_family' => 'bnazanin', 'visible' => true],
        'grade' => ['x' => 50, 'y' => 70, 'font_size' => 14, 'align' => 'center', 'color' => '#059669', 'font_family' => 'bnazanin', 'visible' => true],
        'custom_text_1' => ['x' => 15, 'y' => 30, 'font_size' => 12, 'align' => 'center', 'color' => '#374151', 'font_family' => 'yekanbakh', 'visible' => false],
        'custom_text_2' => ['x' => 85, 'y' => 30, 'font_size' => 12, 'align' => 'center', 'color' => '#374151', 'font_family' => 'yekanbakh', 'visible' => false],
        'custom_text_3' => ['x' => 15, 'y' => 85, 'font_size' => 12, 'align' => 'center', 'color' => '#374151', 'font_family' => 'yekanbakh', 'visible' => false],
        'custom_text_4' => ['x' => 85, 'y' => 85, 'font_size' => 12, 'align' => 'center', 'color' => '#374151', 'font_family' => 'yekanbakh', 'visible' => false],
        'custom_text_5' => ['x' => 50, 'y' => 90, 'font_size' => 12, 'align' => 'center', 'color' => '#374151', 'font_family' => 'yekanbakh', 'visible' => false],
        'qr_code' => ['x' => 88, 'y' => 12, 'width' => 120, 'height' => 120, 'visible' => true],
        'logo' => ['x' => 12, 'y' => 8, 'width' => 100, 'height' => 60, 'visible' => true],
        'signature' => ['x' => 75, 'y' => 72, 'width' => 140, 'height' => 50, 'visible' => true],
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
        'custom_text_1' => '',
        'custom_text_2' => '',
        'custom_text_3' => '',
        'custom_text_4' => '',
        'custom_text_5' => '',
        'frame_enabled' => true,
        'frame_color' => '',
        'frame_width' => 3,
        'frame_inset' => 24,
        'frame_style' => 'solid',
    ];

    public static function defaultCanvasForOrientation(string $orientation): array
    {
        return $orientation === 'portrait' ? self::CANVAS_PORTRAIT : self::CANVAS_LANDSCAPE;
    }
}
