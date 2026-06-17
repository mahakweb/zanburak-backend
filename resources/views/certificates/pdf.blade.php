<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            width: 100%;
            height: 100%;
        }
        .cert {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 210mm;
            overflow: hidden;
        }
        .cert-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        .cert-frame {
            position: absolute;
            inset: 12mm;
            border: 3px solid {{ $settings['primary_color'] ?? '#facc15' }};
            z-index: 1;
        }
        .cert-inner {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            padding: 18mm 20mm;
            text-align: center;
        }
        .logo {
            position: absolute;
            top: 8mm;
            left: 12mm;
            max-width: 90px;
            max-height: 55px;
        }
        .qr {
            position: absolute;
            top: 10mm;
            right: 12mm;
            width: 28mm;
            height: 28mm;
        }
        .title {
            font-size: 14pt;
            color: #6b7280;
            letter-spacing: 2px;
            margin-top: 25mm;
            margin-bottom: 6mm;
        }
        .student {
            font-size: 28pt;
            font-weight: bold;
            color: {{ $settings['text_color'] ?? '#1f2937' }};
            margin-bottom: 8mm;
        }
        .subtitle {
            font-size: 13pt;
            color: #4b5563;
            margin-bottom: 4mm;
        }
        .course {
            font-size: 18pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 6mm;
        }
        .meta {
            font-size: 11pt;
            color: #6b7280;
            margin-bottom: 3mm;
        }
        .footer {
            position: absolute;
            bottom: 14mm;
            left: 20mm;
            right: 20mm;
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            width: 33%;
            vertical-align: bottom;
            font-size: 10pt;
            color: #6b7280;
        }
        .footer-col.center { text-align: center; }
        .footer-col.left { text-align: left; direction: ltr; }
        .footer-col.right { text-align: right; }
        .signature {
            max-width: 120px;
            max-height: 45px;
            margin-bottom: 2mm;
        }
        .instructor {
            font-size: 11pt;
            font-weight: bold;
            color: #374151;
        }
        .serial {
            font-size: 9pt;
            color: #9ca3af;
            direction: ltr;
        }
    </style>
</head>
<body>
<div class="cert">
    @if($background_url)
        <img class="cert-bg" src="{{ $background_url }}" alt="">
    @else
        <div class="cert-bg" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 50%, #fff 100%);"></div>
    @endif
    <div class="cert-frame"></div>
    <div class="cert-inner">
        @if($logo_url)
            <img class="logo" src="{{ $logo_url }}" alt="logo">
        @endif
        @if($qr_data_uri)
            <img class="qr" src="{{ $qr_data_uri }}" alt="QR">
        @endif

        <div class="title">گواهینامه تکمیل دوره</div>
        <div class="student">{{ $placeholders['student_name'] }}</div>
        <div class="subtitle">با موفقیت دوره آموزشی زیر را به پایان رسانده است:</div>
        <div class="course">{{ $placeholders['course_name'] }}</div>

        @if($placeholders['duration'])
            <div class="meta">مدت زمان: {{ $placeholders['duration'] }}</div>
        @endif
        @if($placeholders['grade'])
            <div class="meta">نمره: {{ $placeholders['grade'] }}</div>
        @endif

        <div class="footer">
            <div class="footer-col left">
                <div class="serial">{{ $placeholders['certificate_serial'] }}</div>
            </div>
            <div class="footer-col center">
                <div>تاریخ صدور</div>
                <div class="instructor">{{ $placeholders['completion_date'] }}</div>
            </div>
            <div class="footer-col right">
                @if($signature_url)
                    <img class="signature" src="{{ $signature_url }}" alt="signature">
                @endif
                @if($placeholders['instructor_name'])
                    <div class="instructor">{{ $placeholders['instructor_name'] }}</div>
                    <div>مدرس دوره</div>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
