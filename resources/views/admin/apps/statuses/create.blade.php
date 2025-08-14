@extends('admin.layouts.master')

@section('title', 'ایجاد وضعیت جدید')

@section('head')
    <link href="/admin/assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />

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
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">ایجاد وضعیت جدید</h1>
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
						<li class="breadcrumb-item text-muted">مدیریت وضعیت ها</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">ایجاد وضعیت جدید</li>
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
                <form id="kt_add_status_form" action="{{ route('admin-create-status') }}"  method="POST"  class="form d-flex flex-column flex-lg-row" data-kt-redirect="{{ route('admin-statuses-list') }}">
                    @csrf
                    <!--begin::Aside column-->
                    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
                        <!--begin::Icon settings-->
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
                                <style>.image-input-placeholder { background-image: url('/admin/assets/media/svg/files/blank-image.svg'); } [data-theme="dark"] .image-input-placeholder { background-image: url('/admin/assets/media/svg/files/blank-image-dark.svg'); }</style>
                                <!--end::Image input placeholder-->
                                <div id="icon-placeholder" class="image-input image-input-empty image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                                    <!--begin::Preview existing avatar-->
                                    <div class="image-input-wrapper w-150px h-150px"></div>
                                    <!--end::Preview existing avatar-->
                                    <!--begin::Label-->
                                    <label id="select-icon" class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="تغییر">
                                        <i class="bi bi-pencil-fill fs-7"></i>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <!--end::Image input-->
                                <input type="text" class="form-control mb-2" id="icon" name="icon"  />

                                <!--begin::Description-->
                                <div class="text-muted fs-7">فرمت های مورد قبول: png  jpg  jpeg</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text icon_error"><div data-field="icon"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Icon settings-->
                    </div>
                    <!--end::Aside column-->
                    <!--begin::Main column-->
                    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
                        <!--begin::General options-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>ویژگی ها</h2>
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
                                    <input type="text" name="title" class="form-control mb-2" placeholder="عنوان فارسی دوره" value="" />
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
                                    <input type="text" name="english_title" class="form-control mb-2" placeholder="عنوان لاتین دوره" value="" />
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
                                    <label class="form-label">توضیحات</label>
                                    <!--end::Label-->
                                    <!--begin::Editor-->
                                    {{--  <div id="kt_courses_add_course_description" class="min-h-200px mb-2"></div>  --}}
                                    <textarea name="description" rows="7" class="form-control mb-2"></textarea>
                                    <!--end::Editor-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">لطفا توضیحات دوره را وارد کنید.</div>

                                    <div class="fv-plugins-message-container invalid-feedback error_text description_error"><div data-field="description"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::General options-->
                        <div class="d-flex justify-content-end">
                            <!--begin::Button-->
                            <a href="javascript:history.back()" id="kt_add_product_cancel" class="btn btn-light me-5">لغو</a>
                            <!--end::Button-->
                            <!--begin::Button-->
                            <button type="submit" id="kt_add_status_submit" class="btn btn-primary">
                                <span class="indicator-label">ثبت</span>
                                <span class="indicator-progress">لطفا منتظر بمانید...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                            <!--end::Button-->
                        </div>
                    </div>
                    <!--end::Main column-->
                </form>
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
    <script src="/admin/assets/js/custom/apps/statuses/save-status.js"></script>
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
            if(inputId == 'icon'){
                document.getElementById("icon-placeholder").style.backgroundImage = "url("+$url+")";
            }
        }
    </script>
    <!--end::file manager-->
@endsection
