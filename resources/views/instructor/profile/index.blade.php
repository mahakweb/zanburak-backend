@extends('layouts.master')
@section('title', 'پروفایل مدرس')
@section('head')
@endsection
@section('header')
    {{--    @parent--}}
    @include('instructor.layouts.header')
@endsection
@section('content')
    <div class="mdk-header-layout__content page-content ">
        <div class="page-section bg-alt border-bottom-2">
            <div class="container page__container">
                <div class="d-flex flex-column flex-lg-row align-items-center">
                    <div class="d-flex flex-column flex-md-row align-items-center flex mb-16pt mb-lg-0 text-center text-md-left">
                        <div class="mb-16pt mb-md-0 mr-md-24pt">
                            <img src="/assets/images/illustration/teacher/128/black.svg" width="104" alt="teacher">
                        </div>
                        <div class="flex">
                            <h1 class="h2 mb-0">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h1>
                            <div class="rating mb-16pt d-inline-flex">
                                <div class="rating__item"><i class="material-icons">star</i></div>
                                <div class="rating__item"><i class="material-icons">star</i></div>
                                <div class="rating__item"><i class="material-icons">star</i></div>
                                <div class="rating__item"><i class="material-icons">star</i></div>
                                <div class="rating__item"><i class="material-icons">star_border</i></div>
                            </div>
                            <div>
                            <span class="chip chip-outline-secondary d-inline-flex align-items-center" data-toggle="tooltip" data-title="Experience IQ" data-placement="bottom">
                            <i class="material-icons icon--left">opacity</i> 2,300 points
                            </span>
                            </div>
                        </div>
                    </div>
                    <div class="ml-lg-16pt">
                        <a href="" class="btn btn-light">Follow</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section">
            <div class="container page__container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="flex" style="max-width: 100%">
                            <div class="card dashboard-area-tabs p-relative o-hidden mb-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card-header p-0 nav">
                                            <div class="row no-gutters d-flex flex-column w-100 p-3" role="tablist">
                                                <div class="mb-2">
                                                    <a href="#account-info" data-toggle="tab" role="tab" aria-selected="true" class="btn btn-outline-primary-red btn-rounded w-100 justify-content-start active"><span class="mr-2"><i class="fa fa-receipt font-size-16pt"></i></span>اطلاعات حساب</a>
                                                </div>
                                                <div class="mb-2">
                                                    <a href="#change-mobile" data-toggle="tab" role="tab" aria-selected="false" class="btn btn-outline-primary-red btn-rounded w-100 justify-content-start"><span class="mr-2"><i class="fa fa-mobile-alt font-size-16pt"></i></span>شماره موبایل</a>
                                                </div>
                                                <div class="mb-2">
                                                    <a href="#change-password" data-toggle="tab" role="tab" aria-selected="false" class="btn btn-outline-primary-red btn-rounded w-100 justify-content-start"><span class="mr-2"><i class="fa fa-lock font-size-16pt"></i></span>تغییر رمز عبور</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body tab-content">
                                            <div id="account-info" class="alert alert-danger tab-pane active text-70">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aperiam eaque error laborum ipsum consequatur nobis dicta totam facilis corporis, porro cupiditate inventore minus vero neque accusamus illo temporibus officiis natus.</div>
                                            <div id="change-mobile" class=" tab-pane  text-70">
                                                <div class="alert alert-primary border-primary">
                                                    <h6>توجه:</h6>
                                                    <p class="text-justify">
                                                        شماره تلفن شما برای کارهای مختلفی در سایت مورد استفاده قرار میگیرد با وارد کردن شماره تلفن خود و فعال کردن آن میتوانید بخشی از فعالیت های خود را در زنبورک سریع تر انجام دهید. در زیر لیست کارهای که با شماره تلفن شما در سایت انجام میشود، را برایتان آورده ایم.
                                                    </p>

                                                    در آینده نزدیک ورود به سایت تماما با شماره تلفن همراه انجام خواهد شد. (به زودی)
                                                    <br>
                                                    در هنگام ورود به سایت پیامکی برای شما ارسال میشود تا از ورود‌های بدون اجازه مطلع شوید.
                                                    <br>
                                                    در صورت فعال‌سازی نوتیفیکشن یک دوره، قسمت‌های جدید آن دوره با پیامک به شما اطلاع داده خواهد شد. (به زودی)
                                                    <br>
                                                    در صورت اضافه شدن مورد جدید در این لیست اضافه خواهد شد ...
                                                    <br>
                                                </div>

                                                <form action="" method="post">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-4">
                                                                <label class="text-label" for="password_2">شماره تماس فعلی</label>
                                                                <div class="input-group input-group-merge">
                                                                    <input disabled dir="ltr" type="text" class="form-control form-control-prepended" value="{{ auth()->user()->mobile }}">

                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text">
                                                                            <i class="fa fa-mobile"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group mb-5">
                                                                <label class="text-label" for="password_2">شماره تماس</label>
                                                                <div class="input-group input-group-merge">
                                                                    <input id=""  dir="ltr" type="text" class="form-control form-control-prepended @error('mobile') is-invalid @enderror" name="mobile" required autocomplete="mobile">
                                                                    @error('mobile')
                                                                    <span class="invalid-feedback" role="alert">
                                                                        <strong>{{ $message }}</strong>
                                                                    </span>
                                                                    @enderror

                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text">
                                                                            <i class="fa fa-mobile"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <button class="btn btn-block btn-primary w-auto" type="submit">ثبت شماره</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                            <div id="change-password" class=" tab-pane  text-70">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h4>تغییر رمز عبور</h4>
                                                        <form id="profile-change-password" action="{{ route('change-password') }}" method="post">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="form-group mb-3">
                                                                <label class="text-label" for="password_2">رمز عبور فعلی</label>
                                                                <div class="input-group input-group-merge">
                                                                    <input id="" type="password" class="form-control form-control-prepended" name="old-password" required autocomplete="current-password">

                                                                    <span class="invalid-feedback error-text old-password_error" role="alert">
                                                                        <strong></strong>
                                                                    </span>

                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text">
                                                                            <i class="fa fa-lock"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="form-group">
                                                                <label class="text-label" for="password_2">رمز عبور جدید</label>
                                                                <div class="input-group input-group-merge">
                                                                    <input id="" type="password" class="form-control form-control-prepended" name="new-password" required autocomplete="current-password">
                                                                    <span class="invalid-feedback error-text new-password_error" role="alert">
                                                                        <strong></strong>
                                                                    </span>
                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text">
                                                                            <i class="fa fa-lock"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="alert alert-danger">
                                                                - حداقل یک حرف کوچک استفاده کنید
                                                                <br>
                                                                - حداقل یک حرف بزرگ استفاده کنید
                                                                <br>
                                                                - پسورد حداقل باید ۸ کاراکتر باشد
                                                                <br>
                                                                - حداقل از یک عدد استفاده کنید
                                                            </div>
                                                            <div class="form-group">
                                                                <button class="btn btn-block btn-primary w-auto" type="submit">ثبت تغییرات</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('sidebar')
    @include('instructor.layouts.sidebar')
@endsection
@section('script')
    <script src="/assets/js/manage-profile.js"></script>
@endsection
