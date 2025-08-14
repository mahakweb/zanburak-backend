<!DOCTYPE html>
<html lang="en" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>{{ __('zanburak') }} | @yield('title')</title>
        <link rel="icon" type="image/x-icon" href="/assets/images/logo/logo-without-text.png">
        <!-- Prevent the demo from appearing in search engines -->
        <meta name="robots" content="noindex">
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700%7CRoboto:400,500%7CExo+2:600&display=swap" rel="stylesheet">
        <!-- Perfect Scrollbar -->
        <link type="text/css" href="/assets/vendor/perfect-scrollbar.css" rel="stylesheet">
        <!-- Fix Footer CSS -->
        <link type="text/css" href="/assets/vendor/fix-footer.css" rel="stylesheet">
        <!-- Material Design Icons -->
        <link type="text/css" href="/assets/css/material-icons.rtl.css" rel="stylesheet">
        <!-- Font Awesome Icons -->
        <link type="text/css" href="/assets/css/fontawesome.rtl.css" rel="stylesheet">
        <!-- Preloader -->
        <link type="text/css" href="/assets/css/preloader.rtl.css" rel="stylesheet">
        <!-- App CSS -->
        <link type="text/css" href="/assets/css/app.rtl.css" rel="stylesheet">
        <!-- Font CSS -->
        <link type="text/css" href="/assets/css/font.css" rel="stylesheet">
        @yield('head')
    </head>
    <body class="layout-default layout-login-centered-boxed">
        <div class="layout-login-centered-boxed__form card mx-3 border-0">
            @yield('content')
        </div>
        <!-- jQuery -->
        <script src="/assets/vendor/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="/assets/vendor/popper.min.js"></script>
        <script src="/assets/vendor/bootstrap.min.js"></script>
        <!-- Perfect Scrollbar -->
        <script src="/assets/vendor/perfect-scrollbar.min.js"></script>
        <!-- DOM Factory -->
        <script src="/assets/vendor/dom-factory.js"></script>
        <!-- MDK -->
        <script src="/assets/vendor/material-design-kit.js"></script>
        <!-- Fix Footer -->
        <script src="/assets/vendor/fix-footer.js"></script>
        <!-- App JS -->
        <script src="/assets/js/app.js"></script>
        @yield('script')
    </body>
</html>
