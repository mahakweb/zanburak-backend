@extends('layouts.master')
@section('title', 'ویرایش دوره')
@section('head')
    <!-- Quill Theme -->
    <link type="text/css" href="/assets/css/quill.rtl.css" rel="stylesheet">
    <!-- Select2 -->
    <link type="text/css" href="/assets/css/select2.rtl.css" rel="stylesheet">
    <link type="text/css" href="/assets/vendor/select2/select2.min.css" rel="stylesheet">
    <link type="text/css" href="https://cdn.plyr.io/3.5.6/plyr.css" rel="stylesheet">
    <link type="text/css" href="/assets/css/player/player.css" rel="stylesheet">
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
                    <div class="flex d-flex flex-column align-items-center align-items-lg-start mb-16pt mb-lg-0 text-center text-lg-left">
                        <h1 class="h2 mb-8pt">ویرایش دوره</h1>
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
                <form action="{{ route('instructor-edit-course', $course->id) }}" id="course-upload" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <div class="row">
                        <div class="col-md-8">
                            <div class="page-separator">
                                <div class="page-separator__text">اطلاعات اولیه</div>
                            </div>
                            <label class="form-label">عنوان دوره</label>
                            <div class="form-group mb-24pt">
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="عنوان دوره" value="{{ (old('title')) ? old('title') : $course->title }}">
                                @error('title')
                                <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                                @enderror
                                <small class="form-text text-muted">لطفا  <a href="">راهنمای انتخاب عنوان </a> را مشاهده نمایید.</small>
                            </div>
                            <div class="form-group mb-32pt">
                                <label class="form-label">توضیحات</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Course description">{{ (old('description')) ? old('description') : $course->description }}</textarea>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                            </span>
                                @enderror
                                <small class="form-text text-muted">این دوره را به اختصار توضیح دهید.</small>
                            </div>
                            <div class="page-separator">
                                <div class="page-separator__text">تریلر</div>
                            </div>
                            <div class="card">
                                {{--
                                <div class="">
                                    --}}
                                {{--
                                <video  controls crossorigin playsinline data-poster="/assets/images/stories/256_rsz_phil-hearing-769014-unsplash.jpg" class="js-player">
                                    --}}
                                {{--                                        <!-- Video files -->--}}
                                {{--                                        --}}
                                {{--
                                <source  src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-576p.mp4" type="video/mp4" size="576" />
                                --}}
                                {{--                                        --}}
                                {{--                                        --}}
                                {{--
                                <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4" type="video/mp4" size="720" />
                                --}}
                                {{--                                        --}}
                                {{--                                        --}}
                                {{--
                                <source src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-1080p.mp4" type="video/mp4" size="1080" />
                                --}}
                                {{--                                        --}}
                                {{--                                        <!-- Caption files -->--}}
                                {{--                                        --}}
                                {{--
                                <track kind="captions" label="English" srclang="en" src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.en.vtt" default>
                                --}}
                                {{--                                        --}}
                                {{--                                        --}}
                                {{--
                                <track kind="captions" label="Français" srclang="fr" src="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-HD.fr.vtt">
                                --}}
                                {{--                                        --}}
                                {{--                                        <!-- Fallback for browsers that don't support the <video> element -->--}}
                                {{--                                                                                                                    <a href="https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-576p.mp4" download>Download</a>--}}
                                {{--
                            </video>
                            --}}
                                {{--
                            </div>
                            --}}
                                <div class="card-body">
                                    <label class="form-label">آدرس</label>
                                    <div class="input-group">
                                        <input type="text" id="trailer-video" class="form-control @error('trailer-video') is-invalid @enderror" name="trailer-video"
                                               aria-label="Image" aria-describedby="button-image" value="{{ (old('trailer-video')) ? old('trailer-video') : $course->trailer }}">
                                        <div class="input-group-append" >
                                            <button class="btn btn-primary-yellow" type="button" id="button-trailer"><i class="material-icons">attachment</i></button>
                                        </div>
                                        @error('trailer-video')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">یک URL معتبر وارد کنید.</small>
                                </div>
                            </div>
                            <div class="page-separator">
                                <div class="page-separator__text">فایل پیوست</div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <label class="form-label">آدرس</label>
                                    <div class="input-group">
                                        <input type="text" id="attached-file" class="form-control @error('attached-file') is-invalid @enderror" name="attached-file"
                                               aria-label="Image" aria-describedby="button-image" value="{{ (old('attached-file')) ? old('attached-file') : $course->attached_file }}">
                                        <div class="input-group-append" >
                                            <button class="btn btn-primary-yellow" type="button" id="button-attached-file"><i class="material-icons">attachment</i></button>
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
                        </div>
                        <div class="col-md-4">
                            <div class="page-separator">
                                <div class="page-separator__text">خصوصیات</div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="form-label">دسته بندی</label>
                                        <select name="category[]" multiple id="select04" data-toggle="select" class="form-control @error('category') is-invalid @enderror">
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" @if(collect(old('category'))->contains($category->id)) selected @elseif(in_array($category->id, $course_category)) selected @endif >{{ $category->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                    <div class="page-separator"></div>
                                    <div class="form-group">
                                        <label class="form-label">وضعیت</label>
                                        <select name="status" id="select01" data-toggle="select" class="form-control @error('status') is-invalid @enderror" >
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->id }}" @if(old('status') == $status->id) selected @elseif($course->status_id == $status->id) selected @endif>{{ $status->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                    <div class="page-separator"></div>
                                    <div class="form-group">
                                        <label class="form-label">سطح دوره</label>
                                        <select name="level" id="select02" data-toggle="select" class="form-control @error('level') is-invalid @enderror">
                                            @foreach($levels as $level)
                                                <option value="{{ $level->id }}" @if(old('level') == $level->id) selected @elseif($course->level_id == $level->id) selected @endif>{{ $level->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('level')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                    <div class="page-separator"></div>
                                    <div class="form-group">
                                        <label class="form-label">قیمت</label>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group form-inline">
                                                    <span class="input-group-prepend"><span class="input-group-text"><i class="material-icons icon-16pt">payment</i></span></span>
                                                    <input name="price" {{ old('free-price') == 'on' ? 'readonly' : '' }} id="price" type="text" value="{{ old('price') ? old('price') : $course->price }}" class="form-control @error('price') is-invalid @enderror" placeholder="قیمت: 4,250,000" data-mask="#,###,###" data-mask-reverse="true">
                                                    @error('price')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card-body">
                                                    <div class="list-group list-group-flush">
                                                        <div class="list-group-item d-flex">
                                                            <label class="form-label mb-0 flex" for="">رایگان:</label>
                                                            <div class="custom-control custom-checkbox-toggle custom-control-inline mr-1">
                                                                <input name="free-price" {{ old('free-price') == 'on' ? 'checked' : '' }} type="checkbox" id="free-price" class="custom-control-input">
                                                                <label class="custom-control-label" for="free-price">Yes</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="page-separator"></div>
                                    <div class="form-group mb-0">
                                        <label class="form-label" for="select03">برچسب ها</label>
                                        <select name="tags[]" multiple id="select03" data-toggle="select"  class="form-control @error('tags') is-invalid @enderror">
                                            @foreach($tags as $tag)
                                                <option value="{{ $tag->tag_id }}"@if(collect(old('tags'))->contains($tag->tag_id)) selected @elseif(in_array($tag->name, $course_tag)) selected @endif>{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('tags')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="page-separator">
                                <div class="page-separator__text">پوستر</div>
                            </div>
                            <div class="card">
                                {{--
                                <div class="">
                                    --}}
                                {{--
                                <div id="poster-preview" style="width: 100%; height: 150px; position: relative;background-image: url(/assets/images/other/no-image.jpg);">--}}
                                {{--
                            </div>
                            --}}
                                {{--
                            </div>
                            --}}
                                <div class="card-body">
                                    <label class="form-label">آدرس</label>
                                    <div class="input-group">
                                        <input type="text" id="poster" class="form-control @error('poster') is-invalid @enderror" name="poster"
                                               aria-label="Image" aria-describedby="button-image" value="{{ (old('poster')) ? old('poster') : $course->poster }}">
                                        <div class="input-group-append" >
                                            <button class="btn btn-primary-yellow" type="button" id="button-poster"><i class="material-icons">attachment</i></button>
                                        </div>
                                        @error('poster')
                                        <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">یک URL معتبر وارد کنید.</small>
                                </div>
                            </div>
                            <div class="card">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex">
                                        <label class="form-label mb-0 flex" for="">وضعیت انتشار دوره:</label>
                                        <div class="custom-control custom-checkbox-toggle custom-control-inline mr-1">
                                            <input name="publish" type="checkbox" id="publish" class="custom-control-input" @if(old('publish') == 'on') checked @elseif($course->publish == 1) checked @endif>
                                            <label class="custom-control-label" for="publish">Yes</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-accent">
                                        ویرایش دوره<i class="material-icons icon--right">edit</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-8">
                        <div class="page-separator">
                            <div class="page-separator__text">بخش ها</div>
                        </div>
                        <div class="accordion js-accordion accordion--boxed mb-24pt" id="parent">
                            @if($sections->isEmpty())
                                <div class="flex mb-2" id="section-empty" style="max-width: 100%">
                                    <div class="alert alert-warning mb-0" role="alert">
                                        <div class="d-flex flex-wrap align-items-start pt-1">
                                            <div class="mr-8pt">
                                                <i class="material-icons">access_time</i>
                                            </div>
                                            <div class="flex" style="min-width: 180px">
                                                <h5>هیچ بخشی برای این دوره آموزشی موجود نیست.</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @foreach($sections as $section)
                                <div id="new-section" class="accordion__item">
                                    <div class="accordion__toggle collapsed flex" style="cursor: pointer" data-toggle="collapse" data-target="#course-toc-{{ $loop->iteration }}" data-parent="#parent">
                                        <span class="flex" >{{ $section->title }}</span>
                                        <span class="accordion__toggle-icon"><i class="material-icons">keyboard_arrow_down</i></span>
                                        <a href="{{ route('instructor-edit-section', [$section->course_id, $section->id]) }}" title="ویرایش بخش" class="btn btn-sm btn-outline-warning mr-1"><i class="material-icons">border_color</i></a>
                                        <form id="delete-section" class="mr-1" action="{{ route('instructor-delete-section', [$course->id, $section->id]) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"  class="btn btn-sm btn-outline-danger" title="حذف بخش"><i class="material-icons">delete</i></button>
                                        </form>
                                        <a href="{{ route('instructor-add-episode', [$section->course_id, $section->id]) }}" title="افزودن قسمت جدید" class="btn btn-sm btn-secondary mr-1"><i class="material-icons">add</i></a>
                                    </div>
                                    <div class="accordion__menu collapse" id="course-toc-{{ $loop->iteration }}">
                                        @php
                                            $episodes = $section->episode()->get();
                                        @endphp
                                        @foreach($episodes as $episode)
                                            <div id="new-episode" class="accordion__menu-link">
                                                <i class="material-icons text-70 icon-16pt icon--left">drag_handle</i>
                                                <a class="flex" href="learnly-student-lesson.html">{{ $episode->title }}</a>
{{--                                                <span class="text-muted">{{ floor($episode->total_time / 60).'m' }}&nbsp; {{ ($episode->total_time % 60).'s' }}</span>--}}
                                                <span class="text-muted">{{ $episode->total_time.' دقیقه ' }}</span>
                                                <a href="{{ route('instructor-edit-episode', [$course->id, $section->id, $episode->id]) }}" class="btn btn-sm btn-outline-secondary ml-2"> ویرایش <i class="material-icons">border_color</i></a>
                                                <form id="delete-episode" class="ml-1" action="{{ route('instructor-delete-episode', [$course->id, $section->id, $episode->id]) }}" method="post">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"  class="btn btn-sm btn-outline-danger" title="حذف قسمت"><i class="material-icons">delete</i></button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <form action="{{ route('instructor-add-section', [$course->id]) }}" id="add-new-section" method="post">
                            <div class="page-separator">
                                <div class="page-separator__text">فرم ایجاد بخش جدید</div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label">عنوان بخش</label>
                                    <div class="form-group mb-24pt">
                                        <input type="text" required name="create-section-title" id="create-section-title" class="form-control" placeholder="عنوان بخش" value="">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">وضعیت بخش</label>
                                        <select id="create-section-status" name="create-section-status" data-toggle="select" class="form-control" >
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->id }}">{{ $status->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary mb-24pt mb-sm-0">ایجاد</button>
                            {{--                            <a  type="button" id="add-section" class="btn btn-outline-secondary mb-24pt mb-sm-0">ایجاد</a>--}}
                        </form>
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
    <!-- Quill -->
    <script src="/assets/vendor/quill.min.js"></script>
    <script src="/assets/js/quill.js"></script>
    <!-- Select2 -->
    <script src="/assets/vendor/select2/select2.min.js"></script>
    <script src="/assets/js/select2.js"></script>
    {{--    <script src="/assets/js/create-new-section.js"></script>--}}
    <script src="/assets/js/instructor-section.js"></script>
    <script src="/assets/js/instructor-episode.js"></script>
    <script src="https://cdn.plyr.io/3.5.6/plyr.js"></script>
    <script src="/assets/js/player/player.js"></script>
    <script src="/assets/js/player/preview-video.js"></script>
    <script src="/assets/js/file-upload-preview.js"></script>
    <script src="/assets/vendor/jquery.mask.min.js"></script>
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
        $.fn.hasAttr = function(name) {
            return this.attr(name) !== undefined;
        };

        $('#free-price').click(function (){
            if($('#price').hasAttr('readonly')){
                $('#price').removeAttr('readonly');
            }else{
                $('#price').attr('readonly', 'readonly');
                $('#price').attr('value', "0");
                $('#price').val("0");
            }
        })

    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.getElementById('button-poster').addEventListener('click', (event) => {
                event.preventDefault();

                inputId = 'poster';

                window.open('/file-manager/fm-button', 'fm', 'width=1400,height=800');
            });

            // second button
            document.getElementById('button-trailer').addEventListener('click', (event) => {
                event.preventDefault();

                inputId = 'trailer-video';

                window.open('/file-manager/fm-button', 'fm', 'width=1400,height=800');
            });

            // Third button
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
