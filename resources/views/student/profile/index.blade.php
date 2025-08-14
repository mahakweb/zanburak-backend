@extends('student.layouts.master')
@section('title', 'پروفایل دانشجو')
@section('head')
<link type="text/css" href="/assets/css/activation-code.css" rel="stylesheet">
<link type="text/css" href="/assets/css/profile-cover-and-profile.css" rel="stylesheet">
<link type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.5/css/bulma.min.css" rel="stylesheet">
@endsection

@section('student-content')

<div class="row">
    <div class="col-md-3">
        <div class="nav">
            <div class="w-100" role="tablist">
                <style>
                    .profile-menu .menu-item{
                        border: 1px solid lightgrey;
                        border-radius: 12px;
                        margin-bottom: 8px;
                        padding: 12px 15px;
                        display: flex;
                        background-color: white;
                    }
                    .profile-menu .menu-item:hover{
                        border-color: #fed700;
                        color: black!important;
                    }
                    .profile-menu .menu-item:hover .menu-item-icon{
                        background-color: #fed700;
                    }
                    .profile-menu .active .menu-item-icon{
                        background-color: #fed700!important;
                    }
                    .profile-menu .active{
                        border-color: #fed700;
                        background-color: rgba(255,226,17,0.14)!important;
                    }
                    .profile-menu .menu-item .menu-item-icon{
                        width: 35px;
                        height: 35px;
                        padding: 5px;
                        border-radius: 7px;
                        background-color: lightgray;
                        display: flex;
                        margin-left: 15px;
                    }
                    .profile-menu .menu-item .menu-item-icon svg{
                        margin: auto;
                    }
                    .profile-menu .menu-item .menu-item-text{
                        margin: auto 0px;
                        font-weight: bold;
                    }
                </style>
                <div class="nav-tabs profile-menu">
                        <a href="#account-info" data-toggle="tab" role="tab" aria-selected="true" class="menu-item active">
                            <div class="menu-item-icon">
                                <svg width="22" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="0.4" d="M16.2391 3.6499H7.75906C5.28906 3.6499 3.28906 5.6599 3.28906 8.1199V17.5299C3.28906 19.9899 5.29906 21.9999 7.75906 21.9999H16.2291C18.6991 21.9999 20.6991 19.9899 20.6991 17.5299V8.1199C20.7091 5.6499 18.6991 3.6499 16.2391 3.6499Z" fill="#292D32"></path> <path d="M14.3498 2H9.64977C8.60977 2 7.75977 2.84 7.75977 3.88V4.82C7.75977 5.86 8.59977 6.7 9.63977 6.7H14.3498C15.3898 6.7 16.2298 5.86 16.2298 4.82V3.88C16.2398 2.84 15.3898 2 14.3498 2Z" fill="#292D32"></path> <path d="M15 12.9502H8C7.59 12.9502 7.25 12.6102 7.25 12.2002C7.25 11.7902 7.59 11.4502 8 11.4502H15C15.41 11.4502 15.75 11.7902 15.75 12.2002C15.75 12.6102 15.41 12.9502 15 12.9502Z" fill="#292D32"></path> <path d="M12.38 16.9502H8C7.59 16.9502 7.25 16.6102 7.25 16.2002C7.25 15.7902 7.59 15.4502 8 15.4502H12.38C12.79 15.4502 13.13 15.7902 13.13 16.2002C13.13 16.6102 12.79 16.9502 12.38 16.9502Z" fill="#292D32"></path> </g></svg>
                            </div>
                            <div class="menu-item-text">اطلاعات حساب</div>

                        </a>
                        <a href="#change-mobile" data-toggle="tab" role="tab" aria-selected="false" class="menu-item">
                            <div class="menu-item-icon">
                                <svg width="22" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="0.4" d="M16.24 2H7.76C5 2 4 3 4 5.81V18.19C4 21 5 22 7.76 22H16.23C19 22 20 21 20 18.19V5.81C20 3 19 2 16.24 2Z" fill="#292D32"></path> <path d="M14 6.25H10C9.59 6.25 9.25 5.91 9.25 5.5C9.25 5.09 9.59 4.75 10 4.75H14C14.41 4.75 14.75 5.09 14.75 5.5C14.75 5.91 14.41 6.25 14 6.25Z" fill="#292D32"></path> <path d="M12 19.3C12.9665 19.3 13.75 18.5165 13.75 17.55C13.75 16.5835 12.9665 15.8 12 15.8C11.0335 15.8 10.25 16.5835 10.25 17.55C10.25 18.5165 11.0335 19.3 12 19.3Z" fill="#292D32"></path> </g></svg>
                            </div>
                            <div class="menu-item-text">شماره موبایل</div>

                        </a>
                        <a href="#change-password" data-toggle="tab" role="tab" aria-selected="false" class="menu-item">
                            <div class="menu-item-icon">
                                <svg width="22" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="0.4" d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.18C2 19.83 4.17 22 7.81 22H16.18C19.82 22 21.99 19.83 21.99 16.19V7.81C22 4.17 19.83 2 16.19 2Z" fill="#292D32"></path> <path d="M15.8897 8.11007C14.4097 6.64007 12.0097 6.64007 10.5397 8.11007C9.50971 9.13007 9.19971 10.6101 9.59971 11.9101L7.24971 14.2601C7.08971 14.4301 6.96971 14.7601 7.00971 15.0001L7.15971 16.0901C7.20971 16.4501 7.54971 16.7901 7.90971 16.8401L8.99971 17.0001C9.23971 17.0301 9.56971 16.9301 9.73971 16.7501L10.1497 16.3401C10.2497 16.2501 10.2497 16.0901 10.1497 15.9901L9.17971 15.0201C9.03971 14.8801 9.03971 14.6401 9.17971 14.4901C9.31971 14.3501 9.55971 14.3501 9.70971 14.4901L10.6797 15.4601C10.7697 15.5501 10.9297 15.5501 11.0297 15.4601L12.0897 14.4101C13.3797 14.8101 14.8597 14.5001 15.8897 13.4801C17.3697 11.9901 17.3697 9.59007 15.8897 8.11007ZM13.2497 12.0001C12.5597 12.0001 11.9997 11.4401 11.9997 10.7501C11.9997 10.0601 12.5597 9.50007 13.2497 9.50007C13.9397 9.50007 14.4997 10.0601 14.4997 10.7501C14.4997 11.4401 13.9397 12.0001 13.2497 12.0001Z" fill="#292D32"></path> </g></svg>
                            </div>
                            <div class="menu-item-text">تغییر رمز عبور</div>

                        </a>
                        <a href="#login-statistics" data-toggle="tab" role="tab" aria-selected="false" class="menu-item">
                            <div class="menu-item-icon">
                                <svg width="22" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="0.4" d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.18C2 19.83 4.17 22 7.81 22H16.18C19.82 22 21.99 19.83 21.99 16.19V7.81C22 4.17 19.83 2 16.19 2Z" fill="#292D32"></path> <path d="M16.4198 7.8099V16.1899C16.4198 16.8299 15.8998 17.3499 15.2598 17.3499C14.6098 17.3499 14.0898 16.8299 14.0898 16.1899V7.8099C14.0898 7.1699 14.6098 6.6499 15.2598 6.6499C15.8998 6.6499 16.4198 7.1699 16.4198 7.8099Z" fill="#292D32"></path> <path d="M9.91008 12.93V16.19C9.91008 16.83 9.39008 17.35 8.74008 17.35C8.10008 17.35 7.58008 16.83 7.58008 16.19V12.93C7.58008 12.29 8.10008 11.77 8.74008 11.77C9.39008 11.77 9.91008 12.29 9.91008 12.93Z" fill="#292D32"></path> </g></svg>
                            </div>
                            <div class="menu-item-text">آمار ورود</div>

                        </a>
                    {{--  <div class="mb-2">
                        <a href="#manage-notice" data-toggle="tab" role="tab" aria-selected="false" class="btn btn-light rounded-lg w-100 d-flex"><span class="mr-2"><i class="fa fa-lightbulb font-size-16pt"></i></span><span>مدیریت اطلاع رسانی</span><span class="ml-auto"><i class="fa fa-arrow-alt-circle-left"></i></span></a>
                    </div>  --}}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="flex" style="max-width: 100%">
            <div class="card shadow-none border-0 dashboard-area-tabs p-relative o-hidden mb-0">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card-body tab-content">
                            <div id="account-info" class=" tab-pane active text-70">
                                <h4><span class="mr-2 text-secondary">&#x2022;</span>اطلاعات حساب</h4>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="user_profile_cap">
                                            <form id="update-cover" action="{{ route('student-update-cover-pic') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="user_profile_cover">
                                                    <div class="cover-pic">
                                                        <label class="-label" for="cover-file">
                                                            <span class="">
                                                                <svg class="" width="40" height="40" viewBox="0 0 32 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M20.8137 2.41488C22.4128 3.05138 22.9021 5.26804 23.556 5.98054C24.2099 6.69304 25.1457 6.93529 25.6634 6.93529C28.4152 6.93529 30.6462 9.16621 30.6462 11.9165V21.0919C30.6462 24.781 27.6537 27.7735 23.9645 27.7735H8.03616C4.34541 27.7735 1.35449 24.781 1.35449 21.0919V11.9165C1.35449 9.16621 3.58541 6.93529 6.33724 6.93529C6.85341 6.93529 7.78916 6.69304 8.44466 5.98054C9.09858 5.26804 9.58624 3.05138 11.1854 2.41488C12.7862 1.77837 19.2145 1.77837 20.8137 2.41488Z" stroke="#ECEDEE" stroke-width="2.375" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                    <path d="M24.7013 11.0417H24.7155" stroke="#ECEDEE" stroke-width="3.16667" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21.0334 16.7863C21.0334 14.006 18.7803 11.7529 16 11.7529C13.2196 11.7529 10.9666 14.006 10.9666 16.7863C10.9666 19.5667 13.2196 21.8198 16 21.8198C18.7803 21.8198 21.0334 19.5667 21.0334 16.7863Z" stroke="#ECEDEE" stroke-width="2.375" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                </svg>
                                                            </span>
                                                        </label>
                                                        <input name="cover" class="" accept=".jpg,.jpeg,.png,.PNG,.JPG,.JPEG" id="cover-file" type="file" />
                                                        <img src="{{ auth()->user()->cover_pic }}" id="cover-output" class="border-2 border-light" />
                                                    </div>
                                                </div>
                                            </form>
                                            <form id="update-profile" action="{{ route('student-update-profile-pic') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="user_profile_headline">
                                                    <div class="profile-pic">
                                                        <label class="-label" for="profile-file">
                                                            <span class="">
                                                                <svg class="" width="25" height="25" viewBox="0 0 32 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M20.8137 2.41488C22.4128 3.05138 22.9021 5.26804 23.556 5.98054C24.2099 6.69304 25.1457 6.93529 25.6634 6.93529C28.4152 6.93529 30.6462 9.16621 30.6462 11.9165V21.0919C30.6462 24.781 27.6537 27.7735 23.9645 27.7735H8.03616C4.34541 27.7735 1.35449 24.781 1.35449 21.0919V11.9165C1.35449 9.16621 3.58541 6.93529 6.33724 6.93529C6.85341 6.93529 7.78916 6.69304 8.44466 5.98054C9.09858 5.26804 9.58624 3.05138 11.1854 2.41488C12.7862 1.77837 19.2145 1.77837 20.8137 2.41488Z" stroke="#ECEDEE" stroke-width="2.375" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                    <path d="M24.7013 11.0417H24.7155" stroke="#ECEDEE" stroke-width="3.16667" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21.0334 16.7863C21.0334 14.006 18.7803 11.7529 16 11.7529C13.2196 11.7529 10.9666 14.006 10.9666 16.7863C10.9666 19.5667 13.2196 21.8198 16 21.8198C18.7803 21.8198 21.0334 19.5667 21.0334 16.7863Z" stroke="#ECEDEE" stroke-width="2.375" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                </svg>
                                                            </span>
                                                        </label>
                                                        <input name="profile" class="" accept=".jpg,.jpeg,.png,.PNG,.JPG,.JPEG" id="profile-file" type="file" />
                                                        <img src="{{ auth()->user()->profile_pic }}" id="profile-output" width="200" />
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <form id="profile-change-info" action="{{ route('student-profile') }}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')

                                    <div class="row mb-3 mt-n48pt">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="name_2">نام</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control rounded-lg" name="first_name" placeholder="نام خود را وارد نمایید" value="{{ old('first_name') ? old('first_name') : auth()->user()->first_name }}" autocomplete="first_name">
                                                    <span class="invalid-feedback error-text first_name_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="name_2">نام خانوادگی</label>
                                                <div class="input-group">
                                                    <input id="name_2" type="text" class="form-control rounded-lg" name="last_name" placeholder="نام خانوادگی خود را وارد نمایید" value="{{ old('last_name') ? old('last_name') : auth()->user()->last_name }}" autocomplete="last_name">
                                                    <span class="invalid-feedback error-text last_name_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="name_2">ایمیل</label>
                                                <div class="input-group ">
                                                    <input disabled readonly style="cursor: not-allowed" type="text" dir="ltr" class="form-control text-secondary rounded-lg" placeholder="info@zanburak.ir" value="{{ auth()->user()->email }}">
                                                </div>
                                                <small class="form-text text-muted">در حال حاضر ایمیل قابل تغییر نمیباشد.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="name_2">آدرس پروفایل</label>
                                                <div class="input-group input-group-merge" dir="ltr">

                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://zanburak.ir@</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="username" placeholder="{{ auth()->user()->username }}" value="{{ old('username') ? old('username') : auth()->user()->username }}" autocomplete="">
                                                    <span class="invalid-feedback error-text username_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <h4><span class="mr-2 text-secondary">&#x2022;</span>اطلاعات فردی</h4>
                                    <div class="row mb-3">

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="name_2">تاریخ تولد</label>
                                                <div class="input-group">
                                                    <input dir="ltr" type="text" class="form-control rounded-lg" name="birth-date" maxlength="10" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" placeholder="----/--/--" value="{{ old('birth_date') ? old('birth_date') : auth()->user()->info->birth_date }}" data-mask="####/##/##" data-mask-reverse="true">
                                                    <span class="invalid-feedback error-text birth-date_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="name_2">شغل یا حرفه</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control rounded-lg" name="job" placeholder="سمت یا حرفه ای که در آن مشغول هستی" value="{{ old('job') ? old('job') : auth()->user()->info->job }}" autocomplete="job">
                                                    <span class="invalid-feedback error-text job_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="text-label" for="">درباره من</label>
                                                <div class="input-group">
                                                    <textarea rows="6" class="form-control rounded-lg" name="about" placeholder="توضیحی مختصر در مورد خودتان و حرفه ای که در آن تخصص دارید.">{{ old('about') ? old('about') : auth()->user()->info->about }}</textarea>
                                                    <span class="invalid-feedback error-text about_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <h4><span class="mr-2 text-secondary">&#x2022;</span>راه های ارتباطی</h4>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="">وبسایت</label>
                                                <div class="input-group input-group-merge" dir="ltr">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="website" placeholder="" value="{{ old('website') ? old('website') : auth()->user()->info->website }}">
                                                    <span class="invalid-feedback error-text website_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="">گیت هاب</label>
                                                <div class="input-group input-group-merge" dir="ltr">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://github.com/</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="github" placeholder="" value="{{ old('github') ? old('github') : auth()->user()->info->github }}">
                                                    <span class="invalid-feedback error-text github_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="">لینکدین</label>
                                                <div class="input-group input-group-merge" dir="ltr">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://linkedin.com/in/</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="linkedin" placeholder="" value="{{ old('linkedin') ? old('linkedin') : auth()->user()->info->linkedin }}">
                                                    <span class="invalid-feedback error-text linkedin_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="">تلگرام</label>
                                                <div class="input-group input-group-merge" dir="ltr">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://t.me/</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="telegram" placeholder="" value="{{ old('telegram') ? old('telegram') : auth()->user()->info->telegram }}">
                                                    <span class="invalid-feedback error-text telegram_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="">اینستاگرام</label>
                                                <div class="input-group input-group-merge" dir="ltr">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://instagram.com/</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="instagram" placeholder="" value="{{ old('instagram') ? old('instagram') : auth()->user()->info->instagram }}">
                                                    <span class="invalid-feedback error-text instagram_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="text-label" for="">توییتر</label>
                                                <div class="input-group input-group-merge" dir="ltr">
                                                    <div class="input-group-append">
                                                        <div class="input-group-text rounded-lg-left">
                                                            <span class="text-muted">https://twitter.com/</span>
                                                        </div>
                                                    </div>
                                                    <input type="text" dir="ltr" class="form-control rounded-lg-right form-control-appended" name="twitter" placeholder="" value="{{ old('twitter') ? old('twitter') : auth()->user()->info->twitter }}">
                                                    <span class="invalid-feedback error-text twitter_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-yellow">ثبت تغییرات</button>
                                </form>
                            </div>
                            <div id="change-mobile" class=" tab-pane text-70">
                                <h4><span class="mr-2 text-secondary">&#x2022;</span>مدیریت شماره موبایل</h4>
                                <div class="alert alert-primary-light border-0 lh-24pt text-70 font-size-12pt font-medium rounded-lg px-3">
                                    <h5>توجه</h5>
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

                                <form id="profile-change-mobile" class="profile-change-mobile" action="{{ route('student-update-mobile') }}" method="post">
                                    @csrf
                                    @method('patch')
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-4">
                                                <label class="text-label" for="password_2">شماره تماس فعلی</label>
                                                <div class="input-group input-group-merge">
                                                    <input disabled dir="ltr" type="tel" class="form-control bg-light rounded-lg-left form-control-prepended" value="{{ auth()->user()->mobile }}">

                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text bg-light rounded-lg-right">
                                                            <i class="fa fa-mobile"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{--  <input type="hidden" name="current_mobile" value="{{ auth()->user()->mobile }}">  --}}
                                            <div class="form-group mb-5">
                                                <label class="text-label" for="">شماره تماس</label>
                                                <div class="input-group input-group-merge">
                                                    <input dir="ltr" type="tel" maxlength="11" class="form-control rounded-lg-left form-control-prepended" name="mobile" autocomplete="mobile">
                                                    <span class="invalid-feedback error-text mobile_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>

                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text rounded-lg-right">
                                                            <i class="fa fa-mobile"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <button class="btn btn-yellow" type="submit">ثبت شماره</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="change-password" class=" tab-pane text-70">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4><span class="mr-2 text-secondary">&#x2022;</span>تغییر رمز عبور</h4>
                                        <form id="profile-change-password" action="{{ route('change-password') }}" method="post">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-group mb-3">
                                                <label class="text-label" for="password_2">رمز عبور فعلی</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="password" class="form-control rounded-lg-left form-control-prepended" name="old-password" required autocomplete="current-password">

                                                    <span class="invalid-feedback error-text old-password_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>

                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text rounded-lg-right">
                                                            <i class="fa fa-lock"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="text-label" for="password_2">رمز عبور جدید</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="password" class="form-control rounded-lg-left form-control-prepended" name="new-password" required autocomplete="current-password">
                                                    <span class="invalid-feedback error-text new-password_error" role="alert">
                                                        <strong class="text-danger"></strong>
                                                    </span>
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text rounded-lg-right">
                                                            <i class="fa fa-lock"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-warning border-0 lh-24pt text-70 font-size-12pt font-medium rounded-lg px-3">
                                                - حداقل یک حرف کوچک استفاده کنید
                                                <br>
                                                - حداقل یک حرف بزرگ استفاده کنید
                                                <br>
                                                - پسورد حداقل باید ۸ کاراکتر باشد
                                                <br>
                                                - حداقل از یک عدد استفاده کنید
                                            </div>
                                            <div class="form-group">
                                                <button class="btn btn-yellow" type="submit">ثبت تغییرات</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div id="login-statistics" class=" tab-pane text-70">
                                <h4><span class="mr-2 text-secondary">&#x2022;</span>مدیریت ورود به وب سایت</h4>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            @foreach(auth()->user()->sessions()->get() as $session)
                                            <tr id="sessions" class="card shadow-none card-body d-flex flex-row align-items-center rounded-lg mb-1 py-1 {{ $session->id == request()->session()->getId() ? 'bg-light' : '' }}">

                                                <td class="py-1 d-flex flex-row o-hidden" style="width: 230px">
                                                    <div>
                                                        <span class="btn btn-yellow p-2 rounded-lg mr-2" style="width: 30px">
                                                            <i class="fab fa-@if($session->platform() == 'windows')windows @elseif($session->platform() == 'androidos')android @elseif($session->platform() == 'chromeos')chrome @elseif($session->platform() == 'Macintosh')apple @elseif($session->platform() == 'ios')apple @elseif($session->platform() == 'linux')linux @else()question @endif"></i>
                                                        </span>
                                                    </div>
                                                    <div class="my-auto font-bold text-nowrap">
                                                        {{ $session->deviceInfo() }}
                                                    </div>
                                                </td>
                                                <td class="py-1 text-center" style="width: 120px">
                                                    {{ $session->ip_address }}
                                                </td>
                                                <td class="py-1 text-center" style="width: 90px">
                                                    {{ jdate($session->created_at )->format("Y/m/d") }}
                                                </td>
                                                <td class="py-1 text-center" style="width: 90px">
                                                    {{ jdate($session->created_at )->format("H:i:s") }}
                                                </td>
                                                <td class="py-1 text-center" style="width: 70px">
                                                    @if($session->id == request()->session()->getId())
                                                    <button class="btn btn-sm btn-outline-success rounded-lg text-nowrap">نشست فعلی</button>
                                                    @else
                                                    <form class="delete-session text-center" action="{{ route('terminate-session') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $session->id }}">
                                                        <button class="btn btn-sm btn-outline-accent rounded-lg text-nowrap">حذف نشست</button>
                                                    </form>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {{--  <div id="manage-notice" class=" tab-pane text-70">
                                <h4><span class="mr-2 text-secondary">&#x2022;</span>صفحه مدیریت دوره ها</h4>
                            </div>  --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="/assets/js/manage-profile.js"></script>
<script src="/assets/js/activation-code.js"></script>
<script src="/assets/vendor/jquery.mask.min.js"></script>
<script>
    // Javascript to enable link to tab
    var hash = location.hash.replace(/^#/, '');  // ^ means starting, meaning only match the first hash
    if (hash) {
        $('.nav-tabs a[href="#' + hash + '"]').tab('show');
    }

    // Change hash for page-reload
    $('.nav-tabs a').on('show.bs.tab', function (e) {
        window.location.hash = e.target.hash;
    })
</script>
@endsection
@section('modal')
<!-- Modal change mobile -->
<div class="modal fade" data-keyboard="false" data-backdrop="static" id="modal-change-mobile" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div class="alert alert-primary-light border-0 rounded-lg font-bold text-50">
                    کد تایید به شماره <span id="user_phone" class="font-bold mx-4pt text-70"></span> ارسال شد.
                </div>
                <button type="button" class="btn close" data-dismiss="modal" aria-label="Close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M9.78363 8.30602C9.37561 7.89799 8.71407 7.89799 8.30604 8.30602C7.89802 8.71404 7.89802 9.37557 8.30604 9.78359L10.5225 12L8.30602 14.2164C7.89799 14.6244 7.89799 15.286 8.30602 15.694C8.71404 16.102 9.37558 16.102 9.78361 15.694L12 13.4776L14.2164 15.6939C14.6244 16.1019 15.286 16.1019 15.694 15.6939C16.102 15.2859 16.102 14.6243 15.694 14.2163L13.4776 12L15.694 9.78369C16.102 9.37567 16.102 8.71413 15.694 8.30611C15.2859 7.89809 14.6244 7.89809 14.2164 8.30611L12 10.5224L9.78363 8.30602Z"></path>
                        <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M0 12C0 21.882 2.118 24 12 24C21.882 24 24 21.882 24 12C24 2.118 21.882 0 12 0C2.118 0 0 2.118 0 12ZM2 12C2 14.4249 2.13254 16.2369 2.43771 17.6101C2.73783 18.9605 3.17768 19.7608 3.70846 20.2915C4.23924 20.8223 5.03949 21.2622 6.38993 21.5623C7.76307 21.8675 9.57515 22 12 22C14.4249 22 16.2369 21.8675 17.6101 21.5623C18.9605 21.2622 19.7608 20.8223 20.2915 20.2915C20.8223 19.7608 21.2622 18.9605 21.5623 17.6101C21.8675 16.2369 22 14.4249 22 12C22 9.57515 21.8675 7.76307 21.5623 6.38993C21.2622 5.03949 20.8223 4.23924 20.2915 3.70846C19.7608 3.17768 18.9605 2.73783 17.6101 2.43771C16.2369 2.13254 14.4249 2 12 2C9.57515 2 7.76307 2.13254 6.38993 2.43771C5.03949 2.73783 4.23924 3.17768 3.70846 3.70846C3.17768 4.23924 2.73783 5.03949 2.43771 6.38993C2.13254 7.76307 2 9.57515 2 12Z" fill-opacity="0.4"></path>
                    </svg>
                </button>
            </div>
            <form class="profile-verify-token" action="{{ route('student-verify-token-mobile') }}" method="post">
                @csrf
                <div class="modal-body border-0">
                    <div class="row flex-center">
                        <div class="col">
                            <input type="tel" name="code" class="activation-code-input w-100 " placeholder="کد تایید">
                            <span class="invalid-feedback error-text code_error" role="alert">
                                <strong class="text-danger"></strong>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-yellow">تایید کد</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
