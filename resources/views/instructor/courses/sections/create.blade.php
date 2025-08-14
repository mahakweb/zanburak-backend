@extends('layouts.master')

@section('title', 'ایجاد بخش جدید')

@section('head')
    <link type="text/css" href="/assets/css/select2.rtl.css" rel="stylesheet">
    <link type="text/css" href="/assets/vendor/select2/select2.min.css" rel="stylesheet">
@endsection

@section('header')
    @include('instructor.layouts.header')
@endsection


@section('content')

    <div class="mdk-header-layout__content page-content ">
        <div class="page-section bg-alt border-bottom-2">
            <div class="container page__container">
                <div class="d-flex flex-column flex-lg-row align-items-center">
                    <div class="flex d-flex flex-column align-items-center align-items-lg-start mb-16pt mb-lg-0 text-center text-lg-left">
                        <h6 class="h2 mb-8pt">ایجاد بخش جدید </h6>
                        <div>
                        <span class="chip chip-outline-secondary d-inline-flex align-items-center" data-toggle="tooltip" data-title="Earnings" data-placement="bottom">
                        <i class="material-icons icon--left">trending_up</i> &dollar;12.3k
                        </span>
                            <span class="chip chip-outline-secondary d-inline-flex align-items-center" data-toggle="tooltip" data-title="Sales" data-placement="bottom">
                        <i class="material-icons icon--left">receipt</i> 264
                        </span>
                        </div>
                    </div>
                    <div class="ml-lg-16pt">
                        <a href="learnly-teacher-profile.html" class="btn btn-light">پروفایل من</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-section border-bottom-2">
            <div class="container page__container">
                <form action="{{ route('instructor-add-section', [$course->id]) }}"  method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">

                            <div class="flex mb-2" id="section-empty" style="max-width: 100%">
                                <div class="alert alert-warning mb-0" role="alert">
                                    <div class="d-flex flex-wrap align-items-start pt-1">
                                        <div class="mr-8pt">
                                            <i class="material-icons">movie</i>
                                        </div>
                                        <div class="flex" style="min-width: 180px">
                                            <h5>  ایجاد بخش جدید برای دوره:   <span class="font-fat text-underline">{{ $course->title }}</span></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="page-separator">
                                <div class="page-separator__text"> </div>
                            </div>
                            <label class="form-label">عنوان بخش</label>
                            <div class="form-group mb-24pt">
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="عنوان " value="{{ old('title') }}">
                                @error('title')
                                <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                                @enderror
                                <small class="form-text text-muted">لطفا  <a href="">راهنمای انتخاب عنوان </a> را مشاهده نمایید.</small>
                            </div>
                            <div class="form-group mb-32pt">
                                <label class="form-label">توضیحات</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="">{{ old('description') }}</textarea>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                                @enderror
                                <small class="form-text text-muted">این بخش را به اختصار توضیح دهید.</small>
                            </div>

                        </div>
                        <div class="col-md-4">
                            <div class="page-separator">
                                <div class="page-separator__text">فایل پیوست</div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <label class="form-label">آدرس</label>
                                    <div class="input-group">
                                        <span class="input-group-prepend"><span class="input-group-text"><i class="fa fa-file-archive"></i></span></span>
                                        <input type="text" id="attached-file" class="form-control @error('attached-file') is-invalid @enderror" name="attached-file"
                                               aria-label="Image" aria-describedby="button-image" value="{{ old('attached-file') }}">

                                        <div class="input-group-append" >
                                            <button class="btn btn-primary-yellow" type="button" id="button-attached-file"><i class="fa fa-paperclip"></i></button>
                                        </div>
                                        @error('attached-file')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <small class="form-text text-muted">یک URL معتبر وارد کنید.</small>
                                </div>
                            </div>

                            <div class="page-separator">
                                <div class="page-separator__text">وضعیت</div>
                            </div>
                            <div class="card">
                                <div class="card-body">

                                    <div class="form-group">
                                        <label class="form-label">وضعیت</label>
                                        <select name="status" id="select01" data-toggle="select" class="form-control @error('status') is-invalid @enderror" >
                                            @foreach(\App\Models\status::all() as $status)
                                                <option value="{{ $status->id }}" {{ old('status') == $status->id ? 'selected' : '' }}>{{ $status->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>

                                </div>
                            </div>

                            <div class="card">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex">
                                        <label class="form-label mb-0 flex" for="">وضعیت انتشار:</label>
                                        <div class="custom-control custom-checkbox-toggle custom-control-inline mr-1">
                                            <input name="publish" type="checkbox" id="publish" class="custom-control-input" {{ old('publish') == 'on' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="publish">Yes</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-accent w-100">
                                        ثبت <i class="material-icons icon--right">save</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection


@section('sidebar')
    @include('instructor.layouts.sidebar')
@endsection

@section('script')


    <script src="/assets/vendor/select2/select2.min.js"></script>
    <script src="/assets/js/select2.js"></script>
    <script src="https://cdn.tiny.cloud/1/qwrnnt8r9ugt6rhqx3i2479xw0lz4df6qfv4hjmzh8384a8g/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea',
            plugins: 'preview directionality fullscreen codesample a11ychecker advcode casechange export formatpainter image editimage linkchecker link lists checklist media mediaembed pageembed permanentpen powerpaste table advtable tableofcontents tinycomments tinymcespellchecker',
            // menubar: 'view',
            toolbar: 'preview ltr rtl align fullscreen codesample a11ycheck addcomment showcomments casechange checklist code export formatpainter image editimage pageembed permanentpen table tableofcontents',
            // toolbar_location: 'bottom',
            toolbar_mode: 'floating',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Author name',
            // skin: "oxide-dark",
            // content_css: "dark"
            file_picker_callback (callback, value, meta) {
                let x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth
                let y = window.innerHeight|| document.documentElement.clientHeight|| document.getElementsByTagName('body')[0].clientHeight

                tinymce.activeEditor.windowManager.openUrl({
                    url : '/file-manager/tinymce5',
                    title : 'Laravel File manager',
                    width : x * 0.8,
                    height : y * 0.8,
                    onMessage: (api, message) => {
                        callback(message.content, { text: message.text })
                    }
                })
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {


            document.getElementById('button-attached-file').addEventListener('click', (event) => {
                event.preventDefault();

                inputId = 'attached-file';

                window.open('/file-manager/fm-button', 'fm', 'width=1400,height=800');
            });

        });

        // input
        let inputId = '';

        // set file link
        function fmSetLink($url) {
            document.getElementById(inputId).value = $url;
        }
    </script>
@endsection
