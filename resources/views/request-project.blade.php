@extends('layouts.master')
@section('title', 'درخواست پروژه')
@section('head')
    <link rel="stylesheet" href="/assets/css/editor/easymde/easymde-v2.18.0.css">
    <script src="/assets/js/editor/easymde/easymde-v2.18.0.js"></script>

    <link href="/assets/css/highlight/11.7.0/rainbow.min.css" rel="stylesheet">
    <script src="/assets/js/highlight/11.7.0/highlight.min.js"></script>
    <script src="/assets/js/highlight/highlightjs-line-numbers.min.js"></script>
    <script>
        hljs.highlightAll();
        hljs.initLineNumbersOnLoad();
    </script>

    <link href="/assets/css/editor/easymde/custom-style.css" rel="stylesheet">

@endsection
@section('content')
    <div class="mdk-header-layout__content page-content ">
        <div class="page-section mb-24pt">
            <div class="container page__container">
                <div class="row">
                    <div class="col-md-6 text-center text-lg-left text-md-left mt-3 mt-lg-0 mt-md-0 order-1 order-lg-0 order-md-0">

                        <h1>صفحه درخواست پروژه زنبورک (طراحی سایت و اپلیکیشن)</h1>
                        <p class="font-size-20pt text-50">
                            تو این صفحه میتونی اسم و سایر ویژگی‌های پروژه ای که میخوای رو وارد کنی تا همکاران ما در اسرع وقت باهات تماس بگیرن.
                        </p>


                        <a href="#request" class="btn  btn-light my-3">
                            سفارش پروژه
                            <svg class="ml-2 " width="18" height="18" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                                <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                            </svg>
                        </a>

                    </div>
                    <div class="col-md-6 order-0 order-lg-1 order-md-1">
                        <object class="w-100" height="400" data="/assets/images/other/svg/yellow/website/building-blocks.svg" type="image/svg+xml">
                        </object>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section mb-64pt"  id="request">
            <div class="container page__container">
                <h4>ایجاد پروژه</h4>
                <hr>
                <form class="request-project" action="{{ route('request-project') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card card-body rounded-lg border-0 shadow-none">
                                <label class="h6">عنوان پروژه</label>
                                <div class="form-group mb-12pt">
                                    <input type="text" id="subject" name="title" class="form-control rounded-lg @error('title') is-invalid @enderror" placeholder="عنوان را وارد کنید" autocomplete="off">
                                    <span class="invalid-feedback error-text title_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <hr>
                                <label class="h6">نوع پروژه</label>
                                <div class="d-flex flex-row mt-2">
                                    <div class="custom-control custom-radio">
                                        <input checked id="type-1" type="radio" name="type" value="website" class="custom-control-input">
                                        <label for="type-1" class="custom-control-label">وب‌سایت</label>
                                    </div>
                                    <div class="custom-control custom-radio ml-4">
                                        <input disabled id="type-2" type="radio" name="type" value="website" class="custom-control-input">
                                        <label for="type-2" class="custom-control-label">اپلیکیشن</label>
                                    </div>
                                    <div class="custom-control custom-radio ml-4">
                                        <input disabled id="type-3" type="radio" name="type" value="websiteAndApp" class="custom-control-input">
                                        <label for="type-3" class="custom-control-label">هردو</label>
                                    </div>
                                    @error('type')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card card-body rounded-lg border-0 shadow-none">
                                <label class="h6">بودجه</label>
                                <div class="form-group mb-12pt">
                                    <input type="text" id="min_price" name="min_price" class="form-control rounded-lg @error('min_price') is-invalid @enderror" placeholder="حداقل بودجه (تومان)">
                                    <span class="invalid-feedback error-text min_price_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('min_price')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group mb-12pt">
                                    <input type="text" id="max_price" name="max_price" class="form-control rounded-lg @error('max_price') is-invalid @enderror" placeholder="حداکثر بودجه (تومان)">
                                    <span class="invalid-feedback error-text max_price_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('max_price')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card card-body rounded-lg border-0 shadow-none">
                                <label class="h6">مهلت انجام</label>
                                <div class="form-group mb-12pt">
                                    <input type="number" min="1" id="deadline" name="deadline" class="form-control rounded-lg @error('deadline') is-invalid @enderror" placeholder="مثلا: 30 روز">
                                    <span class="invalid-feedback error-text deadline_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('deadline')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card card-body rounded-lg border-0 shadow-none">
                                <label class="h6">پیوست</label>
                                <div class="form-group mb-12pt">
                                    <input dir="ltr" type="file" id="attach_file" name="attach_file" accept=".jpg,.jpeg,.png,.pdf,.txt,.rar,.zip" class="form-control rounded-lg @error('attach_file') is-invalid @enderror">
                                    <small class="form-text text-muted">فرمت‌های مجاز: jpg,jpeg,png,pdf,txt,rar,zip</small>
                                    <span class="invalid-feedback error-text attach_file_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('attach_file')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card card-body rounded-lg border-0 shadow-none">
                                <label class="h6">توضیحات</label>
                                <div class="form-group mb-12pt">
                                    <textarea id="editor" name="description" class="form-control rounded-lg @error('description') is-invalid @enderror"></textarea>
                                    <span class="invalid-feedback error-text description_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('description')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group d-flex">
                                    <label class="my-auto mr-2 font-size-14pt font-bold cursor-pointer" for="preview-btn">پیش نمایش متن</label>
                                    <div class="custom-control custom-checkbox-toggle custom-control-inline my-auto">
                                        <input type="checkbox" id="preview-btn" class="custom-control-input">
                                        <label class="custom-control-label cursor-pointer" for="preview-btn"></label>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-body rounded-lg border-0 shadow-none">
                                <label class="h6">نمونه مشابه</label>
                                <div class="form-group mb-12pt">
                                    <input dir="ltr" type="text" id="sample" name="sample" class="form-control rounded-lg @error('sample') is-invalid @enderror" placeholder="http://zanburak.ir">
                                    <span class="invalid-feedback error-text sample_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    @error('sample')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card card-body rounded-lg border-0 shadow-none pb-0">
                                <div class="d-flex flex-row form-group">
                                    <button type="reset" class="btn btn-light w-50">ریست فرم</button>
                                    <button type="submit" class="btn btn-yellow ml-2 w-50"> ثبت درخواست <i class="fa fa-check ml-1"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('modal')
<div class="modal fade" id="modal-upload-image" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="m-2">
                <div class="mx-2">
                    <div class="font-size-20pt font-bold mb-2">
                        آپلود تصویر
                    </div>
                    <div class="text-50 font-size-16pt">
                        پسوند‌های مجاز :‌ jpg , jpeg , png
                    </div>
                </div>
                <hr>
                <div class="bg-light rounded-lg mt-4 p-2">
                    <form id="editor-upload-image" action="{{ route('editor-upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="file" name="image" accept=".jpg, .jpeg, .png" class="form-control rounded-lg">
                            <span class="invalid-feedback error-text image_error" role="alert">
                                <strong class="text-danger"></strong>
                            </span>
                        </div>
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-yellow rounded-lg mx-2">آپلود تصویر</button>
                            <button data-dismiss="modal" class="btn btn-light rounded-lg">انصراف</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script src="/assets/js/manage-project.js"></script>
    <script src="/assets/js/editor/easymde/config.js"></script>

    <script src="/assets/js/scrollTo.js"></script>
@endsection
