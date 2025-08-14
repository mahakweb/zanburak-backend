<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#fed700">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title')</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Prevent the demo from appearing in search engines -->
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/x-icon" href="/assets/images/logo/logo-without-text.png">

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
    <!-- Toastr -->
    <link type="text/css" href="/assets/vendor/toastr.min.css" rel="stylesheet">
    <link type="text/css" href="/assets/css/toastr.rtl.css" rel="stylesheet">
    <!-- Sweet Alert -->
    <link rel="stylesheet" href="/assets/css/sweetalert.rtl.css">

    <!-- sweetalert2.github.io  start-->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            // confirmButtonText: 'باشه',
            timer: 5000,
            width: 500,
            // showCloseButton: true,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
                // toast.addEventListener('click', Swal.clickCancel)
            }
        })
    </script>
    <!-- sweetalert2.github.io  end-->

    @yield('head')

</head>

<body class="layout-sticky layout-sticky-subnav ">

<div class="preloader">
    <div class="brand-avatar">
        <div class="bg-gradient-yellow rounded-lg p-1">
            <img src="/assets/images/logo/logo-dark.png" class="" alt="logo" />
        </div>
        {{--  <div class="d-flex flex-row mt-4">
            <p class="font-size-16pt font-bold mx-auto">لطفا چند لحظه صبر کنید...</p>
        </div>  --}}
    </div>
    <div class="sk-double-bounce">
        <div class="sk-child sk-double-bounce1"></div>
        <div class="sk-child sk-double-bounce2"></div>
    </div>
</div>

<!-- Header Layout -->
    <div class="mdk-header-layout js-mdk-header-layout">

        <!-- Header -->

        <div id="header" class="mdk-header js-mdk-header mb-0" data-fixed>
            <div class="mdk-header__content">

                @include('student.layouts.header')

            </div>
        </div>

        <!-- // END Header -->



        <div class="mdk-header-layout__content">

            <!-- Drawer Layout -->
            <div class="mdk-drawer-layout js-mdk-drawer-layout" data-push data-responsive-width="992px">

                <!-- Drawer Layout Content -->
                <div class="mdk-drawer-layout__content page-content">
                    <div class="py-32pt px-lg-24pt" style="min-height: 100vh;">
                        <div class="page__container">
                            @yield('student-content')
                        </div>
                    </div>
                </div>
                @include('student.layouts.sidebar')
            </div>
            <!-- // END drawer-layout -->
        </div>



    </div>
    <!-- // END drawer-layout__content -->

    <!-- drawer -->

    <!-- // END drawer -->

</div>
<!-- // END drawer-layout -->

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

<!-- Toastr -->
<script src="/assets/vendor/toastr.min.js"></script>
<script src="/assets/js/toastr.js"></script>
<!-- Sweet Alert -->
<script src="/assets/js/settings.js"></script>
<script src="/assets/vendor/sweetalert.min.js"></script>
<script src="/assets/js/sweetalert.js"></script>

<!-- https://realrashid.github.io/sweet-alert/config -->
@include('sweetalert::alert')

<!-- Cart -->
<script type="text/javascript" src="/assets/js/manage-cart.js"></script>

@yield('script')

</body>

@yield('modal')

</html>
