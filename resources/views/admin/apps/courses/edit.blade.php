@extends('admin.layouts.master')

@section('title', 'ویرایش دوره: '.$course->title)

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
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">ویرایش دوره: {{ $course->title }}</h1>
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
						<li class="breadcrumb-item text-muted">مدیریت دوره ها</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">ویرایش دوره</li>
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
                <form id="kt_courses_update_course_form" action="{{ route('admin-edit-course', $course) }}" method="POST" enctype="multipart/form-data" class="form d-flex flex-column flex-lg-row" data-kt-redirect="{{ route('admin-courses-list') }}">
                    @csrf
                    @method('patch')
                    <!--begin::Aside column-->
                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <!--begin::poster settings-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">پوستر</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body text-center pt-0">
                                <!--begin::Image input-->
                                <!--begin::Image input placeholder-->
                                <style>.image-input-placeholder { background-image: url('{{ $course->poster }}'); } [data-theme="dark"] .image-input-placeholder { background-image: url('{{ $course->poster }}'); }</style>
                                <!--end::Image input placeholder-->
                                <div id="poster-placeholder" class="image-input image-input-empty image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                                    <!--begin::Preview existing avatar-->
                                    <div class="image-input-wrapper w-150px h-150px"></div>
                                    <!--end::Preview existing avatar-->
                                    <!--begin::Label-->
                                    <label id="select-poster" class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="تغییر">
                                        <i class="bi bi-pencil-fill fs-7"></i>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <!--end::Image input-->
                                <input type="text" class="form-control mb-2" id="poster" name="poster" value="{{ $course->poster }}" />

                                <!--begin::Description-->
                                <div class="text-muted fs-7">فرمت های مورد قبول: png  jpg  jpeg</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text poster_error"><div data-field="poster"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::poster settings-->
                        <!--begin::Status-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">وضعیت</h2>
                                </div>
                                <!--end::Card title-->
                                <!--begin::Card toolbar-->

                                <!--begin::Card toolbar-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Select2-->
                                <select name="status_id" class="form-select mb-2" data-control="select2" data-hide-search="true" data-placeholder="انتخاب کنید" id="kt_courses_update_course_status_select">
                                    <option></option>
                                    @foreach (App\Models\Status::all() as $status)
                                        <option value="{{ $status->id }}" @if($status->id == $course->status->id) selected="selected" @endif>{{ $status->title }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">وضعیت دوره را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text status_id_error"><div data-field="status_id"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Status-->
                        <!--begin::level-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">سطح </h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Select2-->
                                <select name="level_id" class="form-select mb-2" data-control="select2" data-hide-search="true" data-placeholder="انتخاب کنید" id="kt_courses_update_course_level_select">
                                    <option></option>
                                    @foreach (App\Models\Level::all() as $level)
                                        <option value="{{ $level->id }}" @if($level->id == $course->level->id) selected="selected" @endif>{{ $level->title }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">سطح دوره را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text level_id_error"><div data-field="level_id"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::level-->
                        <!--begin::type-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">نوع دوره</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Select2-->
                                <select name="type" class="form-select mb-2" data-control="select2" data-hide-search="true" data-placeholder="انتخاب کنید" id="kt_courses_update_course_type_select">
                                    <option></option>
                                    <option value="free" @if($course->type == 'free') selected="selected" @endif>رایگان</option>
                                    <option value="cash" @if($course->type == 'cash') selected="selected" @endif>نقدی</option>
                                    <option value="cash-vip" @if($course->type == 'cash-vip') selected="selected" @endif>نقدی و اعضای ویژه</option>
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">نوع دوره را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text type_error"><div data-field="type"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::type-->
                        <!--begin::Category & tags-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>جزییات دوره</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <!--begin::Label-->
                                <label class="required form-label">دسته بندی</label>
                                <!--end::Label-->
                                <!--begin::Select2-->
                                <select name="category[]" class="form-select mb-2" data-control="select2" data-placeholder="انتخاب کنید" data-allow-clear="true" multiple="multiple">
                                    <option></option>
                                    @foreach (App\Models\Category::all() as $category)
                                        <option value="{{ $category->id }}" @if(in_array($category->id, $course->category->pluck('id')->toArray())) selected="selected" @endif>{{ $category->title }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">دسته بندی دوره را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container mb-7 invalid-feedback error_text category_error"><div data-field="category"></div></div>
                                <!--end::Description-->
                                <!--end::Input group-->
                                <!--begin::Button-->
                                <a href="{{ route('admin-create-category') }}" target="_blank" class="btn btn-light-primary btn-sm mb-10">
                                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr087.svg-->
                                    <span class="svg-icon svg-icon-2">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect opacity="0.5" x="11" y="18" width="12" height="2" rx="1" transform="rotate(-90 11 18)" fill="currentColor" />
                                            <rect x="6" y="11" width="12" height="2" rx="1" fill="currentColor" />
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    ایجاد دسته بندی جدید
                                </a>
                                <!--end::Button-->
                                <!--begin::Input group-->
                                <!--begin::Label-->
                                <label class="required form-label d-block">برچسب</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input id="kt_courses_update_course_tags" name="tags" placeholder="" class="form-control mb-2" value="{{ $course->tagList }}" />
                                <!--end::Input-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">برچسب(های) دوره را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text tags_error"><div data-field="tags"></div></div>
                                <!--end::Description-->
                                <!--end::Input group-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Category & tags-->
                    </div>
                    <!--end::Aside column-->
                    <!--begin::Main column-->
                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <!--begin:::Tabs-->
                        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-n2">
                            <!--begin:::Tab item-->
                            <li class="nav-item">
                                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_courses_update_course_general">پایه</a>
                            </li>
                            <!--end:::Tab item-->
                            <!--begin:::Tab item-->
                            <li class="nav-item">
                                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_courses_update_course_advanced">بیشتر</a>
                            </li>
                            <!--end:::Tab item-->
                        </ul>
                        <!--end:::Tabs-->
                        <!--begin::Tab content-->
                        <div class="tab-content">
                            <!--begin::Tab pane-->
                            <div class="tab-pane fade show active" id="kt_courses_update_course_general" role="tab-panel">
                                <div class="d-flex flex-column gap-7 gap-lg-10">
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
                                                <input type="text" name="title" class="form-control mb-2" placeholder="عنوان فارسی دوره" value="{{ $course->title }}" />
                                                <!--end::Input-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا یک نام یکتا و کوتاه انتخاب کنید.</div>
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
                                                <input type="text" name="english_title" class="form-control mb-2" placeholder="عنوان لاتین دوره" value="{{ $course->english_title }}" />
                                                <!--end::Input-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا یک نام یکتا و کوتاه انتخاب کنید.</div>
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
                                                {{--  <div id="kt_courses_update_course_description" class="min-h-200px mb-2"></div>  --}}
                                                <textarea name="description" rows="7" class="form-control mb-2">{!! $course->description !!}</textarea>
                                                <!--end::Editor-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا توضیحات دوره را وارد کنید.</div>

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
                                                <h2>وضعیت انتشار</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->
                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            <!--begin::Input group-->
                                            <div class="fv-row mb-10">
                                                <!--begin::Label-->
                                                <label class="fs-6 fw-semibold mb-2 required">وضعیت انتشار
                                                <i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="وضعیت دوره را برای انتشار دادن یا ندادن مشخص کنید"></i></label>
                                                <!--End::Label-->
                                                <!--begin::Row-->
                                                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-1 row-cols-xl-3 g-9" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                                                    <!--begin::Col-->
                                                    <div class="col">
                                                        <!--begin::Option-->
                                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary {{ $course->publish == 1 ? 'active' : ''  }} d-flex text-start p-6" data-kt-button="true">
                                                            <!--begin::Radio-->
                                                            <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                                <input class="form-check-input" type="radio" name="publish" value="1" {{ $course->publish == 1 ? 'checked' : ''  }} />
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
                                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary {{ $course->publish == 0 ? 'active' : ''  }} d-flex text-start p-6" data-kt-button="true">
                                                            <!--begin::Radio-->
                                                            <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                                <input class="form-check-input" type="radio" name="publish" value="0" {{ $course->publish == 0 ? 'checked' : ''  }} />
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
                                    <!--begin::Pricing-->
                                    <div class="card card-flush py-4">
                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>قیمت گذاری</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->
                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            <!--begin::Input group-->
                                            <div class="mb-10 fv-row">
                                                <!--begin::Label-->
                                                <label class="form-label">قیمت</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input {{ $course->type == 'free' ? 'disabled' : ''  }} type="tel" name="price" class="form-control mb-2" placeholder="0" value="{{ $course->price }}" />
                                                <!--end::Input-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا قیمت دوره را وارد کنید.</div>
                                                <div class="fv-plugins-message-container invalid-feedback error_text price_error"><div data-field="price"></div></div>
                                                <!--end::Description-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Pricing-->
                                </div>
                            </div>
                            <!--end::Tab pane-->
                            <!--begin::Tab pane-->
                            <div class="tab-pane fade" id="kt_courses_update_course_advanced" role="tab-panel">
                                <div class="d-flex flex-column gap-7 gap-lg-10">
                                    <!--begin::Start & End date-->
                                    <div class="card card-flush py-4">
                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>تاریخ شروع و پایان دوره</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->
                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            <!--begin::Input group-->
                                            <div class="mb-10 fv-row">
                                                <!--begin::Label-->
                                                <label class=" form-label">تاریخ شروع</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="text" name="start_date" class="form-control mb-2" placeholder="تاریخ شروع دوره" value="{{ $course->start_date }}" />
                                                <!--end::Input-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا تاریخ شروع دوره را وارد کنید.</div>
                                                <div class="fv-plugins-message-container invalid-feedback error_text start_date_error"><div data-field="start_date"></div></div>
                                                <!--end::Description-->
                                            </div>
                                            <!--end::Input group-->
                                            <!--begin::Input group-->
                                            <div class="mb-10 fv-row">
                                                <!--begin::Label-->
                                                <label class=" form-label">تاریخ پایان</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="text" name="end_date" class="form-control mb-2" placeholder="تاریخ پایان دوره" value="{{ $course->end_date }}" />
                                                <!--end::Input-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا تاریخ پایان دوره را وارد کنید.</div>
                                                <div class="fv-plugins-message-container invalid-feedback error_text end_date_error"><div data-field="end_date"></div></div>
                                                <!--end::Description-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Start & End date-->

                                    <!--begin::Trailer-->
                                    <div class="card card-flush py-4">
                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>تریلر دوره</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->
                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            <!--begin::Input group-->
                                            <div class="mb-10 fv-row">
                                                <!--begin::Label-->
                                                <label class=" form-label">تریلر</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="text" id="trailer" name="trailer" class="form-control mb-2" placeholder="تریلر دوره" value="{{ $course->trailer }}" />
                                                <input type="hidden" name="disk" value="dl"/>
                                                <!--end::Input-->
                                                <!--begin::Button-->
                                                <button id="select-trailer" class="btn btn-sm btn-light-primary mb-2">
                                                    انتخاب
                                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="16" class="mr-2" viewBox="0 0 32 32" xml:space="preserve" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .puchipuchi_een{} </style> <path class="puchipuchi_een" d="M7,6c0-2.757,2.243-5,5-5s5,2.243,5,5c0,1.627-0.793,3.061-2,3.974V6c0-1.654-1.346-3-3-3 S9,4.346,9,6v3.974C7.793,9.061,7,7.627,7,6z M24,13c-1.104,0-2,0.896-2,2v-1c0-1.104-0.896-2-2-2s-2,0.896-2,2v-1 c0-1.104-0.896-2-2-2s-2,0.896-2,2V6c0-1.104-0.896-2-2-2s-2,0.896-2,2v10.277C9.705,16.106,9.366,16,9,16c-1.104,0-2,0.896-2,2v3 c0,0.454,0.155,0.895,0.438,1.249L11,28h12l2.293-3.293C25.682,24.318,26,23.55,26,23v-8C26,13.896,25.104,13,24,13z M11,29v1 c0,0.552,0.447,1,1,1h10c0.553,0,1-0.448,1-1v-1H11z"></path> </g></svg>
                                                </button>
                                                <!--end::Button-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا تریلر دوره را وارد کنید.</div>
                                                <div class="fv-plugins-message-container invalid-feedback error_text trailer_error"><div data-field="trailer"></div></div>
                                                <!--end::Description-->

                                                <div class="separator separator-dashed my-5"></div>

                                                <!--Start::Disk-->
                                                <!--begin::Label-->
                                                <label class=" form-label">دیسک</label>
                                                <!--end::Label-->
                                                <!--begin::Select2-->
                                                <select name="disk" class="form-select mb-2" data-control="select2" data-hide-search="true" data-placeholder="انتخاب کنید" id="kt_courses_update_course_disk_select">
                                                    @foreach (config('file-manager.diskList') as $disk)
                                                        <option @if($disk == $course->disk) selected="selected" @endif value="{{ $disk }}">{{ $disk }}</option>
                                                    @endforeach
                                                </select>
                                                <!--end::Select2-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">دیسک حاوی ویدیو را انتخاب کنید.</div>
                                                <div class="fv-plugins-message-container invalid-feedback error_text disk_error"><div data-field="disk"></div></div>
                                                <!--end::Description-->

                                                <!--End::Disk-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Trailer-->
                                    <!--begin::Attachment-->
                                    <div class="card card-flush py-4">
                                        <!--begin::Card header-->
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h2>پیوست دوره</h2>
                                            </div>
                                        </div>
                                        <!--end::Card header-->
                                        <!--begin::Card body-->
                                        <div class="card-body pt-0">
                                            <!--begin::Input group-->
                                            <div class="mb-10 fv-row">
                                                <!--begin::Label-->
                                                <label class=" form-label">پیوست</label>
                                                <!--end::Label-->
                                                <!--begin::Input-->
                                                <input type="text" id="attached_file" name="attached_file" class="form-control mb-2" placeholder="فایل پیوست دوره" value="{{ $course->attached_file }}" />
                                                <!--end::Input-->
                                                <!--begin::Button-->
                                                <button id="select-attached-file" class="btn btn-sm btn-light-warning mb-2" >
                                                    انتخاب
                                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18" height="16" class="mr-2" viewBox="0 0 32 32" xml:space="preserve" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <style type="text/css"> .puchipuchi_een{} </style> <path class="puchipuchi_een" d="M7,6c0-2.757,2.243-5,5-5s5,2.243,5,5c0,1.627-0.793,3.061-2,3.974V6c0-1.654-1.346-3-3-3 S9,4.346,9,6v3.974C7.793,9.061,7,7.627,7,6z M24,13c-1.104,0-2,0.896-2,2v-1c0-1.104-0.896-2-2-2s-2,0.896-2,2v-1 c0-1.104-0.896-2-2-2s-2,0.896-2,2V6c0-1.104-0.896-2-2-2s-2,0.896-2,2v10.277C9.705,16.106,9.366,16,9,16c-1.104,0-2,0.896-2,2v3 c0,0.454,0.155,0.895,0.438,1.249L11,28h12l2.293-3.293C25.682,24.318,26,23.55,26,23v-8C26,13.896,25.104,13,24,13z M11,29v1 c0,0.552,0.447,1,1,1h10c0.553,0,1-0.448,1-1v-1H11z"></path> </g></svg>
                                                </button>
                                                <!--end::Button-->
                                                <!--begin::Description-->
                                                <div class="text-muted fs-7">لطفا پیوست دوره را درصورت وجود وارد کنید.</div>
                                                <div class="fv-plugins-message-container invalid-feedback error_text attached_file_error"><div data-field="attached_file"></div></div>
                                                <!--end::Description-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Attachment-->
                                </div>
                            </div>
                            <!--end::Tab pane-->
                        </div>
                        <!--end::Tab content-->
                        <div class="d-flex justify-content-end">
                            <!--begin::Button-->
                            <a href="javascript:history.back()" id="kt_courses_update_course_cancel" class="btn btn-light me-5">لغو</a>
                            <!--end::Button-->
                            <!--begin::Button-->
                            <button type="submit" id="kt_courses_update_course_submit" class="btn btn-primary">
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
    <script src="/admin/assets/js/custom/apps/courses/edit/edit-course.js"></script>
    <script src="/admin/assets/js/widgets.bundle.js"></script>
    <script src="/admin/assets/js/custom/widgets.js"></script>


    <!--start::file manager-->
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.getElementById('select-poster').addEventListener('click', (event) => {
                event.preventDefault();
                inputId = 'poster';
                window.open('/file-manager/fm-button', 'fm', 'width=800,height=400');
            });
        });

        document.addEventListener("DOMContentLoaded", function() {

            document.getElementById('select-attached-file').addEventListener('click', (event) => {
                event.preventDefault();
                inputId = 'attached_file';
                window.open('/file-manager/fm-button', 'fm', 'width=800,height=400');
            });
        });

        document.addEventListener("DOMContentLoaded", function() {

            document.getElementById('select-trailer').addEventListener('click', (event) => {
                event.preventDefault();
                inputId = 'trailer';
                window.open('/file-manager/fm-button', 'fm', 'width=800,height=400');
            });
        });

        let inputId = '';

        // set file link
        function fmSetLink($url) {
            document.getElementById(inputId).value = $url;
            if(inputId == 'poster'){
                document.getElementById("poster-placeholder").style.backgroundImage = "url("+$url+")";
            }
        }
    </script>
    <!--end::file manager-->
@endsection
