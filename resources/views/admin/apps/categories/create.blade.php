@extends('admin.layouts.master')

@section('title', 'ایجاد دسته بندی جدید')

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
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">ایجاد دسته بندی جدید</h1>
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
						<li class="breadcrumb-item text-muted">مدیریت دسته بندی ها</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">ایجاد دسته بندی جدید</li>
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
                <form id="kt_add_category_form" action="{{ route('admin-create-category') }}"  method="POST"  class="form d-flex flex-column flex-lg-row" data-kt-redirect="{{ route('admin-categories-list') }}">
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
                                <div class="card-toolbar">
                                    <div class="rounded-circle bg-success w-15px h-15px" id="kt_add_category_status"></div>
                                </div>
                                <!--begin::Card toolbar-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Select2-->
                                <select class="form-select mb-2" name="status" data-control="select2" data-hide-search="true" data-placeholder="انتخاب کنید" id="kt_add_category_status_select">
                                    <option value="1" selected="selected">فعال</option>
                                    {{--  <option value="scheduled">Scheduled</option>  --}}
                                    <option value="0">غیرفعال</option>
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">وضعیت دسته بندی را مشخص کنید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text status_error"><div data-field="status"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Status-->
                        <!--begin::Parent Category settings-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="required">دسته والد</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Select Parent category-->
                                <label for="" class="form-label">انتخاب دسته بندی والد</label>
                                <!--end::Select Parent category-->
                                <!--begin::Select2-->
                                <select class="form-select mb-2" name="parent_id" data-control="select2" data-hide-search="false" data-placeholder="بدون والد" id="">
                                    <option value="0">بدون دسته والد</option>
                                    @foreach (\App\Models\Category::all() as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                <!--begin::Description-->
                                <div class="text-muted fs-7">اگر زیر دسته ثبت میکنید، دسته والد را انتخاب نمایید.</div>
                                <div class="fv-plugins-message-container invalid-feedback error_text parent_id_error"><div data-field="parent_id"></div></div>
                                <!--end::Description-->
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Parent Category settings-->
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
                        <!--begin::Meta options-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2 class="required">برچسب‌ها</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div>
                                    <!--begin::Editor-->
                                    <input id="kt_add_category_meta_keywords" name="tags" class="form-control mb-2" />
                                    <!--end::Editor-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">می‌توانید فهرستی از برچسب‌ها را انتخاب کنید، برچسب هارا با<code>,</code>از هم جدا کنید.</div>
                                    <div class="fv-plugins-message-container invalid-feedback error_text tags_error"><div data-field="tags"></div></div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::Meta options-->
                        <!--begin::Automation-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>روش اتصال دوره‌ها</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div>
                                    <!--begin::Methods-->
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::Radio-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input class="form-check-input me-3" name="method" type="radio" value="0" id="kt_add_category_automation_0" checked='checked' />
                                            <!--end::Input-->
                                            <!--begin::Label-->
                                            <label class="form-check-label" for="kt_add_category_automation_0">
                                                <div class="fw-bold text-gray-800">دستی</div>
                                                <div class="text-gray-600">با انتخاب دستی این دسته در حین ایجاد یا به روز رسانی دوره، دوره‌ها را یکی یکی به این دسته اضافه کنید.</div>
                                            </label>
                                            <!--end::Label-->
                                        </div>
                                        <!--end::Radio-->
                                    </div>
                                    <!--end::Input row-->
                                    <div class='separator separator-dashed my-5'></div>
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::Radio-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input disabled class="form-check-input me-3" name="method" type="radio" value="1" id="kt_add_category_automation_1" />
                                            <!--end::Input-->
                                            <!--begin::Label-->
                                            <label class="form-check-label" for="kt_add_category_automation_1">
                                                <div class="fw-bold text-gray-800">خودکار</div>
                                                <div class="text-gray-600">دوره‌ها مطابق با شرایط زیر به طور خودکار به این دسته اختصاص داده می شوند.</div>
                                            </label>
                                            <!--end::Label-->
                                        </div>
                                        <!--end::Radio-->
                                    </div>
                                    <!--end::Input row-->
                                    <!--end::Methods-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="d-none mt-10" data-kt-catalog-add-category="auto-options">
                                    <!--begin::Label-->
                                    <label class="form-label">شرایط</label>
                                    <!--end::Label-->
                                    <!--begin::Conditions-->
                                    <div class="d-flex flex-wrap align-items-center text-gray-600 gap-5 mb-7">
                                        <span>دوره‌هایی که مطابقت داشته باشند با:</span>
                                        <!--begin::Radio-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="conditions" value="" id="all_conditions" checked="checked" />
                                            <label class="form-check-label" for="all_conditions">همه شرایط</label>
                                        </div>
                                        <!--end::Radio-->
                                        <!--begin::Radio-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="conditions" value="" id="any_conditions" />
                                            <label class="form-check-label" for="any_conditions">هر شرطی</label>
                                        </div>
                                        <!--end::Radio-->
                                    </div>
                                    <!--end::Conditions-->
                                    <!--begin::Repeater-->
                                    <div id="kt_add_category_conditions">
                                        <!--begin::Form group-->
                                        <div class="form-group">
                                            <div data-repeater-list="kt_add_category_conditions" class="d-flex flex-column gap-3">
                                                <div data-repeater-item="" class="form-group d-flex flex-wrap align-items-center gap-5">
                                                    <!--begin::Select2-->
                                                    <div class="w-100 w-md-200px">
                                                        <select class="form-select" name="condition_type" data-placeholder="Select an option" data-kt-catalog-add-category="condition_type">
                                                            <option></option>
                                                            <option value="title">Product Title</option>
                                                            <option value="tag" selected="selected">Product Tag</option>
                                                            <option value="price">Prodict Price</option>
                                                        </select>
                                                    </div>
                                                    <!--end::Select2-->
                                                    <!--begin::Select2-->
                                                    <div class="w-100 w-md-200px">
                                                        <select class="form-select" name="condition_equals" data-placeholder="Select an option" data-kt-catalog-add-category="condition_equals">
                                                            <option></option>
                                                            <option value="equal" selected="selected">is equal to</option>
                                                            <option value="notequal">is not equal to</option>
                                                            <option value="greater">is greater than</option>
                                                            <option value="less">is less than</option>
                                                            <option value="starts">starts with</option>
                                                            <option value="ends">ends with</option>
                                                        </select>
                                                    </div>
                                                    <!--end::Select2-->
                                                    <!--begin::Input-->
                                                    <input type="text" class="form-control mw-100 w-200px" name="condition_label" placeholder="" />
                                                    <!--end::Input-->
                                                    <!--begin::Button-->
                                                    <button type="button" data-repeater-delete="" class="btn btn-sm btn-icon btn-light-danger">
                                                        <!--begin::Svg Icon | path: icons/duotune/arrows/arr088.svg-->
                                                        <span class="svg-icon svg-icon-2">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <rect opacity="0.5" x="7.05025" y="15.5356" width="12" height="2" rx="1" transform="rotate(-45 7.05025 15.5356)" fill="currentColor" />
                                                                <rect x="8.46447" y="7.05029" width="12" height="2" rx="1" transform="rotate(45 8.46447 7.05029)" fill="currentColor" />
                                                            </svg>
                                                        </span>
                                                        <!--end::Svg Icon-->
                                                    </button>
                                                    <!--end::Button-->
                                                </div>
                                            </div>
                                        </div>
                                        <!--end::Form group-->
                                        <!--begin::Form group-->
                                        <div class="form-group mt-5">
                                            <!--begin::Button-->
                                            <button type="button" data-repeater-create="" class="btn btn-sm btn-light-primary">
                                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr087.svg-->
                                            <span class="svg-icon svg-icon-2">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect opacity="0.5" x="11" y="18" width="12" height="2" rx="1" transform="rotate(-90 11 18)" fill="currentColor" />
                                                    <rect x="6" y="11" width="12" height="2" rx="1" fill="currentColor" />
                                                </svg>
                                            </span>
                                            <!--end::Svg Icon-->Add another condition</button>
                                            <!--end::Button-->
                                        </div>
                                        <!--end::Form group-->
                                    </div>
                                    <!--end::Repeater-->
                                </div>
                                <!--end::Input group-->
                                <div class="fv-plugins-message-container invalid-feedback error_text method_error"><div data-field="method"></div></div>
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Automation-->
                        <div class="d-flex justify-content-end">
                            <!--begin::Button-->
                            <a href="javascript:history.back()" id="kt_add_product_cancel" class="btn btn-light me-5">لغو</a>
                            <!--end::Button-->
                            <!--begin::Button-->
                            <button type="submit" id="kt_add_category_submit" class="btn btn-primary">
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
    <script src="/admin/assets/js/custom/apps/categories/save-category.js"></script>
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
