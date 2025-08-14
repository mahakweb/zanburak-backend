@extends('admin.layouts.master')

@section('title', $course->title)

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
					<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">مشاهده اطلاعات دوره</h1>
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
						<li class="breadcrumb-item text-muted">مشاهده دوره</li>
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
				<!--begin::Navbar-->
				@include('admin.apps.courses.layouts.navbar')
				<!--end::Navbar-->
				<!--begin::Row-->
				<div id="kt_sections_table" class="row g-6 g-xl-9">
					@foreach ($sections as $section)
                        <!--begin::Col-->
                        <div class="col-lg-6">
                            <!--begin::Card-->
                            <div class="card card-flush h-lg-100">
                                <!--begin::Card header-->
                                <div class="card-header mt-6">
                                    <!--begin::Card title-->
                                    <div class="card-title flex-column">
                                        <h3 class="fw-bold mb-1">{{ $section->title }}</h3>
                                        <div class="fs-6 text-gray-400">این فصل {{ $section->episode->count() }} جلسه دارد</div>
                                    </div>
                                    <!--end::Card title-->
                                    <!--begin::Card toolbar-->
                                    <div class="card-toolbar">
                                        <a href="{{ route('admin-episode-create', ['course' => $course, 'section' => $section]) }}" target="_blank" class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                            <span class="svg-icon svg-icon-3">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="5" fill="currentColor"/>
                                                    <rect x="10.8891" y="17.8033" width="12" height="2" rx="1" transform="rotate(-90 10.8891 17.8033)" fill="currentColor"/>
                                                    <rect x="6.01041" y="10.9247" width="12" height="2" rx="1" fill="currentColor"/>
                                                </svg>
                                            </span>
                                        </a>
                                        <a href="#" class="btn btn-icon btn-active-light-warning w-30px h-30px mx-1">
                                            <span class="svg-icon svg-icon-3">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                                                    <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                                                </svg>
                                            </span>
                                        </a>
                                        <form action="{{ route('admin-section-delete', [$course->id, $section->id]) }}" method="POST" class="delete-section" data-kt-sections-table-filter="delete_row">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-icon btn-active-light-danger w-30px h-30px">
                                                <span class="svg-icon svg-icon-3">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"></path>
                                                        <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"></path>
                                                        <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"></path>
                                                    </svg>
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                    <!--end::Card toolbar-->
                                </div>
                                <!--end::Card toolbar-->
                                <!--begin::Card body-->
                                <div class="card-body d-flex flex-column p-9 pt-3 mb-9 mh-300px scroll-y">
                                    @foreach ($section->episode as $episode)
                                        <!--begin::Item-->
                                        <div class="d-flex align-items-center mb-5 episodes-table">
                                            <!--begin::Avatar-->
                                            <div class="me-5 position-relative">
                                                <!--begin::Image-->
                                                <div class="symbol symbol-35px symbol-circle">
                                                    <div class="symbol-label fs-3 bg-light-warning text-warning">{{ $loop->iteration }}</div>
                                                </div>
                                                <!--end::Image-->
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="fw-semibold">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary">{{ Str::words($episode->title, 6, '...') }}</a>
                                                <div class="text-gray-400">{{ Str::words($episode->english_title, 6, '...') }}</div>
                                            </div>
                                            <!--end::Details-->
                                            <!--begin::Badge-->
                                            <div class="ms-auto">
                                                <a href="#" class="btn btn-sm btn-light btn-active-light-primary text-nowrap" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">عملیات
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
															<a href="{{ route('episode-single', [$course->slug, $episode]) }}" class="menu-link px-3">مشاهده</a>
														</div>
														<!--end::Menu item-->
														<!--begin::Menu item-->
														<div class="menu-item px-3">
															<a href="{{ route('admin-episode-edit', ['course' => $course, 'section' => $section, 'episode' => $episode]) }}" class="menu-link px-3">ویرایش</a>
														</div>
														<!--end::Menu item-->
														<!--begin::Menu item-->
														<form action="{{ route('admin-episode-delete', [$course->id, $section->id, $episode->id]) }}" method="POST" class="menu-item px-3 delete-episode">
															@csrf
															@method('delete')
															<button class="btn btn-sm btn-active-light-primary menu-link w-100 px-3">
																حذف
															</button>
														</form>
														<!--end::Menu item-->
													</div>
													<!--end::Menu-->
                                            </div>
                                            <!--end::Badge-->
                                        </div>
                                        <!--end::Item-->
                                    @endforeach
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                        <!--end::Col-->
                    @endforeach
                    <!--begin::new Col-->
                        <div class="col-lg-6">
                            <!--begin::Card-->
                            <div class="card card-flush h-lg-100">
                                <!--begin::Card body-->
                                <div class="card-body d-flex flex-center">
                                    <!--begin::Button-->
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#kt_modal_add_section"  class="btn btn-clear d-flex flex-column flex-center" >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 800 800" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M609.48783,100.59015l-25.44631,6.56209L270.53735,187.9987,245.091,194.56079A48.17927,48.17927,0,0,0,210.508,253.17865L320.849,681.05606a48.17924,48.17924,0,0,0,58.61776,34.58317l.06572-.01695,364.26536-93.93675.06572-.01695a48.17923,48.17923,0,0,0,34.58309-58.6178l-110.341-427.87741A48.17928,48.17928,0,0,0,609.48783,100.59015Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M612.94784,114.00532l-30.13945,7.77236L278.68955,200.20385l-30.139,7.77223a34.30949,34.30949,0,0,0-24.6275,41.74308l110.341,427.87741a34.30946,34.30946,0,0,0,41.7431,24.62736l.06572-.01695,364.26536-93.93674.06619-.01707a34.30935,34.30935,0,0,0,24.627-41.7429l-110.341-427.87741A34.30938,34.30938,0,0,0,612.94784,114.00532Z" transform="translate(-208.9778 -99.05999)" fill="#fff"/><path d="M590.19,252.56327,405.917,300.08359a8.01411,8.01411,0,0,1-4.00241-15.52046l184.273-47.52033A8.01412,8.01412,0,0,1,590.19,252.56327Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M628.955,270.49906,412.671,326.27437a8.01411,8.01411,0,1,1-4.00241-15.52046l216.284-55.77531a8.01411,8.01411,0,0,1,4.00242,15.52046Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M620.45825,369.93676l-184.273,47.52032a8.01411,8.01411,0,1,1-4.00242-15.52046l184.273-47.52032a8.01411,8.01411,0,1,1,4.00241,15.52046Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M659.22329,387.87255l-216.284,55.77531a8.01411,8.01411,0,1,1-4.00242-15.52046l216.284-55.77531a8.01411,8.01411,0,0,1,4.00242,15.52046Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M650.72653,487.31025l-184.273,47.52033a8.01412,8.01412,0,0,1-4.00242-15.52047l184.273-47.52032a8.01411,8.01411,0,0,1,4.00242,15.52046Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M689.49156,505.246l-216.284,55.77532a8.01412,8.01412,0,1,1-4.00241-15.52047l216.284-55.77531a8.01411,8.01411,0,0,1,4.00242,15.52046Z" transform="translate(-208.9778 -99.05999)" fill="#f2f2f2"/><path d="M374.45884,348.80871l-65.21246,16.817a3.847,3.847,0,0,1-4.68062-2.76146L289.5963,304.81607a3.847,3.847,0,0,1,2.76145-4.68061l65.21247-16.817a3.847,3.847,0,0,1,4.68061,2.76145l14.96947,58.04817A3.847,3.847,0,0,1,374.45884,348.80871Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M404.72712,466.1822l-65.21247,16.817a3.847,3.847,0,0,1-4.68062-2.76146l-14.96946-58.04816A3.847,3.847,0,0,1,322.626,417.509l65.21246-16.817a3.847,3.847,0,0,1,4.68062,2.76145l14.96946,58.04817A3.847,3.847,0,0,1,404.72712,466.1822Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M434.99539,583.55569l-65.21246,16.817a3.847,3.847,0,0,1-4.68062-2.76145l-14.96946-58.04817a3.847,3.847,0,0,1,2.76145-4.68062l65.21247-16.817a3.847,3.847,0,0,1,4.68061,2.76146l14.96947,58.04816A3.847,3.847,0,0,1,434.99539,583.55569Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M863.63647,209.0517H487.31811a48.17928,48.17928,0,0,0-48.125,48.12512V699.05261a48.17924,48.17924,0,0,0,48.125,48.12507H863.63647a48.17924,48.17924,0,0,0,48.125-48.12507V257.17682A48.17928,48.17928,0,0,0,863.63647,209.0517Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M863.637,222.90589H487.31811a34.30948,34.30948,0,0,0-34.271,34.27093V699.05261a34.30947,34.30947,0,0,0,34.271,34.27088H863.637a34.30936,34.30936,0,0,0,34.27051-34.27088V257.17682A34.30937,34.30937,0,0,0,863.637,222.90589Z" transform="translate(-208.9778 -99.05999)" fill="#fff"/><circle cx="694.19401" cy="614.02963" r="87.85039" fill="#fed700"/><path d="M945.18722,701.63087H914.63056V671.07421a11.45875,11.45875,0,0,0-22.9175,0v30.55666H861.1564a11.45875,11.45875,0,0,0,0,22.9175h30.55666V755.105a11.45875,11.45875,0,1,0,22.9175,0V724.54837h30.55666a11.45875,11.45875,0,0,0,0-22.9175Z" transform="translate(-208.9778 -99.05999)" fill="#fff"/><path d="M807.00068,465.71551H616.699a8.01412,8.01412,0,1,1,0-16.02823H807.00068a8.01412,8.01412,0,0,1,0,16.02823Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M840.05889,492.76314H616.699a8.01412,8.01412,0,1,1,0-16.02823H840.05889a8.01411,8.01411,0,1,1,0,16.02823Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M807.00068,586.929H616.699a8.01412,8.01412,0,1,1,0-16.02823H807.00068a8.01411,8.01411,0,0,1,0,16.02823Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M840.05889,613.97661H616.699a8.01412,8.01412,0,1,1,0-16.02823H840.05889a8.01412,8.01412,0,1,1,0,16.02823Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M574.07028,505.04162H506.72434a3.847,3.847,0,0,1-3.84278-3.84278V441.25158a3.847,3.847,0,0,1,3.84278-3.84278h67.34594a3.847,3.847,0,0,1,3.84278,3.84278v59.94726A3.847,3.847,0,0,1,574.07028,505.04162Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M574.07028,626.25509H506.72434a3.847,3.847,0,0,1-3.84278-3.84278V562.46505a3.847,3.847,0,0,1,3.84278-3.84278h67.34594a3.847,3.847,0,0,1,3.84278,3.84278v59.94726A3.847,3.847,0,0,1,574.07028,626.25509Z" transform="translate(-208.9778 -99.05999)" fill="#e6e6e6"/><path d="M807.21185,330.781H666.91017a8.01411,8.01411,0,0,1,0-16.02823H807.21185a8.01411,8.01411,0,0,1,0,16.02823Z" transform="translate(-208.9778 -99.05999)" fill="#ccc"/><path d="M840.27007,357.82862H666.91017a8.01411,8.01411,0,1,1,0-16.02822h173.3599a8.01411,8.01411,0,0,1,0,16.02822Z" transform="translate(-208.9778 -99.05999)" fill="#ccc"/><path d="M635.85911,390.6071H506.51316a3.847,3.847,0,0,1-3.84277-3.84277V285.81706a3.847,3.847,0,0,1,3.84277-3.84277H635.85911a3.847,3.847,0,0,1,3.84277,3.84277V386.76433A3.847,3.847,0,0,1,635.85911,390.6071Z" transform="translate(-208.9778 -99.05999)" fill="#fed700"/></svg>                                        <!--begin::Label-->
                                        <h4 class="fw-bold fs-3 text-gray-600 text-hover-primary">ایجاد فصل جدید</h4>
                                        <!--end::Label-->
                                    </button>
                                    <!--begin::Button-->
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                    <!--end:: new Col-->
				</div>
				<!--end::Row-->
				<!--begin::Table-->
				{{--  <div class="card card-flush mt-6 mt-xl-9">
					<!--begin::Card header-->
					<div class="card-header mt-5">
						<!--begin::Card title-->
						<div class="card-title flex-column">
							<h3 class="fw-bold mb-1">کاربران این دوره</h3>
							<div class="fs-6 text-gray-400">{{ $course->users->count() }} کاربر این دوره را تهیه کرده اند.</div>
						</div>
						<!--begin::Card title-->
						<!--begin::Card toolbar-->
						<div class="card-toolbar my-1">
							<!--begin::Select-->
							<div class="me-6 my-1">
								<select id="kt_filter_year" name="year" data-control="select2" data-hide-search="true" class="w-125px form-select form-select-solid form-select-sm">
									<option value="All" selected="selected">All time</option>
									<option value="thisyear">This year</option>
									<option value="thismonth">This month</option>
									<option value="lastmonth">Last month</option>
									<option value="last90days">Last 90 days</option>
								</select>
							</div>
							<!--end::Select-->
							<!--begin::Select-->
							<div class="me-4 my-1">
								<select id="kt_filter_orders" name="orders" data-control="select2" data-hide-search="true" class="w-125px form-select form-select-solid form-select-sm">
									<option value="All" selected="selected">All Orders</option>
									<option value="Approved">Approved</option>
									<option value="Declined">Declined</option>
									<option value="In Progress">In Progress</option>
									<option value="In Transit">In Transit</option>
								</select>
							</div>
							<!--end::Select-->
							<!--begin::Search-->
							<div class="d-flex align-items-center position-relative my-1">
								<!--begin::Svg Icon | path: icons/duotune/general/gen021.svg-->
								<span class="svg-icon svg-icon-3 position-absolute ms-3">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
										<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
									</svg>
								</span>
								<!--end::Svg Icon-->
								<input type="text" id="kt_filter_search" class="form-control form-control-solid form-select-sm w-150px ps-9" placeholder="جستجوی کاربر" />
							</div>
							<!--end::Search-->
						</div>
						<!--begin::Card toolbar-->
					</div>
					<!--end::Card header-->
					<!--begin::Card body-->
					<div class="card-body pt-0">
						<!--begin::Table container-->
						<div class="table-responsive">
							<!--begin::Table-->
							<table id="kt_profile_overview_table" class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
								<!--begin::Head-->
								<thead class="fs-7 text-gray-400 text-uppercase">
									<tr>
										<th class="text-start min-w-250px">کاربر</th>
										<th class="text-start min-w-150px">تاریخ ثبت نام در دوره</th>
										<th class="text-start min-w-90px">قیمت</th>
										<th class="text-start min-w-90px">نقش</th>
										<th class="min-w-50px text-end">اقدامات</th>
									</tr>
								</thead>
								<!--end::Head-->
								<!--begin::Body-->
								<tbody class="fs-6">
									@foreach ($course->users as $user)
										<tr>
											<td>
												<!--begin::User-->
												<div class="d-flex align-items-center">
													<!--begin::Wrapper-->
													<div class="me-5 position-relative">
														<!--begin::Avatar-->
														<div class="symbol symbol-35px symbol-circle">
															<img alt="{{ $user->first_name.' '.$user->last_name }}" src="{{ $user->profile_pic }}" />
														</div>
														<!--end::Avatar-->
													</div>
													<!--end::Wrapper-->
													<!--begin::Info-->
													<div class="d-flex flex-column justify-content-center">
														<a href="{{ route('admin-show-user', $user->id) }}" class="fs-6 text-gray-800 text-hover-primary">{{ $user->first_name.' '.$user->last_name }}</a>
														<div class="fw-semibold text-gray-400">{{ $user->email }}</div>
													</div>
													<!--end::Info-->
												</div>
												<!--end::User-->
											</td>
											<td>{{ jdate($user->pivot->created_at) }}</td>
											<td>
												{{ number_format($user->pivot->price, 0, '.', ',') }}
												<!--begin::Svg Icon-->
													<svg class="mr-1" width="15" height="15" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M1.39223 9.60081C2.01809 9.60081 2.46646 9.45602 2.73736 9.16644C3.00825 8.88621 3.16705 8.54059 3.21376 8.12958H2.55521C2.06947 8.12958 1.67247 8.0782 1.36421 7.97545C1.05595 7.8727 0.813083 7.71857 0.635601 7.51306C0.45812 7.30756 0.332014 7.06002 0.257285 6.77044C0.191897 6.47153 0.159203 6.13057 0.159203 5.74759C0.159203 5.38328 0.21058 5.03766 0.313332 4.71072C0.416085 4.37444 0.565543 4.08486 0.761707 3.84199C0.967212 3.59912 1.21942 3.40763 1.51834 3.26751C1.8266 3.11806 2.18156 3.04333 2.58323 3.04333C2.90083 3.04333 3.19974 3.0947 3.47998 3.19746C3.76955 3.30021 4.02176 3.46368 4.23661 3.68787C4.45146 3.91205 4.6196 4.2063 4.74103 4.5706C4.87181 4.93491 4.9372 5.37861 4.9372 5.90172V6.20997H5.94604C6.15154 6.20997 6.2543 6.51823 6.2543 7.13475C6.2543 7.79797 6.15154 8.12958 5.94604 8.12958H4.92318C4.89516 8.58729 4.79708 9.02166 4.62894 9.43267C4.4608 9.84368 4.22727 10.2033 3.92835 10.5116C3.63878 10.8198 3.28381 11.0627 2.86346 11.2402C2.44311 11.427 1.97606 11.5204 1.46229 11.5204H0.0891448L0.0050746 9.60081H1.39223ZM1.85462 5.60747C1.85462 5.83166 1.90133 5.99046 1.99474 6.08387C2.09749 6.16794 2.28431 6.20997 2.55521 6.20997H3.24178V5.81765C3.24178 5.46268 3.18106 5.21981 3.05963 5.08904C2.94753 4.95826 2.77005 4.89287 2.52718 4.89287C2.07881 4.89287 1.85462 5.13107 1.85462 5.60747ZM8.2548 6.20997C8.37623 6.20997 8.45563 6.2847 8.493 6.43416C8.5397 6.58362 8.56305 6.81715 8.56305 7.13475C8.56305 7.48037 8.5397 7.73258 8.493 7.89138C8.45563 8.05018 8.37623 8.12958 8.2548 8.12958H5.94286C5.82143 8.12958 5.74203 8.05485 5.70467 7.90539C5.65796 7.74659 5.63461 7.51306 5.63461 7.2048C5.63461 6.84984 5.65796 6.59763 5.70467 6.44817C5.74203 6.28937 5.82143 6.20997 5.94286 6.20997H8.2548ZM10.5673 6.20997C10.6887 6.20997 10.7681 6.2847 10.8055 6.43416C10.8522 6.58362 10.8755 6.81715 10.8755 7.13475C10.8755 7.48037 10.8522 7.73258 10.8055 7.89138C10.7681 8.05018 10.6887 8.12958 10.5673 8.12958H8.25534C8.13391 8.12958 8.05451 8.05485 8.01715 7.90539C7.97044 7.74659 7.94709 7.51306 7.94709 7.2048C7.94709 6.84984 7.97044 6.59763 8.01715 6.44817C8.05451 6.28937 8.13391 6.20997 8.25534 6.20997H10.5673ZM12.8798 6.20997C13.0012 6.20997 13.0806 6.2847 13.118 6.43416C13.1647 6.58362 13.188 6.81715 13.188 7.13475C13.188 7.48037 13.1647 7.73258 13.118 7.89138C13.0806 8.05018 13.0012 8.12958 12.8798 8.12958H10.5678C10.4464 8.12958 10.367 8.05485 10.3296 7.90539C10.2829 7.74659 10.2596 7.51306 10.2596 7.2048C10.2596 6.84984 10.2829 6.59763 10.3296 6.44817C10.367 6.28937 10.4464 6.20997 10.5678 6.20997H12.8798ZM15.1922 6.20997C15.3137 6.20997 15.3931 6.2847 15.4304 6.43416C15.4771 6.58362 15.5005 6.81715 15.5005 7.13475C15.5005 7.48037 15.4771 7.73258 15.4304 7.89138C15.3931 8.05018 15.3137 8.12958 15.1922 8.12958H12.8803C12.7589 8.12958 12.6795 8.05485 12.6421 7.90539C12.5954 7.74659 12.572 7.51306 12.572 7.2048C12.572 6.84984 12.5954 6.59763 12.6421 6.44817C12.6795 6.28937 12.7589 6.20997 12.8803 6.20997H15.1922ZM16.5099 6.20997C16.7808 6.20997 16.9723 6.14926 17.0844 6.02782C17.2058 5.89705 17.2665 5.69621 17.2665 5.42532V3.9681H19.046V5.57945C19.046 6.42949 18.8405 7.06936 18.4295 7.49905C18.0278 7.9194 17.4393 8.12958 16.664 8.12958H15.1928C15.0713 8.12958 14.9919 8.05485 14.9546 7.90539C14.9079 7.74659 14.8845 7.51306 14.8845 7.2048C14.8845 6.84984 14.9079 6.59763 14.9546 6.44817C14.9919 6.28937 15.0713 6.20997 15.1928 6.20997H16.5099ZM19.032 2.42681H17.3366V0.801454H19.032V2.42681ZM16.8742 2.42681H15.1788V0.801454H16.8742V2.42681ZM8.94465 19.7372C8.94465 20.279 8.86058 20.7788 8.69244 21.2365C8.53364 21.7036 8.30011 22.1052 7.99186 22.4415C7.6836 22.7778 7.30995 23.0393 6.87092 23.2262C6.43189 23.4223 5.94148 23.5204 5.39969 23.5204H4.47492C3.42871 23.5204 2.61603 23.1981 2.03688 22.5536C1.45773 21.9091 1.16815 21.0263 1.16815 19.9054V17.3553H2.93363V19.8213C2.93363 20.0922 2.96165 20.3351 3.0177 20.5499C3.07375 20.7741 3.16716 20.9609 3.29793 21.1104C3.43805 21.2692 3.6202 21.3906 3.84439 21.4747C4.07792 21.5588 4.36749 21.6008 4.71312 21.6008H5.32963C5.69394 21.6008 5.99285 21.5494 6.22638 21.4467C6.46925 21.3533 6.66074 21.2178 6.80086 21.0403C6.94098 20.8722 7.03906 20.676 7.09511 20.4518C7.15115 20.2277 7.17918 19.9895 7.17918 19.7372V15.9681H8.94465V19.7372ZM5.70795 15.814H3.8584V14.1186H5.70795V15.814ZM12.4954 20.1296C12.2245 20.1296 11.9676 20.0969 11.7248 20.0315C11.4819 19.9568 11.2671 19.8353 11.0802 19.6672C10.9028 19.4991 10.758 19.2795 10.6459 19.0086C10.5431 18.7284 10.4917 18.3828 10.4917 17.9718V11.4423H12.2712V17.5094C12.2712 17.9764 12.4767 18.21 12.8877 18.21H13.2661C13.4716 18.21 13.5743 18.5182 13.5743 19.1347C13.5743 19.798 13.4716 20.1296 13.2661 20.1296H12.4954ZM13.3335 18.21C13.6137 18.21 13.8379 18.1633 14.0061 18.0699C14.1742 17.9671 14.2583 17.7803 14.2583 17.5094V17.3553C14.2583 16.991 14.3143 16.6547 14.4264 16.3464C14.5385 16.0288 14.6973 15.7579 14.9028 15.5337C15.1083 15.3095 15.3605 15.1321 15.6594 15.0013C15.9584 14.8705 16.29 14.8051 16.6543 14.8051C17.0373 14.8051 17.3782 14.8705 17.6771 15.0013C17.976 15.1321 18.2236 15.3142 18.4197 15.5477C18.6252 15.7719 18.7794 16.0475 18.8821 16.3744C18.9942 16.692 19.0503 17.0423 19.0503 17.4253C19.0503 18.2847 18.8308 18.9526 18.3917 19.429C17.962 19.896 17.3829 20.1296 16.6543 20.1296C16.29 20.1296 15.935 20.0548 15.5894 19.9054C15.2531 19.7559 15.0056 19.5458 14.8468 19.2749C14.6693 19.5925 14.4404 19.8166 14.1602 19.9474C13.8799 20.0689 13.6044 20.1296 13.3335 20.1296H13.2634C13.142 20.1296 13.0626 20.0548 13.0252 19.9054C12.9785 19.7466 12.9552 19.5131 12.9552 19.2048C12.9552 18.8498 12.9785 18.5976 13.0252 18.4482C13.0626 18.2894 13.142 18.21 13.2634 18.21H13.3335ZM17.3408 17.5094C17.3408 17.3132 17.2941 17.1357 17.2007 16.9769C17.1073 16.8181 16.9252 16.7387 16.6543 16.7387C16.3834 16.7387 16.2012 16.8181 16.1078 16.9769C16.0144 17.1357 15.9677 17.3132 15.9677 17.5094C15.9677 17.9764 16.1966 18.21 16.6543 18.21C17.112 18.21 17.3408 17.9764 17.3408 17.5094Z" fill="currentColor"></path>
													</svg>
												<!--end::Svg Icon--></td>
											<td>
												<span class="badge badge-light-{{ $user->role == 'student' ? 'warning' : 'primary' }} fw-bold px-4 py-3">{{ $user->role }}</span>
											</td>
											<td class="text-end">
												<a href="{{ route('admin-show-user', $user->id) }}" class="btn btn-light btn-sm">مشاهده</a>
											</td>
										</tr>
									@endforeach
								</tbody>
								<!--end::Body-->
							</table>
							<!--end::Table-->
						</div>
						<!--end::Table container-->
					</div>
					<!--end::Card body-->
				</div>  --}}
				<!--end::Card-->

				<!--begin::Modals-->
				<!--begin::Modal - Add sections-->
				<div class="modal fade" id="kt_modal_add_section" tabindex="-1" aria-hidden="true">
					<!--begin::Modal dialog-->
					<div class="modal-dialog modal-dialog-centered mw-650px">
						<!--begin::Modal content-->
						<div class="modal-content">
							<!--begin::Modal header-->
							<div class="modal-header">
								<!--begin::Modal title-->
								<h2 class="fw-bold">ایجاد فصل جدید</h2>
								<!--end::Modal title-->
								<!--begin::Close-->
								<div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-sections-modal-action="close">
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
								<form id="kt_modal_add_section_form" class="form" method="POST" action="{{ route('admin-section-create', $course->id) }}">
									@csrf
									<!--begin::Input group-->
									<div class="fv-row mb-7">
										<!--begin::Label-->
										<label class="fs-6 fw-semibold form-label mb-2">
											<span class="required">عنوان فصل</span>
										</label>
										<!--end::Label-->
										<!--begin::Input-->
										<input class="form-control form-control-solid" placeholder="عنوان فصل را وارد کنید" name="title" />
										<div class="fv-plugins-message-container invalid-feedback error_text title_error"><div data-field="title"></div></div>
										<!--end::Input-->
									</div>
									<!--end::Input group-->
									<!--begin::Input group-->
									<div class="fv-row mb-7">
										<!--begin::Label-->
										<label class="fs-6 fw-semibold form-label mb-2">
											<span class="required">عنوان لاتین فصل</span>
										</label>
										<!--end::Label-->
										<!--begin::Input-->
										<input class="form-control form-control-solid" placeholder="عنوان لاتین فصل را وارد کنید" name="english_title" />
										<div class="fv-plugins-message-container invalid-feedback error_text english_title_error"><div data-field="english_title"></div></div>
										<!--end::Input-->
									</div>
									<!--end::Input group-->
									<div class="separator separator-dashed my-5"></div>
									<!--begin::Input group-->
									<div class="fv-row mb-7">
										<!--begin::Label-->
										<label class="fs-6 fw-semibold form-label mb-2">
											<span class="">تاریخ شروع</span>
										</label>
										<!--end::Label-->
										<!--begin::Input-->
										<input class="form-control form-control-solid" placeholder=" تاریخ شروع فصل را وارد کنید" name="start_date" />
										<div class="fv-plugins-message-container invalid-feedback error_text start_date_error"><div data-field="start_date"></div></div>
										<!--end::Input-->
									</div>
									<!--end::Input group-->
									<!--begin::Input group-->
									<div class="fv-row mb-7">
										<!--begin::Label-->
										<label class="fs-6 fw-semibold form-label mb-2">
											<span class="">تاریخ پایان</span>
										</label>
										<!--end::Label-->
										<!--begin::Input-->
										<input class="form-control form-control-solid" placeholder=" تاریخ پایان فصل را وارد کنید" name="end_date" />
										<div class="fv-plugins-message-container invalid-feedback error_text end_date_error"><div data-field="end_date"></div></div>
										<!--end::Input-->
									</div>
									<!--end::Input group-->
									<div class="separator separator-dashed my-5"></div>
									<!--begin::Input group-->
									<div class="fv-row mb-7">
										<!--begin::Label-->
										<label class="fs-6 fw-semibold form-label mb-2">
											<span class="">فایل پیوست</span>
										</label>
										<!--end::Label-->
										<!--begin::Input-->
										<input class="form-control form-control-solid" placeholder=" آدرس فایل پیوست فصل را وارد کنید" id="attached_file" name="attached_file" />
										<div class="fv-plugins-message-container invalid-feedback error_text attached_file_error"><div data-field="attached_file"></div></div>
										<!--end::Input-->
									</div>
									<!--end::Input group-->
									<!--begin::Input group-->
									<div class="fv-row mb-10">
										<!--begin::Label-->
										<label class="fs-6 fw-semibold mb-2">وضعیت انتشار
										<!--End::Label-->
										<!--begin::Row-->
										<div class="row row-cols-2 row-cols-md-auto row-cols-lg-auto row-cols-xl-auto" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
											<!--begin::Col-->
											<div class="col-md-auto col-lg-auto col-xl-auto">
												<!--begin::Option-->
												<label class="btn btn-outline btn-outline-dashed btn-active-light-primary active d-flex text-start p-6" data-kt-button="true">
													<!--begin::Radio-->
													<span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
														<input class="form-check-input" type="radio" name="publish" value="1" checked="checked" />
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
											<div class="col-md-auto col-lg-auto col-xl-auto">
												<!--begin::Option-->
												<label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6" data-kt-button="true">
													<!--begin::Radio-->
													<span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
														<input class="form-check-input" type="radio" name="publish" value="0" />
													</span>
													<!--end::Radio-->
													<!--begin::Info-->
													<span class="ms-5">
														<span class="fs-4 fw-bold text-gray-800">فعلا منتشر نشود</span>
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
									<!--begin::Actions-->
									<div class="text-center pt-15">
										<button type="reset" class="btn btn-light me-3" data-kt-sections-modal-action="cancel">لغو</button>
										<button type="submit" class="btn btn-primary" data-kt-sections-modal-action="submit">
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
				<!--end::Modal - Add sections-->
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
	<script src="/admin/assets/js/custom/apps/projects/project/project.js"></script>
    <script src="/admin/assets/js/custom/apps/courses/sections/delete.js"></script>
    <script src="/admin/assets/js/custom/apps/courses/sections/add.js"></script>
    <script src="/admin/assets/js/custom/apps/courses/episodes/delete.js"></script>
	<script src="/admin/assets/js/widgets.bundle.js"></script>
	<script src="/admin/assets/js/custom/widgets.js"></script>


	<!--start::file manager-->
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.getElementById('attached_file').addEventListener('focus', (event) => {
                event.preventDefault();
                inputId = 'attached_file';
                window.open('/file-manager/fm-button', 'fm', 'width=800,height=400');
            });
        });

        let inputId = '';

        // set file link
        function fmSetLink($url) {
            document.getElementById(inputId).value = $url;
        }
    </script>
    <!--end::file manager-->
@endsection

