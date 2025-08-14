<!DOCTYPE html>
<html dir="rtl">

<head>
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0mm;
            width: 100% !important;
            height: 100% !important;
        }

        @font-face {
            font-family: IranNastaliq;
            src: url("https://static.zanburak.ir/fonts/IranNastaliq.ttf");
        }
        @font-face {
            font-family: BZar;
            src: url("https://static.zanburak.irfonts/BZar.ttf");
        }

        body {
            direction: rtl;
            margin: 0;
            padding: 0;
            background-image: url("https://static.zanburak.ircertificate/template.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .certificate {
            font-family: IranNastaliq;
            color: white;
            right: 3cm;
            top: 2cm;
            font-size: 36px;
        }
    </style>
</head>

<body>
    <p class="certificate" style="direction: rtl;">
       گواهینامه
    </p>

</body>

</html>
