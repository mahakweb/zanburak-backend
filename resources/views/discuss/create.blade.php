@extends('layouts.master')
@section('title', 'ایجاد پرسش جدید')
@section('head')

    <link type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet">
    <link type="text/css" href="/assets/css/custom-select2.css" rel="stylesheet">
    <!-- Include stylesheet -->

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
        <div class="container page__container">
            <div class="page-section">
                @if (session('success'))
                    <div class="alert border-success border-2 rounded-lg pb-0" style="background-color: rgba(65,228,0,0.22)"
                        role="alert">
                        <div class="d-flex">
                            <div class="mr-8pt">
                                <i class="material-icons text-success">check_circle</i>
                            </div>
                            <div class="flex mt-1" style="min-width: 180px">
                                <h6 class="text-shadow">
                                    <strong> موفق - </strong> {{ session('success') }}.
                                </h6>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        <div class="alert border-accent border-2 rounded-lg pb-0"
                            style="background-color: rgba(255,29,0,0.22)" role="alert">
                            <div class="d-flex">
                                <div class="mr-8pt">
                                    <i class="material-icons text-accent">cancel</i>
                                </div>
                                <div class="flex mt-1" style="min-width: 180px">
                                    <h6 class="text-shadow">
                                        <strong> خطا! - </strong> {{ $error }}.
                                    </h6>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-body border-0 shadow-none">
                            <div class="d-flex flex-column mb-20pt">
                                <div class="d-flex flex-md-row ">
                                    <a href="{{ route('profile-index', auth()->user()->username) }}" class="avatar avatar-xl my-auto border-3 border-light rounded-circle">
                                        <img src="{{ auth()->user()->profile_pic }}" class="avatar-img rounded-circle shadow" alt="{{ auth()->user()->username }}">
                                    </a>
                                    <div class="flex pt-8pt ml-3 my-auto">
                                        <div class="d-flex flex-column flex-lg-row flex-md-row">
                                            <a href="{{ route('profile-index', auth()->user()->username) }}">
                                                <h5 class="mb-4pt">
                                                    {{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}
                                                </h5>
                                            </a>
                                            <div class="ml-lg-2 font-size-14pt text-50">({{ number_format(auth()->user()->currentScore(), 0, '.', ',') }} تجربه)</div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <div class="text-muted">{{ auth()->user()->username }}@</div>
                                            @if ( auth()->user()->info->job)
                                            <div class="mt-1 text-50 d-flex">
                                                <div class="font-size-14pt font-bold mr-1">تخصص: </div>
                                                <div class="">{{ auth()->user()->info->job }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="my-2">
                                <div class="alert alert-info rounded-lg font-size-14pt p-3">
                                    <h5 class="">قبل از ارسال پرسش دقت کنید</h5>
                                    <p class="">با تشکر از اینکه تصمیم گرفته‌اید که سوال مورد نظر خودتان را در بخش
                                        پرسش و پاسخ زنبورک مطرح کنید. قبل از اینکه پرسش خود را ارسال کنید، لطفا به نکات زیر
                                        توجه فرمایید.</p>
                                    <p class="my-2">۱.از ارسال پرسش تکراری خودداری کنید</p>
                                    <p class="">۲.لطفا از کلمات و جملات توهین آمیز استفاده نکنید</p>
                                </div>
                            </div>
                            <form class="send-question" action="{{ route('discuss-store-question') }}" method="post">
                                @csrf
                                <label class="font-bold mb-1">عنوان پرسش</label>
                                <div class="form-group mb-24pt">
                                    <input type="text" id="search-subject" name="subject" class="form-control rounded-lg"
                                        placeholder="عنوان را وارد کنید" autocomplete="off">
                                    <span class="invalid-feedback error-text subject_error" role="alert">
                                        <strong></strong>
                                    </span>
                                    <div id="search-result" class="w-100">

                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="font-bold text-70 mb-1">دسته‌بندی پرسش</label>
                                    <select name="category" id="" data-toggle="select"
                                        class="form-control rounded-lg">
                                        <option value="{{ null }}"></option>
                                        @foreach (\App\Models\QuestionCategory::all() as $category)
                                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                    <span class="invalid-feedback error-text category_error" role="alert">
                                        <strong></strong>
                                    </span>
                                </div>
                                <div class="form-group mb-8pt">
                                    <label class="font-bold text-70 mb-1">متن پرسش</label>

                                    <textarea id="editor" name="question" class="form-control">

                                    </textarea>


                                    <span class="invalid-feedback error-text question_error" role="alert">
                                        <strong></strong>
                                    </span>
                                </div>
                                <div class="form-group d-flex">
                                    <label class="my-auto mr-2 font-size-14pt font-bold cursor-pointer" for="preview-btn">پیش نمایش متن</label>
                                    <div class="custom-control custom-checkbox-toggle custom-control-inline my-auto">
                                        <input type="checkbox" id="preview-btn" class="custom-control-input">
                                        <label class="custom-control-label cursor-pointer" for="preview-btn"></label>
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group  my-32">
                                    <label class="font-bold text-70 mb-1" for="select03">برچسب ها (حداکثر 3 مورد)</label>
                                    <select name="tags[]" multiple id="tags" class="form-control rounded-lg">
                                        @foreach (\Cviebrock\EloquentTaggable\Models\Tag::all() as $tag)
                                            <option value="{{ $tag->name }}">{{ $tag->name }}</option>
                                        @endforeach
                                    </select>

                                    <span class="invalid-feedback error-text tags_error" role="alert">
                                        <strong></strong>
                                    </span>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-yellow">ثبت پرسش</button>
                                </div>

                            </form>
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="accordion js-accordion accordion--boxed mb-32pt" id="category-parent">
                            <div class="accordion__item bg-transparent">
                                <div class="accordion__toggle collapsed cursor-pointer" data-toggle="collapse" data-target="#category" data-parent="#category-parent">
                                    <span class="flex">
                                        <h5 class="mb-1">دسته بندی سوالات</h5>
                                    </span>
                                    <span class="accordion__toggle-icon ">
                                        <svg class="" width="18" height="18" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                                            <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                                        </svg>
                                    </span>
                                </div>
                                <div class="accordion__menu collapse show" id="category">
                                    <div class="px-4">
                                        <hr>
                                        <div class="mb-4">
                                            @foreach(\App\Models\QuestionCategory::all() as $category)

                                            <div class="d-flex flex-column">
                                                <a href="{{ urldecode(route('discuss-all', ['cat['.$loop->iteration.']' => $category->title])) }}" class="my-2 font-size-16pt text-left">{{ $category->title }}</a>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="card card-body border-0 shadow-none">
                            <h5 class="mb-1"> کاربران برتر 30 روز قبل </h5>
                            <hr>
                            @include('discuss.component.top-users', [
                                ($numberOfDays = 30),
                                ($numberOfUsers = 15),
                            ])
                        </div>

                    </div>
                </div>
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
    <script type="text/javascript" src="/assets/js/manage-discuss.js"></script>
    {{--  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>  --}}
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script src="/assets/js/editor/easymde/config.js"></script>

    <script>
        $("#tags").select2({
            tags: true,
            maximumSelectionLength: 3,
            language: {
                // You can find all of the options in the language files provided in the
                // build. They all must be functions that return the string that should be
                // displayed.
                maximumSelected: function (e) {
                    var t = "حداکثر برچسب مجاز " + e.maximum + " مورد می باشد.";
                    return t ;
                }
            },
            placeholder: "با فشردن کلید enter برچسب ها را از هم جدا کنید"
        });

        //$("ul.select2-selection__rendered").sortable({
        //    containment: 'parent'
        //});
    </script>


    <script>
        $('#search-subject').focusin(function() {
            $("#search-result").show('slow')
        })
        $('#search-subject').focusout(function() {
            $("#search-result").hide('slow')
        })
        $(document).on('keyup', ('#search-subject'), function(event) {
            var key = $(this);

            $.ajax({
                url: "{{ route('discuss-search-question-create') }}",
                method: 'GET',
                data: {
                    keyword: key.val()
                },
                dataType: 'json',
                success: function(data) {
                    $("#search-result").html(data.html);
                }
            })

        });
    </script>


@endsection
