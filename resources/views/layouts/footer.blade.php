{{--<div class="js-fix-footer2 bg-dark border-top-2">--}}
{{--    <div class="container page__container page-section d-flex flex-column">--}}
{{--        <p class="text-white-70 brand mb-24pt">--}}
{{--            <img class="brand-icon" src="/assets/images/logo/white-100@2x.png" width="30"--}}
{{--                alt="Luma"> Mahak Web--}}
{{--        </p>--}}
{{--        <p class="measure-lead-max text-white-50 small mr-8pt">زنبورک یکی از پرتلاش‌ترین و بروزترین وبسایت های آموزشی در سطح ایران است که همیشه تلاش کرده تا بتواند جدیدترین و بروزترین مقالات و دوره‌های آموزشی را در اختیار علاقه‌مندان ایرانی قرار دهد. تبدیل کردن برنامه نویسان ایرانی به بهترین برنامه نویسان جهان هدف ماست.</p>--}}
{{--        <p class="mb-8pt d-flex">--}}
{{--            <a href="" class="text-white-70 text-underline mr-8pt small">Terms</a>--}}
{{--            <a href="" class="text-white-70 text-underline small">Privacy policy</a>--}}
{{--        </p>--}}
{{--        <p class="text-white-50 small mt-n1 mb-0">Copyright 2019 &copy; All rights reserved.</p>--}}
{{--    </div>--}}
{{--</div>--}}


<footer class="pt-16pt" >
    <div class="container">
        <div class="card card-body border-0 shadow-lg w-75 mx-auto" style="background-color: #fed700">
            <div class="row d-flex align-items-center">
                <div class="col-lg-8 font-size-16pt text-center text-lg-left my-2">
                    برای اطلاع از جدیدترین اخبار و جشنوراه‌های تخفیفی زنبورک ایمیل خود را وارد کنید.
                </div>
                <div class="col-lg-4">
                    <form action="{{ route('register-newsletter') }}" method="post">
                        @csrf
                        <div class="form-group mb-0">
                            <div class="input-group input-group-merge rounded-lg" dir="">
                                <input required id="" type="email" name="email" dir="rtl" class="form-control form-control-appended @error('email') is-invalid @enderror" style="border-top-right-radius: 10px; border-bottom-right-radius: 10px" value="{{ old('email') }}" placeholder="ایمیل خود را وارد کنید..." >
                                <div class="input-group-append" >
                                    <div class="input-group-text py-0" style="border-top-left-radius: 10px; border-bottom-left-radius: 10px">
                                        <span class="text-muted"><button type="submit" class="btn btn-sm btn-light border-0 rounded-lg"><i class="material-icons fa-rotate-180">send</i></button></span>
                                    </div>
                                </div>
                                @error('email')
                                    <div class="text-accent font-size-12pt">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="row d-flex flex-column flex-lg-row align-items-center mb-16pt">
            <div class=" mb-16pt"><!-- mb -->
                <img class="" src="/assets/images/logo/logo-persian-dark.png">
            </div>
            <div class="ml-lg-auto d-flex align-content-center">
                <a href="https://github.com/" class="font-size-16pt mx-1 btn btn-light rounded"><i class="fab fa-github"></i></a>
                <a href="https://twitter.com/" class="font-size-16pt mx-1 btn btn-light rounded"><i class="fab fa-twitter"></i></a>
                <a href="https://instagram.com/zanburak" class="font-size-16pt mx-1 btn btn-light rounded"><i class="fab fa-instagram"></i></a>
                <a href="https://t.me/" class="font-size-16pt mx-1 btn btn-light rounded"><i class="fab fa-telegram"></i></a>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 pr-lg-64pt mb-16pt">
                <h5>درباره زنبورک</h5>
                <p class="font-size-16pt text-center text-lg-left lh-24pt">
                    زنبورک یکی از پرتلاش‌ترین و بروزترین وبسایت های آموزشی در سطح ایران است که همیشه تلاش کرده تا بتواند جدیدترین و بروزترین مقالات و دوره‌های آموزشی را در اختیار علاقه‌مندان ایرانی قرار دهد. تبدیل کردن برنامه نویسان ایرانی به بهترین برنامه نویسان جهان هدف ماست.
                </p>
                <a href="{{ route('paths') }}" class="text-accent font-size-16pt">
                    مشاهده مسیرهای یادگیری
                    <svg class="ml-2 " width="21" height="21" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                        <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                    </svg>
                </a>
            </div>
            <div class="col-lg-2 text-center text-lg-left">
                <h5>بخش‌های سایت</h5>
                <div class="d-flex flex-column font-size-16pt">
                    <a href="{{ route('terms') }}" class="mb-3">قوانین و مقررات</a>

                    <a href="#" class="mb-3">مدرسان زنبورک</a>

                    <a href="{{ route('about-us') }}" class="mb-3">درباره زنبورک</a>

                    <a href="{{ route('contact-us') }}" class="mb-3">ارتباط با ما</a>
                </div>

            </div>
            <div class="col-lg-4" style="background: url('/assets/images/other/svg/bg-1.svg'); background-repeat: no-repeat;background-size: contain">
                <h5>ارتباط با ما</h5>
                <div class="d-flex flex-column">
                    <div class="d-flex flex-row mb-4 font-size-16pt">
                        <div class="font-size-16pt">
                            <i class="fa fa-envelope-open-text"></i>
                            <span class="ml-2">ایمیل:</span>
                        </div>
                        <a class="ml-auto" href="mailto:info@zanburak.ir">info@zzanburakir</a>
                    </div>
                    <div class="d-flex flex-row mb-4 font-size-16pt">
                        <div class="font-size-16pt">
                            <i class="fab fa-instagram"></i>
                            <span class="ml-2">اینستاگرام:</span>
                        </div>
                        <a class="ml-auto" href="https://www.instagram.com/zanburak">zanburak</a>
                    </div>
                    <div class="d-flex flex-row mb-4 font-size-16pt">
                        <div class="font-size-16pt">
                            <i class="fab fa-telegram"></i>
                            <span class="ml-2">تلگرام:</span>
                        </div>
                        <a class="ml-auto" href="https://t.me/zanburak">zanburak@</a>
                    </div>
                    <div class="d-flex flex-row mb-4 font-size-16pt">
                        <div class="font-size-16pt">
                            <i class="fa fa-phone"></i>
                            <span class="ml-2">شماره تماس:</span>
                        </div>
                        <a class="ml-auto" href="tel:+986633416935">06633416935</a>
                    </div>
                </div>
                <div class="row card-group-row">
                    <div class="col-4 card-group-row__col">
                        <div class="card card-body d-flex border-0 shadow-none">
                            <img referrerpolicy='origin' class="my-auto w-100 cursor-pointer" id = 'rgvjwlaojzpeesgtjxlznbqe' onclick = 'window.open("https://logo.samandehi.ir/Verify.aspx?id=347012&p=xlaoaodsjyoeobpdrfthuiwk", "Popup","toolbar=no, scrollbars=no, location=no, statusbar=no, menubar=no, resizable=0, width=450, height=630, top=30")' alt = 'logo-samandehi' src = '/assets/images/logo/samandehi.png' />
                        </div>
                    </div>
                    <div class="col-4 card-group-row__col">
                        <div class="card card-body d-flex border-0 shadow-none">
                            <img referrerpolicy='origin' class="my-auto w-100 cursor-pointer" src="/assets/images/logo/enamad.png" onclick="window.open('https://trustseal.enamad.ir/?id=341545&amp;Code=brNAMQzTN0t2TlEMqlSM', 'Popup','toolbar=no, scrollbars=no, location=no, statusbar=no, menubar=no, resizable=0, width=450, height=630, top=30')" alt="enamad">
                        </div>
                    </div>
                    <div class="col-4 card-group-row__col">
                        <div class="card card-body d-flex border-0 shadow-none">
                            <img referrerpolicy='origin' class="my-auto w-100 cursor-pointer" src="/assets/images/logo/nashr.png"  alt="nashr">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center">
            <p class="text-50 small ">&copy; کلیه حقوق و دوره های سایت مربوط به زنبورک می‌باشد و هرگونه کپی برداری غیرمجاز می‌باشد.</p>
        </div>
    </div>
</footer>
