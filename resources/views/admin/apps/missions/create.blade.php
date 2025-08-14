@extends('admin.layouts.master')

@section('title', 'ایجاد ماموریت')

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
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">ایجاد
                        ماموریت جدید</h1>
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
                        <li class="breadcrumb-item text-muted">مدیریت ماموریت ها</li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted">ایجاد ماموریت جدید</li>
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
                <form id="kt_missions_add_mission_form" action="{{ route('admin-mission-create') }}" method="POST"
                    enctype="multipart/form-data" class="form d-flex flex-column flex-lg-row"
                    data-kt-redirect="{{ route('admin-mission-list') }}">
                    @csrf
                    <!--begin::Aside column-->
                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <!--begin::icon settings-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">آیکون</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body text-center pt-0">
                                <!--begin::Image input-->
                                <!--begin::Image input placeholder-->
                                <style>
                                    .image-input-placeholder {
                                        background-image: url('/admin/assets/media/svg/files/blank-image.svg');
                                    }

                                    [data-theme="dark"] .image-input-placeholder {
                                        background-image: url('/admin/assets/media/svg/files/blank-image-dark.svg');
                                    }
                                </style>
                                <!--end::Image input placeholder-->
                                <div id="icon-placeholder"
                                    class="image-input image-input-empty image-input-outline image-input-placeholder mb-3"
                                    data-kt-image-input="true">
                                    <!--begin::Preview existing avatar-->
                                    <div class="image-input-wrapper w-150px h-150px"></div>
                                    <!--end::Preview existing avatar-->
                                    <!--begin::Label-->
                                    <label id="select-icon"
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip" title="تغییر">
                                        <i class="bi bi-pencil-fill fs-7"></i>
                                    </label>
                                </div>
                                <!--end::Image input-->
                                <input type="text" class="form-control mb-2" id="icon" name="icon" />

                                <!--begin::Description-->
                                <div class="text-muted fs-7">فرمت های مورد قبول: png jpg jpeg</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text icon_error">
                                    <div data-field="icon"></div>
                                </div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::icon settings-->
                        <!--begin::Category & tags-->
                        <div class="card card-flush py-4">
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <!--begin::Label-->
                                <label class="required form-label">دسته بندی</label>
                                <!--end::Label-->
                                <!--begin::Select2-->
                                <select name="category" class="form-select mb-2" data-control="select2"
                                    data-placeholder="انتخاب کنید" data-allow-clear="true">
                                    <option></option>
                                    @foreach (App\Models\MissionCategory::all() as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">دسته بندی ماموریت را انتخاب کنید.</div>
                                <div class="fv-plugins-message-container mb-7 invalid-feedback error_text category_error">
                                    <div data-field="category"></div>
                                </div>
                                <!--end::Description-->
                                <!--end::Input group-->
                                <!--begin::Button-->
                                <a href="{{ route('admin-create-category') }}" target="_blank"
                                    class="btn btn-light-primary btn-sm mb-10">
                                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr087.svg-->
                                    <span class="svg-icon svg-icon-2">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <rect opacity="0.5" x="11" y="18" width="12" height="2" rx="1"
                                                transform="rotate(-90 11 18)" fill="currentColor" />
                                            <rect x="6" y="11" width="12" height="2" rx="1"
                                                fill="currentColor" />
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    ایجاد دسته بندی جدید
                                </a>
                                <!--end::Button-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Category & tags-->
                    </div>
                    <!--end::Aside column-->
                    <!--begin::Main column-->
                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">

                        <!--begin::Tab content-->
                        <div class="">
                            <!--begin::Tab pane-->
                            <div class="d-flex flex-column gap-7 gap-lg-10">

                                {{-- @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                    <li class="">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif --}}








                                <!--begin::General options-->
                                <div class="card card-flush py-4">
                                    <!--begin::Card body-->
                                    <div class="card-body pt-0">
                                        <!--begin::Input group-->
                                        <div class="mb-10 fv-row">
                                            <!--begin::Label-->
                                            <label class="required form-label">عنوان فارسی</label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <input type="text" name="title" class="form-control mb-2"
                                                placeholder="عنوان فارسی ماموریت" value="" />
                                            <!--end::Input-->
                                            <!--begin::Description-->
                                            <div class="text-muted fs-7">لطفا یک نام یکتا و کوتاه انتخاب کنید.</div>
                                            <div
                                                class="fv-plugins-message-container invalid-feedback error_text title_error">
                                                <div data-field="title"></div>
                                            </div>
                                            <!--end::Description-->
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-10 fv-row">
                                            <!--begin::Label-->
                                            <label class="required form-label">شناسه </label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <input type="text" name="id" class="form-control mb-2"
                                                placeholder="شناسه" value="" />
                                            <!--end::Input-->
                                            <!--begin::Description-->
                                            <div class="text-muted fs-7">لطفا یک شناسه یکتا و کوتاه انتخاب کنید.</div>
                                            <div class="fv-plugins-message-container invalid-feedback error_text id_error">
                                                <div data-field="id"></div>
                                            </div>
                                            <!--end::Description-->
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div>
                                            <!--begin::Label-->
                                            <label class="required form-label">توضیحات</label>
                                            <!--end::Label-->
                                            <!--begin::Editor-->
                                            {{-- <div id="kt_missions_add_mission_description" class="min-h-200px mb-2">
                                        </div> --}}
                                            <textarea name="description" rows="7" class="form-control mb-2"></textarea>
                                            <!--end::Editor-->
                                            <!--begin::Description-->
                                            <div class="text-muted fs-7">لطفا توضیحات ماموریت را وارد کنید.</div>

                                            <div
                                                class="fv-plugins-message-container invalid-feedback error_text description_error">
                                                <div data-field="description"></div>
                                            </div>
                                            <!--end::Description-->
                                        </div>
                                        <!--end::Input group-->
                                    </div>
                                    <!--end::Card body-->
                                </div>
                                <!--end::General options-->
                                <!--begin::Media-->

                                <!--end::Media-->
                                <!--begin::Levels-->
                                <div class="card card-flush py-4">
                                    <!--begin::Card header-->
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h2>مراحل ماموریت</h2>
                                        </div>
                                    </div>
                                    <!--end::Card header-->

                                    <!--begin::Card body-->
                                    <div class="card-body pt-0">
                                        <!--begin::Input group-->
                                        <div class="" data-kt-ecommerce-catalog-add-product="auto-options">
                                            <!--begin::Repeater-->
                                            <div id="kt_ecommerce_add_product_options">
                                                <!--begin::Form group-->
                                                <div class="form-group">
                                                    <div data-repeater-list="kt_ecommerce_add_product_options"
                                                        class="d-flex flex-column gap-3">
                                                        <div data-repeater-item="">
                                                            <div class="form-group d-flex flex-wrap align-items-center gap-5"
                                                                style="">
                                                                <!--begin::Select2-->
                                                                <div class="w-100 w-md-200px">
                                                                    <select class="form-select" data-control="select2"
                                                                        name="kt_ecommerce_add_product_options[0][product_option]"
                                                                        data-placeholder="انتخاب ماموریت"
                                                                        data-kt-ecommerce-catalog-add-product="product_option"
                                                                        tabindex="-1" aria-hidden="true">
                                                                        <option>
                                                                        </option>
                                                                        @foreach ($missions as $mission)
                                                                            <option value="{{ $mission->id }}">{{ $mission->title }}</option>
                                                                        @endforeach
                                                                        
                                                                    </select>
                                                                </div>
                                                                <!--end::Select2-->

                                                                <!--begin::Input-->
                                                                <input type="number" max="5"
                                                                    class="form-control mw-100 w-200px"
                                                                    name="kt_ecommerce_add_product_options[0][product_option_value]"
                                                                    placeholder="سطح">
                                                                <!--end::Input-->

                                                                <button type="button" data-repeater-delete=""
                                                                    class="btn btn-sm btn-icon btn-light-danger">
                                                                    <svg style="width:15px;height:15px"
                                                                        fill="currentColor" viewBox="0 0 16 16"
                                                                        xmlns="http://www.w3.org/2000/svg">
                                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                                        <g id="SVGRepo_tracerCarrier"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"></g>
                                                                        <g id="SVGRepo_iconCarrier">
                                                                            <path
                                                                                d="M0 14.545L1.455 16 8 9.455 14.545 16 16 14.545 9.455 8 16 1.455 14.545 0 8 6.545 1.455 0 0 1.455 6.545 8z"
                                                                                fill-rule="evenodd"></path>
                                                                        </g>
                                                                    </svg>
                                                                </button>

                                                            </div>
                                                            <div class="w-100 d-flex flex-wrap gap-5">

                                                            </div>
                                                            <hr class="">
                                                        </div>

                                                    </div>
                                                </div>
                                                <!--end::Form group-->

                                                <!--begin::Form group-->
                                                <div class="form-group mt-5">
                                                    <button type="button" data-repeater-create=""
                                                        class="btn btn-sm btn-light-primary">
                                                        <i class="ki-duotone ki-plus fs-2"></i> افزودن سطح جدید
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
                                <!--end::Levels-->

                            </div>
                            <!--end::Tab pane-->
                        </div>
                        <!--end::Tab content-->
                        <div class="d-flex justify-content-end">
                            <!--begin::Button-->
                            <a href="javascript:history.back()" id="kt_missions_add_mission_cancel"
                                class="btn btn-light me-5">لغو</a>
                            <!--end::Button-->
                            <!--begin::Button-->
                            <button type="submit" id="kt_missions_add_mission_submit" class="btn btn-primary">
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
    <script src="/admin/assets/js/custom/apps/missions/create/save-mission.js"></script>
    <script src="/admin/assets/js/widgets.bundle.js"></script>
    <script src="/admin/assets/js/custom/widgets.js"></script>


    <!--start::file manager-->
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.getElementById('select-icon').addEventListener('click', (event) => {
                event.preventDefault();
                inputId = 'icon';
                window.open('/file-manager/fm-button', 'fm', 'width=800,height=400');
            });
        });


        let inputId = '';

        // set file link
        function fmSetLink($url) {
            document.getElementById(inputId).value = $url;
            if (inputId == 'icon') {
                document.getElementById("icon-placeholder").style.backgroundImage = "url(" + $url + ")";
            }
        }
    </script>
    <!--end::file manager-->
@endsection
