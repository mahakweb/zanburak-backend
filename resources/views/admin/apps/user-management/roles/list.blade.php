@extends('admin.layouts.master')
@section('title', 'لیست گروه ها')
@section('head')
	<link href="/admin/assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
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
					<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">لیست گروه ها</h1>
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
						<li class="breadcrumb-item text-muted">مدیریت گروه ها</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">لیست گروه ها</li>
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
				<!--begin::Row-->
				<div id="kt_roles_table" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9">
					@foreach ($roles as $role)
					<!--begin::Col-->
					<div class="col-md-4">
						<!--begin::Card-->
						<div class="card card-flush h-md-100">
							<!--begin::Card header-->
							<div class="card-header">
								<!--begin::Card title-->
								<div class="card-title">
									<h2>{{ $role->name }}</h2>
								</div>
								<!--end::Card title-->
							</div>
							<!--end::Card header-->
							<!--begin::Card body-->
							<div class="card-body pt-1">
								<!--begin::Users-->
								<div class="fw-bold text-gray-600 mb-5">تعداد کاربران با این گروه: {{ $role->users->count() }}</div>
								<!--end::Users-->
								<!--begin::Permissions-->
								<div class="d-flex flex-column text-gray-600">
									@foreach ($role->permissions()->limit(4)->get() as $permission)
										<div class="d-flex align-items-center py-2">
											<span class="bullet bg-primary me-3"></span>
											{{ $permission->name }}
										</div>
									@endforeach
									@if ($role->permissions->count() > 4)
										<div class='d-flex align-items-center py-2'>
											<span class='bullet bg-primary me-3'></span>
											<em>و {{ $role->permissions->count() - 4 }} مورد دیگر...</em>
										</div>
									@endif
								</div>
								<!--end::Permissions-->
							</div>
							<!--end::Card body-->
							<!--begin::Card footer-->
							<div class="card-footer flex-wrap pt-0 d-flex">
								<a href="{{ route('admin-edit-role', $role->id) }}" class="btn btn-light btn-active-primary my-1 me-2">مشاهده و ویرایش</a>
								<form class="delete-role " action="{{ route('admin-delete-role', $role->id) }}" method="POST" data-kt-roles-table-filter="delete_row">
									@csrf
									@method('DELETE')
									<button type="submit" class="btn btn-light btn-active-light-danger my-1">حذف</button>
								</form>
							</div>
							<!--end::Card footer-->
						</div>
						<!--end::Card-->
					</div>
					@endforeach
					<!--end::Col-->
					<!--begin::Add new card-->
					<div class="ol-md-4">
						<!--begin::Card-->
						<div class="card h-md-100">
							<!--begin::Card body-->
							<div class="card-body d-flex flex-center">
								<!--begin::Button-->
								<button type="button" class="btn btn-clear d-flex flex-column flex-center" data-bs-toggle="modal" data-bs-target="#kt_modal_add_role">
									<!--begin::Illustration-->
									<img src="/admin/assets/media/illustrations/sketchy-1/4.png" alt="" class="mw-100 mh-150px mb-7" />
									<!--end::Illustration-->
									<!--begin::Label-->
									<div class="fw-bold fs-3 text-gray-600 text-hover-primary">ایجاد گروه جدید</div>
									<!--end::Label-->
								</button>
								<!--begin::Button-->
							</div>
							<!--begin::Card body-->
						</div>
						<!--begin::Card-->
					</div>
					<!--begin::Add new card-->
				</div>
				<!--end::Row-->
				<!--begin::Modals-->
				<!--begin::Modal - Add role-->
				<div class="modal fade" id="kt_modal_add_role" tabindex="-1" aria-hidden="true">
					<!--begin::Modal dialog-->
					<div class="modal-dialog modal-dialog-centered mw-750px">
						<!--begin::Modal content-->
						<div class="modal-content">
							<!--begin::Modal header-->
							<div class="modal-header">
								<!--begin::Modal title-->
								<h2 class="fw-bold">ایجاد گروه</h2>
								<!--end::Modal title-->
								<!--begin::Close-->
								<div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-roles-modal-action="close">
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
							<div class="modal-body scroll-y mx-lg-5 my-7">
								<!--begin::Form-->
								<form id="kt_modal_add_role_form" class="form" method="POST" action="{{ route('admin-create-role') }}">
									@csrf
									<!--begin::Scroll-->
									<div class="d-flex flex-column scroll-y me-n7 pe-7" id="kt_modal_add_role_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_role_header" data-kt-scroll-wrappers="#kt_modal_add_role_scroll" data-kt-scroll-offset="300px">
										<!--begin::Input group-->
										<div class="fv-row mb-7">
											<!--begin::Label-->
											<label class="fs-6 fw-semibold form-label mb-2">
												<span class="required">نام گروه</span>
												<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="لطفا یک نام یکتا انتخاب کنید، نام انتخابی باید لاتین باشد و به جای فاصله از علامت (-) استفاده کنید."></i>
											</label>
											<!--end::Label-->
											<!--begin::Input-->
											<input class="form-control form-control-solid" placeholder="نام گروه را وارد کنید" name="name" value=""/>
											<div class="fv-plugins-message-container invalid-feedback error_text name_error"><div data-field="name"></div></div>
											<!--end::Input-->
										</div>
										<!--end::Input group-->
										<!--begin::Input group-->
										<div class="fv-row mb-7">
											<!--begin::Label-->
											<label class="fs-6 fw-semibold form-label mb-2">
												<span class="required">توضیح گروه</span>
												<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="لطفا یک توضیح فارسی برای دسترسی وارد کنید، این توضیح به عنوان نام فارسی نمایش داده میشود."></i>
											</label>
											<!--end::Label-->
											<!--begin::Input-->
											<input class="form-control form-control-solid" placeholder="توضیح گروه را وارد کنید" name="label" value="" />
											<div class="fv-plugins-message-container invalid-feedback error_text label_error"><div data-field="label"></div></div>
											<!--end::Input-->
										</div>
										<!--end::Input group-->
										
										<!--begin::Permissions-->
										<div class="fv-row">
											<!--begin::Label-->
											<label class="fs-5 fw-bold form-label mb-2">دسترسی های این گروه</label>
											<!--end::Label-->
											
											<!--begin::Table wrapper-->
											<div class="table-responsive">
												<!--begin::Table-->
												<table class="table align-middle table-row-dashed fs-6 gy-5">
													<!--begin::Table body-->
													<tbody class="text-gray-600 fw-semibold">
														<!--begin::Table row-->
														<tr>
															<td class="text-gray-800">دسترسی مدیر
															<i class="fas fa-exclamation-circle ms-1 fs-7" data-bs-toggle="tooltip" title="اجازه دسترسی کامل به سیستم را می دهد"></i></td>
															<td>
																<!--begin::Checkbox-->
																<label class="form-check form-check-custom form-check-solid me-9">
																	<input class="form-check-input" type="checkbox" value="" id="kt_roles_select_all" />
																	<span class="form-check-label" for="kt_roles_select_all">انتخاب همه</span>
																</label>
																<!--end::Checkbox-->
															</td>
														</tr>
														<!--end::Table row-->
														@foreach (App\Models\Role::all() as $role)
															<!--begin::Table row-->
															<tr>
																<!--begin::Label-->
																<td class="text-gray-800">{{ $role->name }}</td>
																<!--end::Label-->
																<!--begin::Options-->
																<td>
																	<!--begin::Wrapper-->
																	<div class="d-flex">
																		@foreach ($role->permissions as $permission)
																			<!--begin::Checkbox-->
																			<label class="form-check form-check-sm form-check-custom form-check-solid me-5 me-lg-20">
																				<input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}"/>
																				<span class="form-check-label">{{ $permission->name }}</span>
																			</label>
																			<!--end::Checkbox-->
																		@endforeach
																	</div>
																	<!--end::Wrapper-->
																</td>
																<!--end::Options-->
															</tr>
															<!--end::Table row-->
														@endforeach
														
														<!--begin::Table row-->
															<tr>
																<!--begin::Label-->
																<td class="text-gray-800">دسترسی های اضافه</td>
																<!--end::Label-->
																<!--begin::Options-->
																<td>
																	<!--begin::Wrapper-->
																	<div class="d-flex">
																		@foreach (App\Models\Permission::whereNotIn('id', function ($q){
																			$q->select('permission_id')
																			->from('permission_role')
																			->groupBy('permission_id')
																			->havingRaw('COUNT(*) > 0');
																										})->get() as $permission)
																			<!--begin::Checkbox-->
																			<label class="form-check form-check-sm form-check-custom form-check-solid me-5 me-lg-20">
																				<input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}"/>
																				<span class="form-check-label">{{ $permission->name }}</span>
																			</label>
																			<!--end::Checkbox-->
																		@endforeach
																	</div>
																	<!--end::Wrapper-->
																</td>
																<!--end::Options-->
															</tr>
														<!--end::Table row-->





													</tbody>
													<!--end::Table body-->
												</table>
												<!--end::Table-->
											</div>
											<!--end::Table wrapper-->



											{{--  <!--begin::Select2-->
											<select class="form-select mb-2" name="permissions[]" data-control="select2" data-placeholder="انتخاب کنید" data-allow-clear="true" multiple="multiple">
												<option></option>
												@foreach (App\Models\Permission::all() as $permission)
													<option value="{{ $permission->id }}">{{ $permission->name }}</option>
												@endforeach
											</select>
											<!--end::Select2-->  --}}

										</div>
										<!--end::Permissions-->
									</div>
									<!--end::Scroll-->
									<!--begin::Actions-->
									<div class="text-center pt-15">
										<button type="reset" class="btn btn-light me-3" data-kt-roles-modal-action="cancel">لغو</button>
										<button type="submit" class="btn btn-primary" data-kt-roles-modal-action="submit">
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
				<!--end::Modal - Add role-->
				<!--begin::Modal - Update role-->
				<!--end::Modal - Update role-->
				<!--end::Modals-->
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
	<script src="/admin/assets/js/custom/apps/user-management/roles/list/add.js"></script>
	<script src="/admin/assets/js/custom/apps/user-management/roles/list/delete.js"></script>
	<script src="/admin/assets/js/widgets.bundle.js"></script>
	<script src="/admin/assets/js/custom/widgets.js"></script>
@endsection