@extends('admin.layouts.master')

@section('title', 'ویرایش جلسه: '.$episode->title)

@section('head')
    <link href="/admin/assets/plugins/custom/datatables/datatables.bundle.rtl.css" rel="stylesheet" type="text/css" />

    <!--start::file manager-->
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
    <!--end::file manager-->
@endsection

@section('content')
    <div class="d-flex flex-column flex-column-fluid">
        <!--begin::Toolbar-->
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <!--begin::Toolbar container-->
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">ویرایش جلسه: {{ $episode->title }}</h1>
                    <!--end::Title-->
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">
							<a href="{{ route('admin-index') }}" class="text-muted text-hover-primary">خانه</a>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">مدیریت جلسه ها</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">ویرایش جلسه </li>
						<!--end::Item-->
					</ul>
                    <!--end::Breadcrumb-->
                </div>
                <!--end::Page title-->
                <!--begin::Actions-->

                <!--end::Actions-->
            </div>
            <!--end::Toolbar container-->
        </div>
        <!--end::Toolbar-->
        <!--begin::Content-->
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <!--begin::Content container-->
            <div id="kt_app_content_container" class="app-container container-xxl">
                <!--begin::Form-->
                <form id="kt_courses_update_episode_form" action="{{ route('admin-episode-update', ['course' => $course, 'section' => $section, 'episode' => $episode]) }}" method="POST" enctype="multipart/form-data" class="form d-flex flex-column flex-lg-row" data-kt-redirect="{{ route('admin-course-sections', $course->id) }}">
                    @csrf
                    @method('patch')
                    <!--begin::Aside column-->
                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <!--begin::Video-->
                        <div class="card card-flush">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2 class="required">ویدیو جلسه</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="fv-row">
                                    <!--begin::Input-->
                                    <input type="text" name="video" class="select-url-input form-control mb-2" placeholder="ویدیو جلسه" value="{{ $episode->videos->where('type', 'stream')->pluck('path')[0] }}" />
                                    <!--end::Input-->
                                    <!--begin::Button-->
                                    <button class="select-url-btn btn btn-sm btn-light-primary mb-2">
                                        انتخاب
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="16" class="mr-2" viewBox="0 0 32 32" xml:space="preserve" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .puchipuchi_een{} </style> <path class="puchipuchi_een" d="M7,6c0-2.757,2.243-5,5-5s5,2.243,5,5c0,1.627-0.793,3.061-2,3.974V6c0-1.654-1.346-3-3-3 S9,4.346,9,6v3.974C7.793,9.061,7,7.627,7,6z M24,13c-1.104,0-2,0.896-2,2v-1c0-1.104-0.896-2-2-2s-2,0.896-2,2v-1 c0-1.104-0.896-2-2-2s-2,0.896-2,2V6c0-1.104-0.896-2-2-2s-2,0.896-2,2v10.277C9.705,16.106,9.366,16,9,16c-1.104,0-2,0.896-2,2v3 c0,0.454,0.155,0.895,0.438,1.249L11,28h12l2.293-3.293C25.682,24.318,26,23.55,26,23v-8C26,13.896,25.104,13,24,13z M11,29v1 c0,0.552,0.447,1,1,1h10c0.553,0,1-0.448,1-1v-1H11z"></path> </g></svg>
                                    </button>
                                    <!--end::Button-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">لطفا ویدیو جلسه را وارد کنید.</div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text video_error"><div data-field="video"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <div class="separator separator-dashed my-3"></div>
                                <!--begin::Input group-->
                                <div class="fv-row">
                                    <!--begin::Input-->
                                    <input type="text" name="download_video" class="select-url-input form-control mb-2" placeholder="آدرس ویدیوی جلسه برای دانلود" value="{{ $episode->videos->where('type', 'download')->pluck('path')[0] }}" />
                                    <!--end::Input-->
                                    <!--begin::Button-->
                                    <button class="select-url-btn btn btn-sm btn-light-primary mb-2">
                                        انتخاب
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="16" class="mr-2" viewBox="0 0 32 32" xml:space="preserve" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .puchipuchi_een{} </style> <path class="puchipuchi_een" d="M7,6c0-2.757,2.243-5,5-5s5,2.243,5,5c0,1.627-0.793,3.061-2,3.974V6c0-1.654-1.346-3-3-3 S9,4.346,9,6v3.974C7.793,9.061,7,7.627,7,6z M24,13c-1.104,0-2,0.896-2,2v-1c0-1.104-0.896-2-2-2s-2,0.896-2,2v-1 c0-1.104-0.896-2-2-2s-2,0.896-2,2V6c0-1.104-0.896-2-2-2s-2,0.896-2,2v10.277C9.705,16.106,9.366,16,9,16c-1.104,0-2,0.896-2,2v3 c0,0.454,0.155,0.895,0.438,1.249L11,28h12l2.293-3.293C25.682,24.318,26,23.55,26,23v-8C26,13.896,25.104,13,24,13z M11,29v1 c0,0.552,0.447,1,1,1h10c0.553,0,1-0.448,1-1v-1H11z"></path> </g></svg>
                                    </button>
                                    <!--end::Button-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">لطفا آدرس دانلود ویدیو را انتخاب کنید.</div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text download_video_error"><div data-field="download_video"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <div class="separator separator-dashed my-3"></div>
                                <!--Start::Disk-->
                                <!--begin::Label-->
                                <label class=" form-label">دیسک</label>
                                <!--end::Label-->
                                <!--begin::Select2-->
                                <select name="disk" class="form-select mb-2" data-control="select2" data-hide-search="true" data-placeholder="انتخاب کنید" id="kt_courses_add_course_disk_select">
                                    @foreach (config('file-manager.diskList') as $disk)
                                        <option @if($disk == $episode->videos->where('type', 'stream')->pluck('disk')[0]) selected="selected" @endif value="{{ $disk }}">{{ $disk }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">دیسک حاوی ویدیوها را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text disk_error"><div data-field="disk"></div></div>
                                <!--end::Description-->

                                <!--End::Disk-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Video-->
                        <!--begin::Video_time-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">زمان ویدیو</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="fv-row">
                                    <!--begin::Input-->
                                    <input type="number" min="0" name="total_time" class="form-control mb-2" value="{{ $episode->total_time }}" />
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">زمان ویدیو را برحسب ثانیه وارد کنید.</div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text total_time_error"><div data-field="total_time"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Video_time-->
                        @if ($course->type != 'free')
                        <!--begin::Lock-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">نوع جلسه</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input name="lock" class="form-check-input" type="checkbox" value="1" {{ $episode->lock == 1 ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-muted">قفل کردن جلسه</span>
                                </label>
                                <!--begin::Description-->
                                <div class="mt-4 fv-plugins-message-container invalid-feedback error_text lock_error"><div data-field="lock"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Lock-->
                        @endif

                    </div>
                    <!--end::Aside column-->
                    <!--begin::Main column-->
                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <!--begin::General options-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>ویژگی های پایه</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="required form-label">عنوان فارسی</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="title" class="form-control mb-2" placeholder="عنوان فارسی جلسه" value="{{ $episode->title }}" />
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">لطفا یک نام کوتاه انتخاب کنید.</div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text title_error"><div data-field="title"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="required form-label">عنوان لاتین</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="english_title" class="form-control mb-2" placeholder="عنوان لاتین جلسه" value="{{ $episode->english_title }}" />
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">لطفا یک نام کوتاه انتخاب کنید.</div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text english_title_error"><div data-field="english_title"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div>
                                    <!--begin::Label-->
                                    <label class="required form-label">توضیحات</label>
                                    <!--end::Label-->
                                    <!--begin::Editor-->
                                    {{--  <div id="kt_courses_add_course_description" class="min-h-200px mb-2"></div>  --}}
                                    <textarea name="description" rows="7" class="form-control mb-2">{!!$episode->description !!}</textarea>
                                    <!--end::Editor-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">لطفا توضیحات جلسه را وارد کنید.</div>

                                    <div class="fv-plugins-message-container invalid-feedback error_text description_error"><div data-field="description"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::General options-->
                        <!--begin::Media-->

                        <!--end::Media-->
                        <!--begin::Publish-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2 class="required">وضعیت انتشار</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">

                                    <!--begin::Row-->
                                    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-1 row-cols-xl-3 g-9" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary {{ $episode->publish == 1 ? 'active' : '' }} d-flex text-start p-6" data-kt-button="true">
                                                <!--begin::Radio-->
                                                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                    <input class="form-check-input" type="radio" name="publish" value="1" {{ $episode->publish == 1 ? 'checked' : '' }} />
                                                </span>
                                                <!--end::Radio-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
                                                    <span class="fs-4 fw-bold text-gray-800 d-block">انتشار دادن</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary {{ $episode->publish == 0 ? 'active' : '' }} d-flex text-start p-6" data-kt-button="true">
                                                <!--begin::Radio-->
                                                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                    <input class="form-check-input" type="radio" name="publish" value="0" {{ $episode->publish == 0 ? 'checked' : '' }} />
                                                </span>
                                                <!--end::Radio-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
                                                    <span class="fs-4 fw-bold text-gray-800 d-block">فعلا منتشر نشود</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text publish_error"><div data-field="publish"></div></div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Publish-->

                        <!--begin::Attachments-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>پیوست‌ها</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="">
                                    <!--begin::Repeater-->
                                    <div id="kt_episode_add_attach">
                                        <!--begin::Form group-->
                                        <div class="form-group">
                                            <div data-repeater-list="attach" class="d-flex flex-column gap-6">
                                                @forelse ($episode->attachs as $attach)
                                                    <div data-repeater-item="" class="form-group d-flex flex-wrap align-items-center gap-1">
                                                        <!--begin::Input-->
                                                        <input type="text" class="form-control mw-100 w-200px" name="attach_title" placeholder="عنوان" value="{{ $attach->title }}"/>
                                                        <!--end::Input-->
                                                        <!--begin::Input-->
                                                        <input type="text" class="select-url-input form-control mw-100 w-200px" name="attach_url" placeholder="آدرس فایل" value="{{ $attach->url }}"/>
                                                        <!--end::Input-->
                                                        <!--begin::Button-->
                                                        <button class="select-url-btn btn btn-sm btn-light-primary">
                                                            انتخاب
                                                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="16" class="mr-2" viewBox="0 0 32 32" xml:space="preserve" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .puchipuchi_een{} </style> <path class="puchipuchi_een" d="M7,6c0-2.757,2.243-5,5-5s5,2.243,5,5c0,1.627-0.793,3.061-2,3.974V6c0-1.654-1.346-3-3-3 S9,4.346,9,6v3.974C7.793,9.061,7,7.627,7,6z M24,13c-1.104,0-2,0.896-2,2v-1c0-1.104-0.896-2-2-2s-2,0.896-2,2v-1 c0-1.104-0.896-2-2-2s-2,0.896-2,2V6c0-1.104-0.896-2-2-2s-2,0.896-2,2v10.277C9.705,16.106,9.366,16,9,16c-1.104,0-2,0.896-2,2v3 c0,0.454,0.155,0.895,0.438,1.249L11,28h12l2.293-3.293C25.682,24.318,26,23.55,26,23v-8C26,13.896,25.104,13,24,13z M11,29v1 c0,0.552,0.447,1,1,1h10c0.553,0,1-0.448,1-1v-1H11z"></path> </g></svg>
                                                        </button>
                                                        <!--end::Button-->
                                                        <button type="button" data-repeater-delete="" class="btn btn-sm btn-icon btn-light-danger">
                                                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr088.svg-->
                                                            <span class="svg-icon svg-icon-1">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <rect opacity="0.5" x="7.05025" y="15.5356" width="12" height="2" rx="1" transform="rotate(-45 7.05025 15.5356)" fill="currentColor" />
                                                                    <rect x="8.46447" y="7.05029" width="12" height="2" rx="1" transform="rotate(45 8.46447 7.05029)" fill="currentColor" />
                                                                </svg>
                                                            </span>
                                                            <!--end::Svg Icon-->
                                                        </button>
                                                    </div>

                                                @empty

                                                    <div data-repeater-item="" class="form-group d-flex flex-wrap align-items-center gap-1">
                                                        <!--begin::Input-->
                                                        <input type="text" class="form-control mw-100 w-200px" name="attach_title" placeholder="عنوان" />
                                                        <!--end::Input-->
                                                        <!--begin::Input-->
                                                        <input type="text" class="select-url-input form-control mw-100 w-200px" name="attach_url" placeholder="آدرس فایل" />
                                                        <!--end::Input-->
                                                        <!--begin::Button-->
                                                        <button class="select-url-btn btn btn-sm btn-light-primary">
                                                            انتخاب
                                                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="16" class="mr-2" viewBox="0 0 32 32" xml:space="preserve" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .puchipuchi_een{} </style> <path class="puchipuchi_een" d="M7,6c0-2.757,2.243-5,5-5s5,2.243,5,5c0,1.627-0.793,3.061-2,3.974V6c0-1.654-1.346-3-3-3 S9,4.346,9,6v3.974C7.793,9.061,7,7.627,7,6z M24,13c-1.104,0-2,0.896-2,2v-1c0-1.104-0.896-2-2-2s-2,0.896-2,2v-1 c0-1.104-0.896-2-2-2s-2,0.896-2,2V6c0-1.104-0.896-2-2-2s-2,0.896-2,2v10.277C9.705,16.106,9.366,16,9,16c-1.104,0-2,0.896-2,2v3 c0,0.454,0.155,0.895,0.438,1.249L11,28h12l2.293-3.293C25.682,24.318,26,23.55,26,23v-8C26,13.896,25.104,13,24,13z M11,29v1 c0,0.552,0.447,1,1,1h10c0.553,0,1-0.448,1-1v-1H11z"></path> </g></svg>
                                                        </button>
                                                        <!--end::Button-->
                                                        <button type="button" data-repeater-delete="" class="btn btn-sm btn-icon btn-light-danger">
                                                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr088.svg-->
                                                            <span class="svg-icon svg-icon-1">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <rect opacity="0.5" x="7.05025" y="15.5356" width="12" height="2" rx="1" transform="rotate(-45 7.05025 15.5356)" fill="currentColor" />
                                                                    <rect x="8.46447" y="7.05029" width="12" height="2" rx="1" transform="rotate(45 8.46447 7.05029)" fill="currentColor" />
                                                                </svg>
                                                            </span>
                                                            <!--end::Svg Icon-->
                                                        </button>
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <!--end::Form group-->
                                        <!--begin::Form group-->
                                        <div class="form-group mt-5">
                                            <button type="button" data-repeater-create="" class="btn btn-sm btn-light-primary">
                                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr087.svg-->
                                                <span class="svg-icon svg-icon-2">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect opacity="0.5" x="11" y="18" width="12" height="2" rx="1" transform="rotate(-90 11 18)" fill="currentColor" />
                                                        <rect x="6" y="11" width="12" height="2" rx="1" fill="currentColor" />
                                                    </svg>
                                                </span>
                                                <!--end::Svg Icon-->
                                                ویرایش سطر
                                            </button>
                                        </div>
                                        <!--end::Form group-->
                                    </div>
                                    <!--end::Repeater-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::Attachments-->



                        <div class="d-flex justify-content-end">
                            <!--begin::Button-->
                            <a href="javascript:history.back()" id="kt_courses_add_episode_cancel" class="btn btn-light me-5">لغو</a>
                            <!--end::Button-->
                            <!--begin::Button-->
                            <button type="submit" id="kt_courses_update_episode_submit" class="btn btn-primary">
                                <span class="indicator-label">ثبت</span>
                                <span class="indicator-progress">لطفا منتظر بمانید...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                            <!--end::Button-->
                        </div>
                    </div>
                    <!--end::Main column-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Content container-->
        </div>
        <!--end::Content-->
    </div>
@endsection


@section('vendors-script')
    <script src="/admin/assets/plugins/custom/datatables/datatables.bundle.js"></script>
	<script src="/admin/assets/plugins/custom/formrepeater/formrepeater.bundle.js"></script>

@endsection

@section('custom-script')
    <script src="/admin/assets/js/custom/apps/courses/episodes/edit-episode.js"></script>
    <script src="/admin/assets/js/widgets.bundle.js"></script>
    <script src="/admin/assets/js/custom/widgets.js"></script>


    <!--start::file manager-->
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
    <script>
        let input = '';
        $(document).ready(function(){
            $(document).on('click', '.select-url-btn', function(e){
                e.preventDefault();
                input = $(this).closest('div').find('.select-url-input')
                window.open('/file-manager/fm-button', 'fm', 'width=800,height=400');
            })
        })


        // set file link
        function fmSetLink($url) {
            input.val($url);
        }
    </script>
    <!--end::file manager-->
@endsection
