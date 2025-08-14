@extends('layouts.master')

@section('title', 'ارتباط با ما')

@section('content')
    <div class="mdk-header-layout__content page-content ">
        <div class="page-section mb-24pt">
            <div class="container page__container">
                <div class="row">
                    <div class="col-md-6 text-center text-lg-left text-md-left mt-3 mt-lg-0 mt-md-0 order-1 order-lg-0 order-md-0">

                        <h1>راه‌های ارتباطی با زنبورک</h1>
                        <p class="font-size-20pt text-50 mt-lg-48pt">
                            تو این صفحه میتونی آدرس و راه‌های ارتباط با زنبورک رو مشاهده کنی یا حتی برای ما نظرات و یا انتقادات خودت رو بفرستی.
                        </p>


                    </div>
                    <div class="col-md-6 order-0 order-lg-1 order-md-1">
                        <object class="w-100" height="300" data="/assets/images/other/contact-page/contact-us.svg" type="image/svg+xml">
                        </object>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section mb-24pt">
            <div class="container page__container">
                <div class="row">
                    <div class="mx-auto">
                        <div class="d-flex flex-column flex-lg-row">
                            <div class="border-right-lg pr-lg-4">
                                <h4 class="text-center">راه‌های ارتباطی</h4>
                                <div class="d-flex flex-row justify-content-center">
                                    <a href="mailto:info@zanburak.ir" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-instagram"></i></a>
                                    <a href="https://t.me/zanburak_support" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-telegram"></i></a>
                                </div>
                            </div>
                            <div class="ml-lg-4 mt-4 mt-lg-0">
                                <h4 class="text-center">شبکه‌های اجتماعی</h4>
                                <div class="d-flex flex-row justify-content-center">
                                    <a href="https://linkedin.com/in/zanburak" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="https://github.com/zanburak" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-github"></i></a>
                                    <a href="https://twitter.com/zanburak" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-twitter"></i></a>
                                    <a href="https://instagram.com/zanburak" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-instagram"></i></a>
                                    <a href="https://t.me/zanburak" class="font-size-16pt mx-1 btn btn-yellow rounded"><i class="fab fa-telegram"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section mb-24pt">
            <div class="container page__container">
                <div class="row">
                    <div class="col-12 card card-body border-0 shadow-none">
                        <div class="row">
                            <div class="col-md-8 order-1 order-lg-0 order-md-0 mb-3 mb-lg-0">
                                <h3>
                                    <i class="fa fa-phone-alt font-size-12pt pr-1 text-yellow"></i>
                                    شماره تماس:
                                </h3>
                                <span class="text-50"><a href="tel:+986633416935" class="font-size-20pt px-3">06633416935</a></span>

                                <h3 class="mt-4">
                                    <i class="fa fa-map-marker-alt font-size-12pt pr-1 text-yellow"></i>
                                    آدرس دفتر:
                                </h3>
                                <p class="font-size-20pt px-3 text-50">لرستان، خرم‌آباد، خیابان طیب، کوچه پاکمهر30، پلاک: 0، طبقه: همکف</p>
                            </div>
                            <div class="col-md-4 order-0 order-lg-1 order-md-1">
                                <object class="w-100" height="200" data="/assets/images/other/contact-page/delivery-address.svg" type="image/svg+xml">
                                </object>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section mb-24pt">
            <div class="container page__container">
                <div class="row">
                    <div class="col-md-8 order-1 order-lg-0 order-md-0">
                        <div class="card card-body border-0 shadow-none">
                            <p class="font-size-16pt">
                                <i class="fa fa-map-marker-alt text-yellow font-size-24pt mr-2"></i>
                                برای مکان یابی و مشاهده کامل روی نقشه <a href="https://www.google.com/maps/dir//33.451269,48.361149/@33.451269,48.361149,17z">کلیلک</a> کنید.
                            </p>
                        </div>
                        <a href="https://goo.gl/maps/KjhJ5YCcKiGpyax8A" class="">
                            <img src="/assets/images/other/contact-page/mahak-web-location.png" class="rounded-lg border-2 border-yellow w-100 h-auto">
                        </a>
                    </div>
                    <div class="col-md-4 order-0 order-lg-1 order-md-1">
                        <div class="card card-body border-0 shadow-none">
                            <h4>
                                <i class="fa fa-headset font-size-24pt mr-2"></i>
                                فرم تماس با ما
                            </h4>
                            <span class="font-size-16pt text-50">
                                علاوه بر راه‌های ارتباطی که در بالا مشاهده میکنید میتوانید از طریق فرم زیر نظر (انتقاد، پیشنهاد ...) خود را برای ما ارسال کنید.
                            </span>

                            <form class="contact-us-form mt-4" action="{{ route('contact-us-message') }}" method="post">
                                @csrf
                                <div class="form-group">
                                    <label class="text-50" for="name">نام و نام خانوادگی</label>
                                    <input type="text" id="name" name="name" class="form-control rounded-lg">
                                    <span class="invalid-feedback error-text name_error" role="alert">
                                        <strong></strong>
                                    </span>
                                </div>
                                <div class="form-group mt-2">
                                    <label class="text-50" for="email">ایمیل</label>
                                    <input type="email" id="email" name="email" class="form-control rounded-lg">
                                    <span class="invalid-feedback error-text email_error" role="alert">
                                        <strong></strong>
                                    </span>
                                </div>
                                <div class="form-group mt-2">
                                    <label class="text-50" for="message">متن پیام</label>
                                    <textarea rows="6" id="message" name="message" class="form-control rounded-lg"></textarea>
                                    <span class="invalid-feedback error-text message_error" role="alert">
                                        <strong></strong>
                                    </span>
                                </div>
                                <div class="form-group mt-2">
                                    <button type="submit" class="btn btn-yellow float-right">ارسال پیام</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="/assets/js/contact-us-form.js"></script>
@endsection
