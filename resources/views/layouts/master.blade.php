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
    <link rel="icon" type="image/x-icon" href="/assets/images/logo/logo-without-text.png">
    <link href="https://fonts.googleapis.com/css?family=Lato:400,700%7CRoboto:400,500%7CExo+2:600&display=swap"
        rel="stylesheet">
    <!-- Perfect Scrollbar -->
    <link type="text/css" href="/assets/vendor/perfect-scrollbar.css" rel="stylesheet">
    <!-- Fix Footer CSS -->
    <link type="text/css" href="/assets/vendor/fix-footer.css" rel="stylesheet">
    <!-- Material Design Icons -->
    <link type="text/css" href="/assets/css/material-icons.rtl.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link type="text/css" href="/assets/css/fontawesome.rtl.css" rel="stylesheet">
    <!-- Preloader -->
    {{--  <link type="text/css" href="/assets/css/preloader.rtl.css" rel="stylesheet">  --}}
    <!-- App CSS -->
    <link type="text/css" href="/assets/css/app.rtl.css" rel="stylesheet">
    <!-- Font CSS -->
    <link type="text/css" href="/assets/css/font.css" rel="stylesheet">
    <!-- Loading CSS -->
    <link type="text/css" href="/assets/css/loading.css" rel="stylesheet">
    <!-- Scroll CSS -->
    {{--  <link type="text/css" href="/assets/css/scroll.css" rel="stylesheet">  --}}
    <!-- Toastr -->
    <link type="text/css" href="/assets/vendor/toastr.min.css" rel="stylesheet">
    <link type="text/css" href="/assets/css/toastr.rtl.css" rel="stylesheet">

    <!---start GOFTINO code--->
    <script type="text/javascript">
    !function(){var i="qss4Su",a=window,d=document;function g(){var g=d.createElement("script"),s="https://www.goftino.com/widget/"+i,l=localStorage.getItem("goftino_"+i);g.async=!0,g.src=l?s+"?o="+l:s;d.getElementsByTagName("head")[0].appendChild(g);}"complete"===d.readyState?g():a.attachEvent?a.attachEvent("onload",g):a.addEventListener("load",g,!1);}();
    </script>
    <!---end GOFTINO code--->
    
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

<body class="layout-sticky-subnav layout-learnly ">
    <div class="loading"></div>
    <!-- Header Layout -->
    <div class="mdk-header-layout">{{-- deleted js-mdk-header-layout class --}}

        {{--  <div class="mt-3">
            <div class="container">
                <div class="alert alert-warning alert-dismissible fade show rounded-lg border-2 border-warning text-warning py-3" role="alert">
                    <button type="button" class="close btn" data-dismiss="alert" aria-label="Close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="currentColor" d="M9.78363 8.30602C9.37561 7.89799 8.71407 7.89799 8.30604 8.30602C7.89802 8.71404 7.89802 9.37557 8.30604 9.78359L10.5225 12L8.30602 14.2164C7.89799 14.6244 7.89799 15.286 8.30602 15.694C8.71404 16.102 9.37558 16.102 9.78361 15.694L12 13.4776L14.2164 15.6939C14.6244 16.1019 15.286 16.1019 15.694 15.6939C16.102 15.2859 16.102 14.6243 15.694 14.2163L13.4776 12L15.694 9.78369C16.102 9.37567 16.102 8.71413 15.694 8.30611C15.2859 7.89809 14.6244 7.89809 14.2164 8.30611L12 10.5224L9.78363 8.30602Z"></path>
                            <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M0 12C0 21.882 2.118 24 12 24C21.882 24 24 21.882 24 12C24 2.118 21.882 0 12 0C2.118 0 0 2.118 0 12ZM2 12C2 14.4249 2.13254 16.2369 2.43771 17.6101C2.73783 18.9605 3.17768 19.7608 3.70846 20.2915C4.23924 20.8223 5.03949 21.2622 6.38993 21.5623C7.76307 21.8675 9.57515 22 12 22C14.4249 22 16.2369 21.8675 17.6101 21.5623C18.9605 21.2622 19.7608 20.8223 20.2915 20.2915C20.8223 19.7608 21.2622 18.9605 21.5623 17.6101C21.8675 16.2369 22 14.4249 22 12C22 9.57515 21.8675 7.76307 21.5623 6.38993C21.2622 5.03949 20.8223 4.23924 20.2915 3.70846C19.7608 3.17768 18.9605 2.73783 17.6101 2.43771C16.2369 2.13254 14.4249 2 12 2C9.57515 2 7.76307 2.13254 6.38993 2.43771C5.03949 2.73783 4.23924 3.17768 3.70846 3.70846C3.17768 4.23924 2.73783 5.03949 2.43771 6.38993C2.13254 7.76307 2 9.57515 2 12Z" fill-opacity="0.4"></path>
                        </svg>
                    </button>
                    <div class="d-flex flex-column flex-lg-row flex-md-row text-center text-lg-left text-md-left">
                        <div class="mr-8pt">
                            <svg width="30" height="30" class="ml-2" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.216 14.7457C14.5198 14.623 14.7893 14.4275 15 14.177C15.2107 14.4275 15.4802 14.623 15.784 14.7457C15.6104 15.0232 15.5075 15.3395 15.4845 15.6658C15.1666 15.5868 14.8334 15.5868 14.5155 15.6658C14.4925 15.3395 14.3896 15.0232 14.216 14.7457Z" fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.05257 14.1818C8.19103 14.2114 8.35338 14.2114 8.67809 14.2114C9.11559 14.2114 9.47025 14.6459 9.47025 15.182C9.47025 15.718 9.11559 16.1525 8.67809 16.1525C8.35991 16.1525 8.20082 16.1525 8.06305 16.1818C7.54076 16.2931 7.11139 16.742 7.02417 17.268C7.00116 17.4068 7.00782 17.5509 7.02115 17.8392C7.06347 18.7548 7.15793 19.4091 7.36869 19.9744C7.79087 21.1068 8.40002 21.8161 9.32428 22.3333C10.1604 22.8012 11.3671 23 13.5164 23H16.4836C18.6329 23 19.8396 22.8012 20.6757 22.3333C21.6 21.8161 22.2091 21.1068 22.6313 19.9744C22.8421 19.4091 22.9365 18.7548 22.9789 17.8392C22.9922 17.551 22.9988 17.4068 22.9758 17.268C22.8886 16.742 22.4592 16.2931 21.937 16.1818C21.7992 16.1525 21.6401 16.1525 21.3219 16.1525C20.8844 16.1525 20.5297 15.718 20.5297 15.182C20.5297 14.6459 20.8844 14.2114 21.3219 14.2114C21.6466 14.2114 21.809 14.2114 21.9474 14.1818C22.4658 14.0708 22.8858 13.6385 22.9811 13.1178C23.0065 12.9787 23.0019 12.8268 22.9926 12.523C22.9587 11.4131 22.8689 10.6628 22.6313 10.0256C22.2091 8.89319 21.6 8.18391 20.6757 7.66667C19.8396 7.19875 18.6329 7 16.4836 7H13.5164C11.3671 7 10.1604 7.19875 9.32428 7.66667C8.40002 8.18391 7.79087 8.89319 7.36869 10.0256C7.13113 10.6628 7.04134 11.4131 7.0074 12.523C6.99811 12.8268 6.99347 12.9787 7.01892 13.1178C7.11424 13.6385 7.53416 14.0708 8.05257 14.1818ZM14.365 12.2847C14.5648 11.6705 15.4352 11.6705 15.6351 12.2847L15.8995 13.0973C15.9889 13.3719 16.2453 13.5579 16.5345 13.5579H17.3903C18.0371 13.5579 18.3061 14.3843 17.7828 14.7639L17.0905 15.2661C16.8564 15.4359 16.7585 15.7368 16.8479 16.0115L17.1123 16.824C17.3122 17.4383 16.6081 17.949 16.0848 17.5694L15.3925 17.0672C15.1585 16.8974 14.8415 16.8974 14.6075 17.0672L13.9152 17.5694C13.3919 17.949 12.6878 17.4383 12.8877 16.824L13.1521 16.0115C13.2415 15.7368 13.1436 15.4359 12.9095 15.2661L12.2172 14.7639C11.6939 14.3843 11.9629 13.5579 12.6097 13.5579H13.4655C13.7547 13.5579 14.0111 13.3719 14.1005 13.0973L14.365 12.2847Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <div class="my-auto d-flex flex-column flex-lg-row flex-md-row font-size-16pt">
                            <div class="mr-2">
                                وقت بخیر {{ auth()->user()->first_name.' '.auth()->user()->last_name }} عزیز، ماموریت هایی داری که هنوز انجام ندادی. لطفا بررسیشون کن و انجامشون بده!
                            </div>
                            <a href="#" class="font-bold text-decoration-underline">
                                مشاهده ماموریت ها
                                <svg class="ml-1" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" opacity="0.4" d="M15.7975 10.8097L19.4967 10.4825C20.3269 10.4825 21 11.1622 21 12.0004C21 12.8387 20.3269 13.5183 19.4967 13.5183L15.7975 13.1912C15.1463 13.1912 14.6183 12.6581 14.6183 12.0004C14.6183 11.3417 15.1463 10.8097 15.7975 10.8097Z"></path>
                                    <path fill="currentColor" d="M3.37522 10.8698C3.43303 10.8115 3.64903 10.5647 3.85194 10.3598C5.03556 9.07656 8.12607 6.97815 9.74278 6.33596C9.98823 6.23352 10.6089 6.01542 10.9417 6C11.2591 6 11.5624 6.0738 11.8515 6.2192C12.2126 6.42299 12.5006 6.74463 12.6598 7.12355C12.7613 7.38572 12.9206 8.17331 12.9206 8.18763C13.0787 9.04792 13.1649 10.4469 13.1649 11.9934C13.1649 13.465 13.0787 14.8067 12.9489 15.6813C12.9347 15.6967 12.7755 16.6738 12.602 17.0086C12.2846 17.6211 11.6638 18 10.9995 18H10.9417C10.5086 17.9857 9.59878 17.6057 9.59878 17.5924C8.06825 16.9502 5.05083 14.9532 3.83776 13.6258C3.83776 13.6258 3.49522 13.2844 3.34685 13.0718C3.11558 12.7656 2.99995 12.3866 2.99995 12.0077C2.99995 11.5847 3.12977 11.1915 3.37522 10.8698Z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>  --}}

        <!-- Header -->
        @section('header')
            @include('layouts.header')
        @show
        <!-- // END Header -->
        <!-- Header Layout Content -->
        @yield('content')
        <!-- // END Header Layout Content -->
        @section('footer')
            @include('layouts.footer')
        @show

    </div>
    <!-- // END Header Layout -->
    <!-- drawer -->
    @section('sidebar')
        @include('layouts.sidebar')
    @show
    <!-- // END drawer -->
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
