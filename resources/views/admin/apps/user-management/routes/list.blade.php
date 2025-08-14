@extends('admin.layouts.master')

@section('title', 'لیست روت ها')

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
					<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">لیست روت ها</h1>
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
						<li class="breadcrumb-item text-muted">
							<a href="{{ route('admin-routes-list') }}" class="text-muted text-hover-primary">مدیریت روت ها</a>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item">
							<span class="bullet bg-gray-400 w-5px h-2px"></span>
						</li>
						<!--end::Item-->
						<!--begin::Item-->
						<li class="breadcrumb-item text-muted">لیست روت ها</li>
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
				<div class="card card-flush">
					<!--begin::Card header-->
					<div class="card-header mt-6">
						<!--begin::Card title-->
						<div class="card-title">
							<!--begin::Search-->
							<div class="d-flex align-items-center position-relative my-1 me-5">
								<!--begin::Svg Icon | path: icons/duotune/general/gen021.svg-->
								<span class="svg-icon svg-icon-1 position-absolute ms-6">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
										<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
									</svg>
								</span>
								<!--end::Svg Icon-->
								<input type="text" data-kt-routes-table-filter="search" class="form-control form-control-solid w-250px ps-15" placeholder="جستجو روت" />
							</div>
							<!--end::Search-->
						</div>
						<!--end::Card title-->
						<!--begin::Card toolbar-->
						<div class="card-toolbar">
							<!--begin::Button-->
							{{--  <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_route">
							<!--begin::Svg Icon | path: icons/duotune/general/gen035.svg-->
								<span class="svg-icon svg-icon-3">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect opacity="0.3" x="2" y="2" width="20" height="20" rx="5" fill="currentColor" />
										<rect x="10.8891" y="17.8033" width="12" height="2" rx="1" transform="rotate(-90 10.8891 17.8033)" fill="currentColor" />
										<rect x="6.01041" y="10.9247" width="12" height="2" rx="1" fill="currentColor" />
									</svg>
								</span>
							<!--end::Svg Icon-->
								ایجاد روت
							</button>  --}}
							<!--end::Button-->
						</div>
						<!--end::Card toolbar-->
					</div>
					<!--end::Card header-->
					<!--begin::Card body-->
					<div class="card-body overflow-auto pt-0">
						<!--begin::Table-->
						<table id="kt_routes_table" class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="kt_routes_table">
							<!--begin::Table head-->
							<thead>
								<!--begin::Table row-->
								<tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
									<th class="text-start min-w-25px">#</th>
									<th class="text-center min-w-75px">دسترسی‌ها</th>
									<th class="text-start min-w-150px">نام</th>
									<th class="text-center min-w-50px">متد</th>
									<th class="text-start min-w-100px">URL</th>
									<th class="text-start min-w-60px">Action</th>
								</tr>
								<!--end::Table row-->
							</thead>
							<!--end::Table head-->
							<!--begin::Table body-->
							<tbody class="fw-semibold text-gray-600">
								@foreach ($routes as $route)
								<tr>
                                    <!--begin::Number=-->
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <!--end::Number=-->
                                    <!--begin::Permission=-->
                                    <td>
                                        <!--begin::Permission-->
										<div class="m-0">
											<!--begin::Permission toggle-->
											<a href="#" class="btn btn-sm btn-light-dark text-nowrap" data-kt-menu-trigger="click" data-kt-menu-placement="top-end">
                                                <!--begin::Svg Icon | path: icons/duotune/general/gen051.svg-->
                                                <span class="svg-icon svg-icon-2">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path opacity="0.3" d="M20.5543 4.37824L12.1798 2.02473C12.0626 1.99176 11.9376 1.99176 11.8203 2.02473L3.44572 4.37824C3.18118 4.45258 3 4.6807 3 4.93945V13.569C3 14.6914 3.48509 15.8404 4.4417 16.984C5.17231 17.8575 6.18314 18.7345 7.446 19.5909C9.56752 21.0295 11.6566 21.912 11.7445 21.9488C11.8258 21.9829 11.9129 22 12.0001 22C12.0872 22 12.1744 21.983 12.2557 21.9488C12.3435 21.912 14.4326 21.0295 16.5541 19.5909C17.8169 18.7345 18.8277 17.8575 19.5584 16.984C20.515 15.8404 21 14.6914 21 13.569V4.93945C21 4.6807 20.8189 4.45258 20.5543 4.37824Z" fill="currentColor"/>
                                                        <path d="M14.854 11.321C14.7568 11.2282 14.6388 11.1818 14.4998 11.1818H14.3333V10.2272C14.3333 9.61741 14.1041 9.09378 13.6458 8.65628C13.1875 8.21876 12.639 8 12 8C11.361 8 10.8124 8.21876 10.3541 8.65626C9.89574 9.09378 9.66663 9.61739 9.66663 10.2272V11.1818H9.49999C9.36115 11.1818 9.24306 11.2282 9.14583 11.321C9.0486 11.4138 9 11.5265 9 11.6591V14.5227C9 14.6553 9.04862 14.768 9.14583 14.8609C9.24306 14.9536 9.36115 15 9.49999 15H14.5C14.6389 15 14.7569 14.9536 14.8542 14.8609C14.9513 14.768 15 14.6553 15 14.5227V11.6591C15.0001 11.5265 14.9513 11.4138 14.854 11.321ZM13.3333 11.1818H10.6666V10.2272C10.6666 9.87594 10.7969 9.57597 11.0573 9.32743C11.3177 9.07886 11.6319 8.9546 12 8.9546C12.3681 8.9546 12.6823 9.07884 12.9427 9.32743C13.2031 9.57595 13.3333 9.87594 13.3333 10.2272V11.1818Z" fill="currentColor"/>
                                                    </svg>
                                                </span>
                                                <!--begin::Svg Icon -->
                                                دسترسی‌ها
                                            </a>
											<!--end::Permission toggle-->
											<!--begin::Permissions form-->
											<div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="kt_menu_633e6dfc67903">
												<!--begin::Header-->
												<div class="px-7 py-5">
													<div class="fs-5 text-dark fw-bold">لیست دسترسی‌ها</div>
												</div>
												<!--end::Header-->
												<!--begin::Menu separator-->
												<div class="separator border-gray-200"></div>
												<!--end::Menu separator-->
												<!--begin::Form-->
												<form action="{{ route('admin-route-sync-permission') }}" method="POST" data-kt-routes-table-filter="sync_permission">
                                                    @csrf
                                                    <input type="hidden" name="route_name" value="{{ $route->getName() }}"/>
                                                    <div class="px-7 py-5">
                                                        <!--begin::Input group-->
                                                        <div class="mb-10 ">
                                                            <!--begin::Options-->
                                                            <div class="d-flex flex-column h-200px overflow-auto">
                                                                @foreach (\App\Models\Permission::all() as $permission)
                                                                <!--begin::Options-->
                                                                <label class="form-check form-check-sm form-check-custom form-check-solid my-2 me-5">
                                                                    <input name="permission[]" class="form-check-input" type="checkbox" @if(\App\Models\PermissionRoute::isUsedPermissionByRoute($permission->id, $route->getName())) checked="checked" @endif value="{{ $permission->id }}" />
                                                                    <span class="form-check-label text-nowrap">
                                                                        {{ $permission->name }}
                                                                        <i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="{{ $permission->label }}"></i>
                                                                    </span>
                                                                </label>
                                                                <!--end::Options-->
                                                                @endforeach
                                                            </div>
                                                            <!--end::Options-->
                                                        </div>
                                                        <!--end::Input group-->
                                                        <!--begin::Actions-->
                                                        <div class="d-flex justify-content-end">
                                                            <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true">انصراف</button>
                                                            <button type="submit" class="btn btn-sm btn-primary" data-kt-routes-form-action="submit">
                                                                <span class="indicator-label">ثبت</span>
                                                                <span class="indicator-progress">لطفا منتظر بمانید...
                                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                                            </button>
                                                        </div>
                                                        <!--end::Actions-->
                                                    </div>
                                                </form>
												<!--end::Form-->
											</div>
											<!--end::Permissions form-->
										</div>
										<!--end::Permission-->

                                    </td>
                                    <!--end::Permission=-->
									<!--begin::Name=-->
									<td class="text-nowrap">
										{{ $route->getName() }}
										<i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="{{ env('APP_URL').'/'.$route->uri() }}"></i>
									</td>
									<!--end::Name=-->
									<!--begin::URL=-->
									<td>
                                        <div class="badge badge-light-info fs-7">
                                            {{ $route->methods()[0] }}
                                        </div>
									</td>
									<!--end::URL=-->
									<!--begin::Method-->
									<td class="text-nowrap">
                                        <div class="badge badge-light-primary fs-7">
                                            {{ env('APP_URL').'/'.$route->uri() }}
                                        </div>
                                    </td>
									<!--end::Method-->
									<!--begin::Action=-->
									<td class="">
										{{ $route->getActionName() }}
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
				<!--begin::Modals-->

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
	<script src="/admin/assets/js/custom/apps/user-management/routes/list.js"></script>
	<script src="/admin/assets/js/custom/apps/user-management/routes/sync-permission.js"></script>
	<script src="/admin/assets/js/widgets.bundle.js"></script>
	<script src="/admin/assets/js/custom/widgets.js"></script>
@endsection










