@extends('admin.layouts.master')
@section('title', 'ویرایش دسترسی ')
@section('head')
	<link href="/admin/assets/plugins/custom/datatables/datatables.bundle.rtl.css" rel="stylesheet" type="text/css" />
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
					<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">ویرایش دسترسی {{ $permission->name }}</h1>
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
						<li class="breadcrumb-item text-muted">مدیریت دسترسی ها</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">ویرایش دسترسی</li>
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
				<!--begin::Layout-->
				<div class="d-flex flex-column flex-lg-row">
					<!--begin::Sidebar-->
					<div class="flex-column flex-lg-row-auto w-100 w-lg-200px w-xl-300px mb-10">
						<!--begin::Card-->
						<div class="card card-flush">
							<!--begin::Card header-->
							<div class="card-header">
								<!--begin::Card title-->
								<div class="card-title">
									<h2 class="mb-0">{{ $permission->name }}</h2>
								</div>
								<!--end::Card title-->
							</div>
							<!--end::Card header-->
							<!--begin::Card body-->
							<div class="card-body pt-0">
								<!--begin::Permissions-->
								<div class="d-flex flex-column text-gray-600">
									<div class="d-flex align-items-center py-2">
									<span class="bullet bg-primary me-3"></span>
									{{ $permission->label }}
								</div>
								</div>
								<!--end::Permissions-->
							</div>
							<!--end::Card body-->
							<!--begin::Card footer-->
							<div class="card-footer pt-0">
								<button type="button" class="btn btn-light btn-active-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_update_permission">ویرایش دسترسی</button>
							</div>
							<!--end::Card footer-->
						</div>
						<!--end::Card-->
						<!--begin::Modal-->
						<!--begin::Modal - Update permission-->
						<div class="modal fade" id="kt_modal_update_permission" tabindex="-1" aria-hidden="true">
							<!--begin::Modal dialog-->
							<div class="modal-dialog modal-dialog-centered mw-650px">
								<!--begin::Modal content-->
								<div class="modal-content">
									<!--begin::Modal header-->
									<div class="modal-header">
										<!--begin::Modal title-->
										<h2 class="fw-bold">ویرایش دسترسی</h2>
										<!--end::Modal title-->
										<!--begin::Close-->
										<div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-permissions-modal-action="close">
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
										<!--begin::Notice-->
										<!--begin::Notice-->
										<div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
											<!--begin::Icon-->
											<!--begin::Svg Icon | path: icons/duotune/general/gen044.svg-->
											<span class="svg-icon svg-icon-2tx svg-icon-warning me-4">
												<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
													<rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor" />
													<rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="currentColor" />
													<rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="currentColor" />
												</svg>
											</span>
											<!--end::Svg Icon-->
											<!--end::Icon-->
											<!--begin::Wrapper-->
											<div class="d-flex flex-stack flex-grow-1">
												<!--begin::Content-->
												<div class="fw-semibold">
													<div class="fs-6 text-gray-700">
													<strong class="me-1">هشدار!</strong>با ویرایش نام دسترسی، ممکن است عملکرد دسترسی های سیستم را خراب کنید. لطفاً قبل از ادامه مطمئن شوید که کاملاً مطمئن هستید.</div>
												</div>
												<!--end::Content-->
											</div>
											<!--end::Wrapper-->
										</div>
										<!--end::Notice-->
										<!--end::Notice-->
										<!--begin::Form-->
										<form id="kt_modal_update_permission_form" class="form" method="POST" action="{{ route('admin-edit-permission', $permission->id) }}">
											@csrf
											@method('patch')
											<!--begin::Input group-->
											<div class="fv-row mb-7">
												<!--begin::Label-->
												<label class="fs-6 fw-semibold form-label mb-2">
													<span class="required">نام دسترسی</span>
													<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="لطفا یک نام یکتا انتخاب کنید، نام انتخابی باید لاتین باشد و به جای فاصله از علامت (-) استفاده کنید."></i>
												</label>
												<!--end::Label-->
												<!--begin::Input-->
												<input class="form-control form-control-solid" placeholder="نام دسترسی را وارد کنید" name="name" value="{{ $permission->name }}"/>
												<div class="fv-plugins-message-container invalid-feedback error_text name_error"><div data-field="name"></div></div>
												<!--end::Input-->
											</div>
											<!--end::Input group-->
											<!--begin::Input group-->
											<div class="fv-row mb-7">
												<!--begin::Label-->
												<label class="fs-6 fw-semibold form-label mb-2">
													<span class="required">توضیح دسترسی</span>
													<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="لطفا یک توضیح فارسی برای دسترسی وارد کنید، این توضیح به عنوان نام فارسی نمایش داده میشود."></i>
												</label>
												<!--end::Label-->
												<!--begin::Input-->
												<input class="form-control form-control-solid" placeholder="توضیح دسترسی را وارد کنید" name="label" value="{{ $permission->label }}"/>
												<div class="fv-plugins-message-container invalid-feedback error_text label_error"><div data-field="label"></div></div>
												<!--end::Input-->
											</div>
											<!--end::Input group-->
											<!--begin::Actions-->
											<div class="text-center pt-15">
												<button type="reset" class="btn btn-light me-3" data-kt-permissions-modal-action="cancel">لغو</button>
												<button type="submit" class="btn btn-primary" data-kt-permissions-modal-action="submit">
													<span class="indicator-label">ویرایش</span>
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
						<!--end::Modal - Update permission-->
						<!--end::Modal-->
					</div>
					<!--end::Sidebar-->
					<!--begin::Content-->
					<div class="flex-lg-row-fluid ms-lg-10">
						<!--begin::Card-->
						<div class="card card-flush mb-6 mb-xl-9">
							<!--begin::Card header-->
							<div class="card-header pt-5">
								<!--begin::Card title-->
								<div class="card-title">
									<h2 class="d-flex align-items-center">کاربرانی که این دسترسی را دارند 
									<span class="text-gray-600 fs-6 ms-1">({{ $permission->users->count() }})</span></h2>
								</div>
								<!--end::Card title-->
								<!--begin::Card toolbar-->
								<div class="card-toolbar">
									<!--begin::Search-->
									<div class="d-flex align-items-center position-relative my-1" data-kt-view-permissions-table-toolbar="base">
										<!--begin::Svg Icon | path: icons/duotune/general/gen021.svg-->
										<span class="svg-icon svg-icon-1 position-absolute ms-6">
											<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
												<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
												<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
											</svg>
										</span>
										<!--end::Svg Icon-->
										<input type="text" data-kt-permissions-table-filter="search" class="form-control form-control-solid w-250px ps-15" placeholder="جستجوی کاربر" />
									</div>
									<!--end::Search-->
									<!--begin::Group actions-->
									<div class="d-flex justify-content-end align-items-center d-none" data-kt-view-permissions-table-toolbar="selected">
										<div class="fw-bold me-5">
										<span class="me-2" data-kt-view-permissions-table-select="selected_count"></span>انتخاب شده</div>
										<form action="{{ route('admin-permission-detach-user', $permission->id) }}" method="POST">
											@csrf
											@method('delete')
											<button type="button" class="btn btn-danger" data-kt-view-permissions-table-select="delete_selected">حذف موارد</button>
										</form>
									</div>
									<!--end::Group actions-->
								</div>
								<!--end::Card toolbar-->
							</div>
							<!--end::Card header-->
							<!--begin::Card body-->
							<div class="card-body pt-0">
								<!--begin::Table-->
								<table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_permissions_view_table">
									<!--begin::Table head-->
									<thead>
										<!--begin::Table row-->
										<tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
											<th class="w-10px pe-2">
												<div class="form-check form-check-sm form-check-custom form-check-solid me-3">
													<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_permissions_view_table .form-check-input" value="1" />
												</div>
											</th>
											
											<th class="text-start min-w-150px">کاربر</th>
											<th class="text-start min-w-125px">تاریخ ثبت نام</th>
											<th class="text-end min-w-100px">اقدامات</th>
										</tr>
										<!--end::Table row-->
									</thead>
									<!--end::Table head-->
									<!--begin::Table body-->
									<tbody class="fw-semibold text-gray-600">
										@foreach ($permission->users as $user)
											<tr>
												<!--begin::Checkbox-->
												<td>
													<div class="form-check form-check-sm form-check-custom form-check-solid">
														<input class="form-check-input" type="checkbox" value="{{ $user->id }}" />
													</div>
												</td>
												<!--end::Checkbox-->
												
												<!--begin::User=-->
												<td class="d-flex align-items-center">
													<!--begin:: Avatar -->
													<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
														<a href="{{ route('admin-show-user', $user->id) }}">
															<div class="symbol-label">
																<img src="{{ $user->profile_pic }}" alt="{{ $user->username }}" class="w-100" />
															</div>
														</a>
													</div>
													<!--end::Avatar-->
													<!--begin::User details-->
													<div class="d-flex flex-column">
														<a href="{{ route('admin-show-user', $user->id) }}" class="text-gray-800 text-hover-primary mb-1">{{ $user->first_name.' '.$user->last_name }}</a>
														<span>{{ $user->email }}</span>
													</div>
													<!--begin::User details-->
												</td>
												<!--end::user=-->
												<!--begin::Joined date=-->
												<td>{{ jdate($user->created_at) }}</td>
												<!--end::Joined date=-->
												<!--begin::Action=-->
												<td class="text-end">
													<a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">عملیات
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
															<a href="{{ route('admin-show-user', $user->id) }}" class="menu-link px-3">مشاهده</a>
														</div>
														<!--end::Menu item-->
														<!--begin::Menu item-->
														<form data-kt-permissions-table-filter="delete_row" action="{{ route('admin-permission-detach-user', $permission->id) }}" method="POST" class="menu-item px-3 detachPermission">
															@csrf
															@method('delete')
															<input type="hidden" name="user_id" value="{{ $user->id }}" />
															<button class="btn btn-sm btn-active-light-primary menu-link w-100 px-3">
																حذف
															</button>
														</form>
														<!--end::Menu item-->
													</div>
													<!--end::Menu-->
												</td>
												<!--end::Action=-->
											</tr>
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
					<!--end::Content-->
				</div>
				<!--end::Layout-->
			</div>
			<!--end::Content container-->
		</div>
		<!--end::Content-->
	</div>
@endsection
@section('vendors-script')
	<script src="/admin/assets/plugins/custom/datatables/datatables.bundle.js"></script>
@endsection
@section('custom-script')
	<script src="/admin/assets/js/custom/apps/user-management/permissions/view.js"></script>
	<script src="/admin/assets/js/custom/apps/user-management/permissions/update-permission.js"></script>
	<script src="/admin/assets/js/widgets.bundle.js"></script>
	<script src="/admin/assets/js/custom/widgets.js"></script>
	<script src="/admin/assets/js/custom/apps/chat/chat.js"></script>
	<script src="/admin/assets/js/custom/utilities/modals/upgrade-plan.js"></script>
	<script src="/admin/assets/js/custom/utilities/modals/create-app.js"></script>
	<script src="/admin/assets/js/custom/utilities/modals/users-search.js"></script>
@endsection