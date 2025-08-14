@extends('admin.layouts.master')

@section('title', 'لیست همه کاربران')

@section('head')

	<link href="/admin/assets/plugins/custom/datatables/datatables.bundle.rtl.css" rel="stylesheet" type="text/css" />

@endsection

@section('content')

    <!--begin::Content wrapper-->
    <div class="d-flex flex-column flex-column-fluid">
        <!--begin::Toolbar-->
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <!--begin::Toolbar container-->
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">لیست کاربران</h1>
                    <!--end::Title-->
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted">
                            <a href="../../demo1/dist/index.html" class="text-muted text-hover-primary">خانه</a>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted">مدیریت کاربران</li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="breadcrumb-item text-muted">لیست کاربران</li>
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
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <!--begin::Search-->
                            <div class="d-flex align-items-center position-relative my-1">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen021.svg-->
                                <span class="svg-icon svg-icon-1 position-absolute ms-6">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                                        <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="جستجوی کاربر" />
                            </div>
                            <!--end::Search-->
                        </div>
                        <!--begin::Card title-->
                        <!--begin::Card toolbar-->
                        <div class="card-toolbar">
                            <!--begin::Toolbar-->
                            <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                <!--begin::Filter-->
                                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen031.svg-->
                                <span class="svg-icon svg-icon-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                    فیلتر
                                </button>
                                <!--begin::Menu 1-->
                                <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                                    <!--begin::Header-->
                                    <div class="px-7 py-5">
                                        <div class="fs-5 text-dark fw-bold">گزینه های فیلتر</div>
                                    </div>
                                    <!--end::Header-->
                                    <!--begin::Separator-->
                                    <div class="separator border-gray-200"></div>
                                    <!--end::Separator-->
                                    <!--begin::Content-->
                                    <div class="px-7 py-5" data-kt-user-table-filter="form">
                                        <!--begin::Input group-->
                                        <div class="mb-10">
                                            <label class="form-label fs-6 fw-semibold">نقش:</label>
                                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="انتخاب" data-allow-clear="true" data-kt-user-table-filter="role" data-hide-search="true">
                                                <option></option>
                                                <option value="Administrator">مدیر</option>
                                                <option value="Analyst">آنالیزور</option>
                                                <option value="Developer">توسعه دهنده</option>
                                                <option value="Support">پشتیبانی</option>
                                                <option value="Trial">آموزشی</option>
                                            </select>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Input group-->
                                        <div class="mb-10">
                                            <label class="form-label fs-6 fw-semibold">تایید دومرحله ای:</label>
                                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true" data-placeholder="انتخاب" data-allow-clear="true" data-kt-user-table-filter="two-step" data-hide-search="true">
                                                <option></option>
                                                <option value="Enabled">فعال</option>
                                            </select>
                                        </div>
                                        <!--end::Input group-->
                                        <!--begin::Actions-->
                                        <div class="d-flex justify-content-end">
                                            <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" data-kt-menu-dismiss="true" data-kt-user-table-filter="reset">پاک کردن</button>
                                            <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true" data-kt-user-table-filter="filter">تایید</button>
                                        </div>
                                        <!--end::Actions-->
                                    </div>
                                    <!--end::Content-->
                                </div>
                                <!--end::Menu 1-->
                                <!--end::Filter-->
                                <!--begin::Export-->
                                <button type="button" class="btn btn-light-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_export_users">
                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr078.svg-->
                                <span class="svg-icon svg-icon-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect opacity="0.3" x="12.75" y="4.25" width="12" height="2" rx="1" transform="rotate(90 12.75 4.25)" fill="currentColor" />
                                        <path d="M12.0573 6.11875L13.5203 7.87435C13.9121 8.34457 14.6232 8.37683 15.056 7.94401C15.4457 7.5543 15.4641 6.92836 15.0979 6.51643L12.4974 3.59084C12.0996 3.14332 11.4004 3.14332 11.0026 3.59084L8.40206 6.51643C8.0359 6.92836 8.0543 7.5543 8.44401 7.94401C8.87683 8.37683 9.58785 8.34458 9.9797 7.87435L11.4427 6.11875C11.6026 5.92684 11.8974 5.92684 12.0573 6.11875Z" fill="currentColor" />
                                        <path opacity="0.3" d="M18.75 8.25H17.75C17.1977 8.25 16.75 8.69772 16.75 9.25C16.75 9.80228 17.1977 10.25 17.75 10.25C18.3023 10.25 18.75 10.6977 18.75 11.25V18.25C18.75 18.8023 18.3023 19.25 17.75 19.25H5.75C5.19772 19.25 4.75 18.8023 4.75 18.25V11.25C4.75 10.6977 5.19771 10.25 5.75 10.25C6.30229 10.25 6.75 9.80228 6.75 9.25C6.75 8.69772 6.30229 8.25 5.75 8.25H4.75C3.64543 8.25 2.75 9.14543 2.75 10.25V19.25C2.75 20.3546 3.64543 21.25 4.75 21.25H18.75C19.8546 21.25 20.75 20.3546 20.75 19.25V10.25C20.75 9.14543 19.8546 8.25 18.75 8.25Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                    صدور
                                </button>
                                <!--end::Export-->
                                <!--begin::Add user-->
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr075.svg-->
                                <span class="svg-icon svg-icon-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect opacity="0.5" x="11.364" y="20.364" width="16" height="2" rx="1" transform="rotate(-90 11.364 20.364)" fill="currentColor" />
                                        <rect x="4.36396" y="11.364" width="16" height="2" rx="1" fill="currentColor" />
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                    ایجاد کاربر
                                </button>
                                <!--end::Add user-->
                            </div>
                            <!--end::Toolbar-->
                            <!--begin::Group actions-->
                            <div class="d-flex justify-content-end align-items-center d-none" data-kt-user-table-toolbar="selected">
                                <div class="fw-bold me-5">
                                <span class="me-2" data-kt-user-table-select="selected_count"></span>Selected</div>
                                <button type="button" class="btn btn-danger" data-kt-user-table-select="delete_selected">Delete Selected</button>
                            </div>
                            <!--end::Group actions-->
                            <!--begin::Modal - Adjust Balance-->
                            <div class="modal fade" id="kt_modal_export_users" tabindex="-1" aria-hidden="true">
                                <!--begin::Modal dialog-->
                                <div class="modal-dialog modal-dialog-centered mw-650px">
                                    <!--begin::Modal content-->
                                    <div class="modal-content">
                                        <!--begin::Modal header-->
                                        <div class="modal-header">
                                            <!--begin::Modal title-->
                                            <h2 class="fw-bold">صادر کردن کاربران</h2>
                                            <!--end::Modal title-->
                                            <!--begin::Close-->
                                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr061.svg-->
                                                <span class="svg-icon svg-icon-1">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
                                                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
                                                    </svg>
                                                </span>
                                                <!--end::Svg Icon-->
                                            </div>
                                            <!--end::Close-->
                                        </div>
                                        <!--end::Modal header-->
                                        <!--begin::Modal body-->
                                        <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                                            <!--begin::Form-->
                                            <form id="kt_modal_export_users_form" class="form" action="#">
                                                <!--begin::Input group-->
                                                <div class="fv-row mb-10">
                                                    <!--begin::Label-->
                                                    <label class="fs-6 fw-semibold form-label mb-2">انتخاب نقش:</label>
                                                    <!--end::Label-->
                                                    <!--begin::Input-->
                                                    <select name="role" data-control="select2" data-placeholder="انتخاب نقش" data-hide-search="true" class="form-select form-select-solid fw-bold">
                                                        <option></option>
                                                        <option value="Administrator">مدیر</option>
                                                        <option value="Analyst">آنالیزور</option>
                                                        <option value="Developer">توسعه دهنده</option>
                                                        <option value="Support">پشتیبانی</option>
                                                        <option value="Trial">آموزشی</option>
                                                    </select>
                                                    <!--end::Input-->
                                                </div>
                                                <!--end::Input group-->
                                                <!--begin::Input group-->
                                                <div class="fv-row mb-10">
                                                    <!--begin::Label-->
                                                    <label class="required fs-6 fw-semibold form-label mb-2">انتخاب فرمت:</label>
                                                    <!--end::Label-->
                                                    <!--begin::Input-->
                                                    <select name="format" data-control="select2" data-placeholder="انتخاب فرمت" data-hide-search="true" class="form-select form-select-solid fw-bold">
                                                        <option></option>
                                                        <option value="excel">Excel</option>
                                                        <option value="pdf">PDF</option>
                                                        <option value="cvs">CVS</option>
                                                        <option value="zip">ZIP</option>
                                                    </select>
                                                    <!--end::Input-->
                                                </div>
                                                <!--end::Input group-->
                                                <!--begin::Actions-->
                                                <div class="text-center">
                                                    <button type="reset" class="btn btn-light me-3" data-kt-users-modal-action="cancel">انصراف</button>
                                                    <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
                                                        <span class="indicator-label">صدور</span>
                                                        <span class="indicator-progress">لطفا منتظر بمانید...
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                                    </button>
                                                </div>
                                                <!--end::Actions-->
                                            </form>
                                            <!--end::Form-->
                                        </div>
                                        <!--end::Modal body-->
                                    </div>
                                    <!--end::Modal content-->
                                </div>
                                <!--end::Modal dialog-->
                            </div>
                            <!--end::Modal - New Card-->
                            <!--begin::Modal - Add task-->
                            <div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true">
                                <!--begin::Modal dialog-->
                                <div class="modal-dialog modal-dialog-centered mw-650px">
                                    <!--begin::Modal content-->
                                    <div class="modal-content">
                                        <!--begin::Modal header-->
                                        <div class="modal-header" id="kt_modal_add_user_header">
                                            <!--begin::Modal title-->
                                            <h2 class="fw-bold">ایجاد کاربر</h2>
                                            <!--end::Modal title-->
                                            <!--begin::Close-->
                                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr061.svg-->
                                                <span class="svg-icon svg-icon-1">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
                                                        <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
                                                    </svg>
                                                </span>
                                                <!--end::Svg Icon-->
                                            </div>
                                            <!--end::Close-->
                                        </div>
                                        <!--end::Modal header-->
                                        <!--begin::Modal body-->
                                        <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                                            <!--begin::Form-->
                                            <form id="kt_modal_add_user_form" class="form" action="#">
                                                <!--begin::Scroll-->
                                                <div class="d-flex flex-column scroll-y me-n7 pe-7" id="kt_modal_add_user_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_user_header" data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">
                                                    <!--begin::Input group-->
                                                    <div class="fv-row mb-7">
                                                        <!--begin::Label-->
                                                        <label class="d-block fw-semibold fs-6 mb-5">تصویر پروفایل</label>
                                                        <!--end::Label-->
                                                        <!--begin::Image placeholder-->
                                                        <style>.image-input-placeholder { background-image: url('/admin/assets/media/svg/files/blank-image.svg'); } [data-theme="dark"] .image-input-placeholder { background-image: url('/admin/assets/media/svg/files/blank-image-dark.svg'); }</style>
                                                        <!--end::Image placeholder-->
                                                        <!--begin::Image input-->
                                                        <div class="image-input image-input-outline image-input-placeholder" data-kt-image-input="true">
                                                            <!--begin::Preview existing avatar-->
                                                            <div class="image-input-wrapper w-125px h-125px" style="background-image: url(/admin/assets/media/avatars/300-6.jpg);"></div>
                                                            <!--end::Preview existing avatar-->
                                                            <!--begin::Label-->
                                                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="تغییر تصویر">
                                                                <i class="bi bi-pencil-fill fs-7"></i>
                                                                <!--begin::Inputs-->
                                                                <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                                                <input type="hidden" name="avatar_remove" />
                                                                <!--end::Inputs-->
                                                            </label>
                                                            <!--end::Label-->
                                                            <!--begin::Cancel-->
                                                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                                                <i class="bi bi-x fs-2"></i>
                                                            </span>
                                                            <!--end::Cancel-->
                                                            <!--begin::Remove-->
                                                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="حذف تصویر">
                                                                <i class="bi bi-x fs-2"></i>
                                                            </span>
                                                            <!--end::Remove-->
                                                        </div>
                                                        <!--end::Image input-->
                                                        <!--begin::Hint-->
                                                        <div class="form-text">فرمت های صحیح: png, jpg, jpeg.</div>
                                                        <!--end::Hint-->
                                                    </div>
                                                    <!--end::Input group-->
                                                    <!--begin::Input group-->
                                                    <div class="fv-row mb-7">
                                                        <!--begin::Label-->
                                                        <label class="required fw-semibold fs-6 mb-2">نام</label>
                                                        <!--end::Label-->
                                                        <!--begin::Input-->
                                                        <input type="text" name="user_name" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="نام" value="میلاد" />
                                                        <!--end::Input-->
                                                    </div>
                                                    <!--end::Input group-->
                                                    <!--begin::Input group-->
                                                    <div class="fv-row mb-7">
                                                        <!--begin::Label-->
                                                        <label class="required fw-semibold fs-6 mb-2">نام خانوادگی</label>
                                                        <!--end::Label-->
                                                        <!--begin::Input-->
                                                        <input type="text" name="user_last_name" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="نام خانوادگی" value="محکی" />
                                                        <!--end::Input-->
                                                    </div>
                                                    <!--end::Input group-->
                                                    <!--begin::Input group-->
                                                    <div class="fv-row mb-7">
                                                        <!--begin::Label-->
                                                        <label class="required fw-semibold fs-6 mb-2">ایمیل</label>
                                                        <!--end::Label-->
                                                        <!--begin::Input-->
                                                        <input type="email" name="user_email" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="info@zanburak.ir" value="info@zanburak.ir" />
                                                        <!--end::Input-->
                                                    </div>
                                                    <!--end::Input group-->
                                                    <!--begin::Input group-->
                                                    <div class="mb-7">
                                                        <!--begin::Label-->
                                                        <label class="required fw-semibold fs-6 mb-5">نقش</label>
                                                        <!--end::Label-->
                                                        <!--begin::Roles-->
                                                        <!--begin::Input row-->
                                                        <div class="d-flex fv-row">
                                                            <!--begin::Radio-->
                                                            <div class="form-check form-check-custom form-check-solid">
                                                                <!--begin::Input-->
                                                                <input class="form-check-input me-3" name="user_role" type="radio" value="0" id="kt_modal_update_role_option_0" checked='checked' />
                                                                <!--end::Input-->
                                                                <!--begin::Label-->
                                                                <label class="form-check-label" for="kt_modal_update_role_option_0">
                                                                    <div class="fw-bold text-gray-800">Administrator</div>
                                                                    <div class="text-gray-600">Best for business owners and company administrators</div>
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
                                                                <input class="form-check-input me-3" name="user_role" type="radio" value="1" id="kt_modal_update_role_option_1" />
                                                                <!--end::Input-->
                                                                <!--begin::Label-->
                                                                <label class="form-check-label" for="kt_modal_update_role_option_1">
                                                                    <div class="fw-bold text-gray-800">Developer</div>
                                                                    <div class="text-gray-600">Best for developers or people primarily using the API</div>
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
                                                                <input class="form-check-input me-3" name="user_role" type="radio" value="2" id="kt_modal_update_role_option_2" />
                                                                <!--end::Input-->
                                                                <!--begin::Label-->
                                                                <label class="form-check-label" for="kt_modal_update_role_option_2">
                                                                    <div class="fw-bold text-gray-800">Analyst</div>
                                                                    <div class="text-gray-600">Best for people who need full access to analytics data, but don't need to update business settings</div>
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
                                                                <input class="form-check-input me-3" name="user_role" type="radio" value="3" id="kt_modal_update_role_option_3" />
                                                                <!--end::Input-->
                                                                <!--begin::Label-->
                                                                <label class="form-check-label" for="kt_modal_update_role_option_3">
                                                                    <div class="fw-bold text-gray-800">Support</div>
                                                                    <div class="text-gray-600">Best for employees who regularly refund payments and respond to disputes</div>
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
                                                                <input class="form-check-input me-3" name="user_role" type="radio" value="4" id="kt_modal_update_role_option_4" />
                                                                <!--end::Input-->
                                                                <!--begin::Label-->
                                                                <label class="form-check-label" for="kt_modal_update_role_option_4">
                                                                    <div class="fw-bold text-gray-800">Trial</div>
                                                                    <div class="text-gray-600">Best for people who need to preview content data, but don't need to make any updates</div>
                                                                </label>
                                                                <!--end::Label-->
                                                            </div>
                                                            <!--end::Radio-->
                                                        </div>
                                                        <!--end::Input row-->
                                                        <!--end::Roles-->
                                                    </div>
                                                    <!--end::Input group-->
                                                </div>
                                                <!--end::Scroll-->
                                                <!--begin::Actions-->
                                                <div class="text-center pt-15">
                                                    <button type="reset" class="btn btn-light me-3" data-kt-users-modal-action="cancel">انصراف</button>
                                                    <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
                                                        <span class="indicator-label">ثبت</span>
                                                        <span class="indicator-progress">لطفا منتظر بمانید...
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                                    </button>
                                                </div>
                                                <!--end::Actions-->
                                            </form>
                                            <!--end::Form-->
                                        </div>
                                        <!--end::Modal body-->
                                    </div>
                                    <!--end::Modal content-->
                                </div>
                                <!--end::Modal dialog-->
                            </div>
                            <!--end::Modal - Add task-->
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body py-4">
                        <!--begin::Table-->
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
                            <!--begin::Table head-->
                            <thead>
                                <!--begin::Table row-->
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="text-start w-10px pe-2">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                                        </div>
                                    </th>
                                    <th class="text-start px-2 min-w-125px">کاربر</th>
                                    <th class="text-start px-2 min-w-125px">نقش</th>
                                    <th class="text-start px-2 min-w-125px">آخرین بازدید</th>
                                    <th class="text-start px-2 min-w-125px">کیف پول</th>
                                    <th class="text-start px-2 min-w-125px">تاریخ ثبت نام</th>
                                    <th class="text-start px-2 min-w-100px">عملیات</th>
                                </tr>
                                <!--end::Table row-->
                            </thead>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <tbody class="text-gray-600 fw-semibold">
                                @foreach ($users as $user)
                                    <!--begin::Table row-->
                                    <tr>
                                        <!--begin::Checkbox-->
                                        <td>
                                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                <input class="form-check-input" type="checkbox" value="1" />
                                            </div>
                                        </td>
                                        <!--end::Checkbox-->
                                        <!--begin::User=-->
                                        <td class="d-flex align-items-center">
                                            <!--begin:: Avatar -->
                                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                <a href="{{ route('admin-show-user', [$user->id]) }}">
                                                    <div class="symbol-label">
                                                        <img src="{{ $user->profile_pic }}" alt="{{ $user->username }}" class="w-100" />
                                                    </div>
                                                </a>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::User details-->
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('admin-show-user', [$user->id]) }}" class="text-gray-800 text-hover-primary mb-1">{{ $user->first_name.' '.$user->last_name }}</a>
                                                <span>{{ $user->email }}</span>
                                            </div>
                                            <!--begin::User details-->
                                        </td>
                                        <!--end::User=-->
                                        <!--begin::Role=-->
                                        <td>{{ $user->role }}</td>
                                        <!--end::Role=-->
                                        <!--begin::Last login=-->
                                        <td>
                                            <div class="badge badge-{{ $user->last_seen >= now() ? 'success' : 'light' }} fw-bold">{{ $user->last_seen >= now() ? 'آنلاین' : jdate($user->last_seen)->ago() }}</div>
                                        </td>
                                        <!--end::Last login=-->
                                        <!--begin::Two step=-->
                                        <td>
                                            <span class="">
                                                {{ number_format($user->wallet_balance, 0, '.' , ',') }}
                                            </span>
                                            <svg class="mr-1" width="12" height="18" viewBox="0 0 20 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M1.39223 9.60081C2.01809 9.60081 2.46646 9.45602 2.73736 9.16644C3.00825 8.88621 3.16705 8.54059 3.21376 8.12958H2.55521C2.06947 8.12958 1.67247 8.0782 1.36421 7.97545C1.05595 7.8727 0.813083 7.71857 0.635601 7.51306C0.45812 7.30756 0.332014 7.06002 0.257285 6.77044C0.191897 6.47153 0.159203 6.13057 0.159203 5.74759C0.159203 5.38328 0.21058 5.03766 0.313332 4.71072C0.416085 4.37444 0.565543 4.08486 0.761707 3.84199C0.967212 3.59912 1.21942 3.40763 1.51834 3.26751C1.8266 3.11806 2.18156 3.04333 2.58323 3.04333C2.90083 3.04333 3.19974 3.0947 3.47998 3.19746C3.76955 3.30021 4.02176 3.46368 4.23661 3.68787C4.45146 3.91205 4.6196 4.2063 4.74103 4.5706C4.87181 4.93491 4.9372 5.37861 4.9372 5.90172V6.20997H5.94604C6.15154 6.20997 6.2543 6.51823 6.2543 7.13475C6.2543 7.79797 6.15154 8.12958 5.94604 8.12958H4.92318C4.89516 8.58729 4.79708 9.02166 4.62894 9.43267C4.4608 9.84368 4.22727 10.2033 3.92835 10.5116C3.63878 10.8198 3.28381 11.0627 2.86346 11.2402C2.44311 11.427 1.97606 11.5204 1.46229 11.5204H0.0891448L0.0050746 9.60081H1.39223ZM1.85462 5.60747C1.85462 5.83166 1.90133 5.99046 1.99474 6.08387C2.09749 6.16794 2.28431 6.20997 2.55521 6.20997H3.24178V5.81765C3.24178 5.46268 3.18106 5.21981 3.05963 5.08904C2.94753 4.95826 2.77005 4.89287 2.52718 4.89287C2.07881 4.89287 1.85462 5.13107 1.85462 5.60747ZM8.2548 6.20997C8.37623 6.20997 8.45563 6.2847 8.493 6.43416C8.5397 6.58362 8.56305 6.81715 8.56305 7.13475C8.56305 7.48037 8.5397 7.73258 8.493 7.89138C8.45563 8.05018 8.37623 8.12958 8.2548 8.12958H5.94286C5.82143 8.12958 5.74203 8.05485 5.70467 7.90539C5.65796 7.74659 5.63461 7.51306 5.63461 7.2048C5.63461 6.84984 5.65796 6.59763 5.70467 6.44817C5.74203 6.28937 5.82143 6.20997 5.94286 6.20997H8.2548ZM10.5673 6.20997C10.6887 6.20997 10.7681 6.2847 10.8055 6.43416C10.8522 6.58362 10.8755 6.81715 10.8755 7.13475C10.8755 7.48037 10.8522 7.73258 10.8055 7.89138C10.7681 8.05018 10.6887 8.12958 10.5673 8.12958H8.25534C8.13391 8.12958 8.05451 8.05485 8.01715 7.90539C7.97044 7.74659 7.94709 7.51306 7.94709 7.2048C7.94709 6.84984 7.97044 6.59763 8.01715 6.44817C8.05451 6.28937 8.13391 6.20997 8.25534 6.20997H10.5673ZM12.8798 6.20997C13.0012 6.20997 13.0806 6.2847 13.118 6.43416C13.1647 6.58362 13.188 6.81715 13.188 7.13475C13.188 7.48037 13.1647 7.73258 13.118 7.89138C13.0806 8.05018 13.0012 8.12958 12.8798 8.12958H10.5678C10.4464 8.12958 10.367 8.05485 10.3296 7.90539C10.2829 7.74659 10.2596 7.51306 10.2596 7.2048C10.2596 6.84984 10.2829 6.59763 10.3296 6.44817C10.367 6.28937 10.4464 6.20997 10.5678 6.20997H12.8798ZM15.1922 6.20997C15.3137 6.20997 15.3931 6.2847 15.4304 6.43416C15.4771 6.58362 15.5005 6.81715 15.5005 7.13475C15.5005 7.48037 15.4771 7.73258 15.4304 7.89138C15.3931 8.05018 15.3137 8.12958 15.1922 8.12958H12.8803C12.7589 8.12958 12.6795 8.05485 12.6421 7.90539C12.5954 7.74659 12.572 7.51306 12.572 7.2048C12.572 6.84984 12.5954 6.59763 12.6421 6.44817C12.6795 6.28937 12.7589 6.20997 12.8803 6.20997H15.1922ZM16.5099 6.20997C16.7808 6.20997 16.9723 6.14926 17.0844 6.02782C17.2058 5.89705 17.2665 5.69621 17.2665 5.42532V3.9681H19.046V5.57945C19.046 6.42949 18.8405 7.06936 18.4295 7.49905C18.0278 7.9194 17.4393 8.12958 16.664 8.12958H15.1928C15.0713 8.12958 14.9919 8.05485 14.9546 7.90539C14.9079 7.74659 14.8845 7.51306 14.8845 7.2048C14.8845 6.84984 14.9079 6.59763 14.9546 6.44817C14.9919 6.28937 15.0713 6.20997 15.1928 6.20997H16.5099ZM19.032 2.42681H17.3366V0.801454H19.032V2.42681ZM16.8742 2.42681H15.1788V0.801454H16.8742V2.42681ZM8.94465 19.7372C8.94465 20.279 8.86058 20.7788 8.69244 21.2365C8.53364 21.7036 8.30011 22.1052 7.99186 22.4415C7.6836 22.7778 7.30995 23.0393 6.87092 23.2262C6.43189 23.4223 5.94148 23.5204 5.39969 23.5204H4.47492C3.42871 23.5204 2.61603 23.1981 2.03688 22.5536C1.45773 21.9091 1.16815 21.0263 1.16815 19.9054V17.3553H2.93363V19.8213C2.93363 20.0922 2.96165 20.3351 3.0177 20.5499C3.07375 20.7741 3.16716 20.9609 3.29793 21.1104C3.43805 21.2692 3.6202 21.3906 3.84439 21.4747C4.07792 21.5588 4.36749 21.6008 4.71312 21.6008H5.32963C5.69394 21.6008 5.99285 21.5494 6.22638 21.4467C6.46925 21.3533 6.66074 21.2178 6.80086 21.0403C6.94098 20.8722 7.03906 20.676 7.09511 20.4518C7.15115 20.2277 7.17918 19.9895 7.17918 19.7372V15.9681H8.94465V19.7372ZM5.70795 15.814H3.8584V14.1186H5.70795V15.814ZM12.4954 20.1296C12.2245 20.1296 11.9676 20.0969 11.7248 20.0315C11.4819 19.9568 11.2671 19.8353 11.0802 19.6672C10.9028 19.4991 10.758 19.2795 10.6459 19.0086C10.5431 18.7284 10.4917 18.3828 10.4917 17.9718V11.4423H12.2712V17.5094C12.2712 17.9764 12.4767 18.21 12.8877 18.21H13.2661C13.4716 18.21 13.5743 18.5182 13.5743 19.1347C13.5743 19.798 13.4716 20.1296 13.2661 20.1296H12.4954ZM13.3335 18.21C13.6137 18.21 13.8379 18.1633 14.0061 18.0699C14.1742 17.9671 14.2583 17.7803 14.2583 17.5094V17.3553C14.2583 16.991 14.3143 16.6547 14.4264 16.3464C14.5385 16.0288 14.6973 15.7579 14.9028 15.5337C15.1083 15.3095 15.3605 15.1321 15.6594 15.0013C15.9584 14.8705 16.29 14.8051 16.6543 14.8051C17.0373 14.8051 17.3782 14.8705 17.6771 15.0013C17.976 15.1321 18.2236 15.3142 18.4197 15.5477C18.6252 15.7719 18.7794 16.0475 18.8821 16.3744C18.9942 16.692 19.0503 17.0423 19.0503 17.4253C19.0503 18.2847 18.8308 18.9526 18.3917 19.429C17.962 19.896 17.3829 20.1296 16.6543 20.1296C16.29 20.1296 15.935 20.0548 15.5894 19.9054C15.2531 19.7559 15.0056 19.5458 14.8468 19.2749C14.6693 19.5925 14.4404 19.8166 14.1602 19.9474C13.8799 20.0689 13.6044 20.1296 13.3335 20.1296H13.2634C13.142 20.1296 13.0626 20.0548 13.0252 19.9054C12.9785 19.7466 12.9552 19.5131 12.9552 19.2048C12.9552 18.8498 12.9785 18.5976 13.0252 18.4482C13.0626 18.2894 13.142 18.21 13.2634 18.21H13.3335ZM17.3408 17.5094C17.3408 17.3132 17.2941 17.1357 17.2007 16.9769C17.1073 16.8181 16.9252 16.7387 16.6543 16.7387C16.3834 16.7387 16.2012 16.8181 16.1078 16.9769C16.0144 17.1357 15.9677 17.3132 15.9677 17.5094C15.9677 17.9764 16.1966 18.21 16.6543 18.21C17.112 18.21 17.3408 17.9764 17.3408 17.5094Z" fill="currentColor"></path>
                                            </svg>
                                        </td>
                                        <!--end::Two step=-->
                                        <!--begin::Joined-->
                                        <td>{{ jdate($user->created_at) }}</td>
                                        <!--begin::Joined-->
                                        <!--begin::Action=-->
                                        <td class="">
                                            <a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">عملیات
                                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr072.svg-->
                                            <span class="svg-icon svg-icon-5 m-0">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="currentColor" />
                                                </svg>
                                            </span>
                                            <!--end::Svg Icon--></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/user-management/users/view.html" class="menu-link px-3">ویرایش</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3 text-danger" data-kt-users-table-filter="delete_row">حذف</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                        <!--end::Action=-->
                                    </tr>
                                    <!--end::Table row-->
                                @endforeach
                            </tbody>
                            <!--end::Table body-->
                        </table>
                        <!--end::Table-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Content container-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Content wrapper-->

@endsection

@section('vendors-script')

	<script src="/admin/assets/plugins/custom/datatables/datatables.bundle.js"></script>

@endsection

@section('custom-script')

	<script src="/admin/assets/js/custom/apps/user-management/users/list/table.js"></script>
	<script src="/admin/assets/js/custom/apps/user-management/users/list/export-users.js"></script>
	<script src="/admin/assets/js/custom/apps/user-management/users/list/add.js"></script>
	<script src="/admin/assets/js/widgets.bundle.js"></script>
	<script src="/admin/assets/js/custom/widgets.js"></script>
	<script src="/admin/assets/js/custom/apps/chat/chat.js"></script>
	<script src="/admin/assets/js/custom/utilities/modals/upgrade-plan.js"></script>
	<script src="/admin/assets/js/custom/utilities/modals/create-app.js"></script>
	<script src="/admin/assets/js/custom/utilities/modals/users-search.js"></script>

@endsection
