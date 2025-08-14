<div class="card mb-6 mb-xl-9">
    <div class="card-body pt-9 pb-0">
        <!--begin::Details-->
        <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
            <!--begin::Image-->
            <div class="d-flex flex-center flex-shrink-0 bg-light rounded w-100px h-100px w-lg-150px h-lg-150px me-7 mb-4">
                <img class="mw-50px mw-lg-75px rounded" src="{{ $course->poster }}" alt="image" />
            </div>
            <!--end::Image-->
            <!--begin::Wrapper-->
            <div class="flex-grow-1">
                <!--begin::Head-->
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <!--begin::Details-->
                    <div class="d-flex flex-column">
                        <!--begin::Status-->
                        <div class="d-flex align-items-center mb-1">
                            <a href="#" class="text-gray-800 text-hover-primary fs-2 fw-bold me-3">{{ $course->title }}</a>
                            <span class="badge badge-light-success me-auto">{{ $course->status->title }}</span>
                        </div>
                        <!--end::Status-->
                        <!--begin::Description-->
                        <div class="d-flex flex-wrap fw-semibold mb-4 fs-5 text-gray-400">{{ $course->english_title }}</div>
                        <!--end::Description-->
                    </div>
                    <!--end::Details-->
                    <!--begin::Actions-->
                    <div class="d-flex mb-4">
                        {{--  <a href="#" class="btn btn-sm btn-bg-light btn-active-color-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_users_search">Add User</a>
                        <a href="#" class="btn btn-sm btn-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">Add Target</a>  --}}
                        <!--begin::Menu-->
                        <div class="me-0">
                            <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="bi bi-three-dots fs-3"></i>
                            </button>
                            <!--begin::Menu 3-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
                                <!--begin::Heading-->
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Payments</div>
                                </div>
                                <!--end::Heading-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">Create Invoice</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link flex-stack px-3">Create Payment
                                    <i class="fas fa-exclamation-circle ms-2 fs-7" data-bs-toggle="tooltip" title="Specify a target name for future usage and reference"></i></a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">Generate Bill</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-end">
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">Subscription</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">Plans</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">Billing</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">Statements</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content px-3">
                                                <!--begin::Switch-->
                                                <label class="form-check form-switch form-check-custom form-check-solid">
                                                    <!--begin::Input-->
                                                    <input class="form-check-input w-30px h-20px" type="checkbox" value="1" checked="checked" name="notifications" />
                                                    <!--end::Input-->
                                                    <!--end::Label-->
                                                    <span class="form-check-label text-muted fs-6">Recuring</span>
                                                    <!--end::Label-->
                                                </label>
                                                <!--end::Switch-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">Settings</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 3-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Head-->
                <!--begin::Info-->
                <div class="d-flex flex-wrap justify-content-start">
                    <!--begin::Stats-->
                    <div class="d-flex flex-wrap">
                        <!--begin::Stat-->
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <span class="svg-icon svg-icon-3 svg-icon-primary me-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.3" d="M21 22H3C2.4 22 2 21.6 2 21V5C2 4.4 2.4 4 3 4H21C21.6 4 22 4.4 22 5V21C22 21.6 21.6 22 21 22Z" fill="currentColor"></path>
                                        <path d="M6 6C5.4 6 5 5.6 5 5V3C5 2.4 5.4 2 6 2C6.6 2 7 2.4 7 3V5C7 5.6 6.6 6 6 6ZM11 5V3C11 2.4 10.6 2 10 2C9.4 2 9 2.4 9 3V5C9 5.6 9.4 6 10 6C10.6 6 11 5.6 11 5ZM15 5V3C15 2.4 14.6 2 14 2C13.4 2 13 2.4 13 3V5C13 5.6 13.4 6 14 6C14.6 6 15 5.6 15 5ZM19 5V3C19 2.4 18.6 2 18 2C17.4 2 17 2.4 17 3V5C17 5.6 17.4 6 18 6C18.6 6 19 5.6 19 5Z" fill="currentColor"></path>
                                        <path d="M8.8 13.1C9.2 13.1 9.5 13 9.7 12.8C9.9 12.6 10.1 12.3 10.1 11.9C10.1 11.6 10 11.3 9.8 11.1C9.6 10.9 9.3 10.8 9 10.8C8.8 10.8 8.59999 10.8 8.39999 10.9C8.19999 11 8.1 11.1 8 11.2C7.9 11.3 7.8 11.4 7.7 11.6C7.6 11.8 7.5 11.9 7.5 12.1C7.5 12.2 7.4 12.2 7.3 12.3C7.2 12.4 7.09999 12.4 6.89999 12.4C6.69999 12.4 6.6 12.3 6.5 12.2C6.4 12.1 6.3 11.9 6.3 11.7C6.3 11.5 6.4 11.3 6.5 11.1C6.6 10.9 6.8 10.7 7 10.5C7.2 10.3 7.49999 10.1 7.89999 10C8.29999 9.90003 8.60001 9.80003 9.10001 9.80003C9.50001 9.80003 9.80001 9.90003 10.1 10C10.4 10.1 10.7 10.3 10.9 10.4C11.1 10.5 11.3 10.8 11.4 11.1C11.5 11.4 11.6 11.6 11.6 11.9C11.6 12.3 11.5 12.6 11.3 12.9C11.1 13.2 10.9 13.5 10.6 13.7C10.9 13.9 11.2 14.1 11.4 14.3C11.6 14.5 11.8 14.7 11.9 15C12 15.3 12.1 15.5 12.1 15.8C12.1 16.2 12 16.5 11.9 16.8C11.8 17.1 11.5 17.4 11.3 17.7C11.1 18 10.7 18.2 10.3 18.3C9.9 18.4 9.5 18.5 9 18.5C8.5 18.5 8.1 18.4 7.7 18.2C7.3 18 7 17.8 6.8 17.6C6.6 17.4 6.4 17.1 6.3 16.8C6.2 16.5 6.10001 16.3 6.10001 16.1C6.10001 15.9 6.2 15.7 6.3 15.6C6.4 15.5 6.6 15.4 6.8 15.4C6.9 15.4 7.00001 15.4 7.10001 15.5C7.20001 15.6 7.3 15.6 7.3 15.7C7.5 16.2 7.7 16.6 8 16.9C8.3 17.2 8.6 17.3 9 17.3C9.2 17.3 9.5 17.2 9.7 17.1C9.9 17 10.1 16.8 10.3 16.6C10.5 16.4 10.5 16.1 10.5 15.8C10.5 15.3 10.4 15 10.1 14.7C9.80001 14.4 9.50001 14.3 9.10001 14.3C9.00001 14.3 8.9 14.3 8.7 14.3C8.5 14.3 8.39999 14.3 8.39999 14.3C8.19999 14.3 7.99999 14.2 7.89999 14.1C7.79999 14 7.7 13.8 7.7 13.7C7.7 13.5 7.79999 13.4 7.89999 13.2C7.99999 13 8.2 13 8.5 13H8.8V13.1ZM15.3 17.5V12.2C14.3 13 13.6 13.3 13.3 13.3C13.1 13.3 13 13.2 12.9 13.1C12.8 13 12.7 12.8 12.7 12.6C12.7 12.4 12.8 12.3 12.9 12.2C13 12.1 13.2 12 13.6 11.8C14.1 11.6 14.5 11.3 14.7 11.1C14.9 10.9 15.2 10.6 15.5 10.3C15.8 10 15.9 9.80003 15.9 9.70003C15.9 9.60003 16.1 9.60004 16.3 9.60004C16.5 9.60004 16.7 9.70003 16.8 9.80003C16.9 9.90003 17 10.2 17 10.5V17.2C17 18 16.7 18.4 16.2 18.4C16 18.4 15.8 18.3 15.6 18.2C15.4 18.1 15.3 17.8 15.3 17.5Z" fill="currentColor"></path>
                                    </svg>
                                </span>
                                 <div class="fs-4 fw-bold">{{ jdate($course->updated_at)->format('Y-m-d') }}</div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-400">آخرین بروزرسانی</div>
                            <!--end::Label-->
                        </div>
                        <!--end::Stat-->
                        <!--begin::Stat-->
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <!--begin::Svg Icon | path: icons/duotune/communication/com007.svg-->
                                <span class="svg-icon svg-icon-3 svg-icon-primary me-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.3" d="M8 8C8 7.4 8.4 7 9 7H16V3C16 2.4 15.6 2 15 2H3C2.4 2 2 2.4 2 3V13C2 13.6 2.4 14 3 14H5V16.1C5 16.8 5.79999 17.1 6.29999 16.6L8 14.9V8Z" fill="currentColor"/>
                                        <path d="M22 8V18C22 18.6 21.6 19 21 19H19V21.1C19 21.8 18.2 22.1 17.7 21.6L15 18.9H9C8.4 18.9 8 18.5 8 17.9V7.90002C8 7.30002 8.4 6.90002 9 6.90002H21C21.6 7.00002 22 7.4 22 8ZM19 11C19 10.4 18.6 10 18 10H12C11.4 10 11 10.4 11 11C11 11.6 11.4 12 12 12H18C18.6 12 19 11.6 19 11ZM17 15C17 14.4 16.6 14 16 14H12C11.4 14 11 14.4 11 15C11 15.6 11.4 16 12 16H16C16.6 16 17 15.6 17 15Z" fill="currentColor"/>
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                <div class="fs-4 fw-bold" data-kt-countup="true" data-kt-countup-value="{{ $course->comments->count() }}">0</div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-400">تعداد کامنت</div>
                            <!--end::Label-->
                        </div>
                        <!--end::Stat-->
                        <!--begin::Stat-->
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr066.svg-->
                                {{--  <span class="svg-icon svg-icon-3 svg-icon-success me-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect opacity="0.5" x="13" y="6" width="13" height="2" rx="1" transform="rotate(90 13 6)" fill="currentColor" />
                                        <path d="M12.5657 8.56569L16.75 12.75C17.1642 13.1642 17.8358 13.1642 18.25 12.75C18.6642 12.3358 18.6642 11.6642 18.25 11.25L12.7071 5.70711C12.3166 5.31658 11.6834 5.31658 11.2929 5.70711L5.75 11.25C5.33579 11.6642 5.33579 12.3358 5.75 12.75C6.16421 13.1642 6.83579 13.1642 7.25 12.75L11.4343 8.56569C11.7467 8.25327 12.2533 8.25327 12.5657 8.56569Z" fill="currentColor" />
                                    </svg>
                                </span>  --}}
                                <!--end::Svg Icon-->
                                <div class="fs-4 fw-bold" data-kt-countup="true" data-kt-countup-value="{{ DB::table('course_user')->where('course_id', $course->id)->sum('price') }}" data-kt-countup-prefix=''>0</div>
                                <!--begin::Svg Icon-->
                                    <svg class="mr-1" width="18" height="18" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1.39223 9.60081C2.01809 9.60081 2.46646 9.45602 2.73736 9.16644C3.00825 8.88621 3.16705 8.54059 3.21376 8.12958H2.55521C2.06947 8.12958 1.67247 8.0782 1.36421 7.97545C1.05595 7.8727 0.813083 7.71857 0.635601 7.51306C0.45812 7.30756 0.332014 7.06002 0.257285 6.77044C0.191897 6.47153 0.159203 6.13057 0.159203 5.74759C0.159203 5.38328 0.21058 5.03766 0.313332 4.71072C0.416085 4.37444 0.565543 4.08486 0.761707 3.84199C0.967212 3.59912 1.21942 3.40763 1.51834 3.26751C1.8266 3.11806 2.18156 3.04333 2.58323 3.04333C2.90083 3.04333 3.19974 3.0947 3.47998 3.19746C3.76955 3.30021 4.02176 3.46368 4.23661 3.68787C4.45146 3.91205 4.6196 4.2063 4.74103 4.5706C4.87181 4.93491 4.9372 5.37861 4.9372 5.90172V6.20997H5.94604C6.15154 6.20997 6.2543 6.51823 6.2543 7.13475C6.2543 7.79797 6.15154 8.12958 5.94604 8.12958H4.92318C4.89516 8.58729 4.79708 9.02166 4.62894 9.43267C4.4608 9.84368 4.22727 10.2033 3.92835 10.5116C3.63878 10.8198 3.28381 11.0627 2.86346 11.2402C2.44311 11.427 1.97606 11.5204 1.46229 11.5204H0.0891448L0.0050746 9.60081H1.39223ZM1.85462 5.60747C1.85462 5.83166 1.90133 5.99046 1.99474 6.08387C2.09749 6.16794 2.28431 6.20997 2.55521 6.20997H3.24178V5.81765C3.24178 5.46268 3.18106 5.21981 3.05963 5.08904C2.94753 4.95826 2.77005 4.89287 2.52718 4.89287C2.07881 4.89287 1.85462 5.13107 1.85462 5.60747ZM8.2548 6.20997C8.37623 6.20997 8.45563 6.2847 8.493 6.43416C8.5397 6.58362 8.56305 6.81715 8.56305 7.13475C8.56305 7.48037 8.5397 7.73258 8.493 7.89138C8.45563 8.05018 8.37623 8.12958 8.2548 8.12958H5.94286C5.82143 8.12958 5.74203 8.05485 5.70467 7.90539C5.65796 7.74659 5.63461 7.51306 5.63461 7.2048C5.63461 6.84984 5.65796 6.59763 5.70467 6.44817C5.74203 6.28937 5.82143 6.20997 5.94286 6.20997H8.2548ZM10.5673 6.20997C10.6887 6.20997 10.7681 6.2847 10.8055 6.43416C10.8522 6.58362 10.8755 6.81715 10.8755 7.13475C10.8755 7.48037 10.8522 7.73258 10.8055 7.89138C10.7681 8.05018 10.6887 8.12958 10.5673 8.12958H8.25534C8.13391 8.12958 8.05451 8.05485 8.01715 7.90539C7.97044 7.74659 7.94709 7.51306 7.94709 7.2048C7.94709 6.84984 7.97044 6.59763 8.01715 6.44817C8.05451 6.28937 8.13391 6.20997 8.25534 6.20997H10.5673ZM12.8798 6.20997C13.0012 6.20997 13.0806 6.2847 13.118 6.43416C13.1647 6.58362 13.188 6.81715 13.188 7.13475C13.188 7.48037 13.1647 7.73258 13.118 7.89138C13.0806 8.05018 13.0012 8.12958 12.8798 8.12958H10.5678C10.4464 8.12958 10.367 8.05485 10.3296 7.90539C10.2829 7.74659 10.2596 7.51306 10.2596 7.2048C10.2596 6.84984 10.2829 6.59763 10.3296 6.44817C10.367 6.28937 10.4464 6.20997 10.5678 6.20997H12.8798ZM15.1922 6.20997C15.3137 6.20997 15.3931 6.2847 15.4304 6.43416C15.4771 6.58362 15.5005 6.81715 15.5005 7.13475C15.5005 7.48037 15.4771 7.73258 15.4304 7.89138C15.3931 8.05018 15.3137 8.12958 15.1922 8.12958H12.8803C12.7589 8.12958 12.6795 8.05485 12.6421 7.90539C12.5954 7.74659 12.572 7.51306 12.572 7.2048C12.572 6.84984 12.5954 6.59763 12.6421 6.44817C12.6795 6.28937 12.7589 6.20997 12.8803 6.20997H15.1922ZM16.5099 6.20997C16.7808 6.20997 16.9723 6.14926 17.0844 6.02782C17.2058 5.89705 17.2665 5.69621 17.2665 5.42532V3.9681H19.046V5.57945C19.046 6.42949 18.8405 7.06936 18.4295 7.49905C18.0278 7.9194 17.4393 8.12958 16.664 8.12958H15.1928C15.0713 8.12958 14.9919 8.05485 14.9546 7.90539C14.9079 7.74659 14.8845 7.51306 14.8845 7.2048C14.8845 6.84984 14.9079 6.59763 14.9546 6.44817C14.9919 6.28937 15.0713 6.20997 15.1928 6.20997H16.5099ZM19.032 2.42681H17.3366V0.801454H19.032V2.42681ZM16.8742 2.42681H15.1788V0.801454H16.8742V2.42681ZM8.94465 19.7372C8.94465 20.279 8.86058 20.7788 8.69244 21.2365C8.53364 21.7036 8.30011 22.1052 7.99186 22.4415C7.6836 22.7778 7.30995 23.0393 6.87092 23.2262C6.43189 23.4223 5.94148 23.5204 5.39969 23.5204H4.47492C3.42871 23.5204 2.61603 23.1981 2.03688 22.5536C1.45773 21.9091 1.16815 21.0263 1.16815 19.9054V17.3553H2.93363V19.8213C2.93363 20.0922 2.96165 20.3351 3.0177 20.5499C3.07375 20.7741 3.16716 20.9609 3.29793 21.1104C3.43805 21.2692 3.6202 21.3906 3.84439 21.4747C4.07792 21.5588 4.36749 21.6008 4.71312 21.6008H5.32963C5.69394 21.6008 5.99285 21.5494 6.22638 21.4467C6.46925 21.3533 6.66074 21.2178 6.80086 21.0403C6.94098 20.8722 7.03906 20.676 7.09511 20.4518C7.15115 20.2277 7.17918 19.9895 7.17918 19.7372V15.9681H8.94465V19.7372ZM5.70795 15.814H3.8584V14.1186H5.70795V15.814ZM12.4954 20.1296C12.2245 20.1296 11.9676 20.0969 11.7248 20.0315C11.4819 19.9568 11.2671 19.8353 11.0802 19.6672C10.9028 19.4991 10.758 19.2795 10.6459 19.0086C10.5431 18.7284 10.4917 18.3828 10.4917 17.9718V11.4423H12.2712V17.5094C12.2712 17.9764 12.4767 18.21 12.8877 18.21H13.2661C13.4716 18.21 13.5743 18.5182 13.5743 19.1347C13.5743 19.798 13.4716 20.1296 13.2661 20.1296H12.4954ZM13.3335 18.21C13.6137 18.21 13.8379 18.1633 14.0061 18.0699C14.1742 17.9671 14.2583 17.7803 14.2583 17.5094V17.3553C14.2583 16.991 14.3143 16.6547 14.4264 16.3464C14.5385 16.0288 14.6973 15.7579 14.9028 15.5337C15.1083 15.3095 15.3605 15.1321 15.6594 15.0013C15.9584 14.8705 16.29 14.8051 16.6543 14.8051C17.0373 14.8051 17.3782 14.8705 17.6771 15.0013C17.976 15.1321 18.2236 15.3142 18.4197 15.5477C18.6252 15.7719 18.7794 16.0475 18.8821 16.3744C18.9942 16.692 19.0503 17.0423 19.0503 17.4253C19.0503 18.2847 18.8308 18.9526 18.3917 19.429C17.962 19.896 17.3829 20.1296 16.6543 20.1296C16.29 20.1296 15.935 20.0548 15.5894 19.9054C15.2531 19.7559 15.0056 19.5458 14.8468 19.2749C14.6693 19.5925 14.4404 19.8166 14.1602 19.9474C13.8799 20.0689 13.6044 20.1296 13.3335 20.1296H13.2634C13.142 20.1296 13.0626 20.0548 13.0252 19.9054C12.9785 19.7466 12.9552 19.5131 12.9552 19.2048C12.9552 18.8498 12.9785 18.5976 13.0252 18.4482C13.0626 18.2894 13.142 18.21 13.2634 18.21H13.3335ZM17.3408 17.5094C17.3408 17.3132 17.2941 17.1357 17.2007 16.9769C17.1073 16.8181 16.9252 16.7387 16.6543 16.7387C16.3834 16.7387 16.2012 16.8181 16.1078 16.9769C16.0144 17.1357 15.9677 17.3132 15.9677 17.5094C15.9677 17.9764 16.1966 18.21 16.6543 18.21C17.112 18.21 17.3408 17.9764 17.3408 17.5094Z" fill="currentColor"></path>
                                    </svg>
                                <!--end::Svg Icon-->
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-400">فروش بدون تخفیف</div>
                            <!--end::Label-->
                        </div>
                        <!--end::Stat-->
                    </div>
                    <!--end::Stats-->
                    <!--begin::Users-->
                    <div class="symbol-group symbol-hover mb-3">
                        @foreach ($course->users()->limit(8)->get() as $user)
                            <!--begin::User-->
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{ $user->first_name.' '.$user->last_name }}">
                                <img alt="{{ $user->first_name.' '.$user->last_name }}" src="{{ $user->profile_pic }}" />
                            </div>
                            <!--end::User-->
                        @endforeach
                        @if ($course->users->count() > 8)
                            <!--begin::All users-->
                            <a href="#" class="symbol symbol-35px symbol-circle" data-bs-toggle="modal" data-bs-target="#kt_modal_view_users">
                                <span class="symbol-label bg-dark text-inverse-dark fs-8 fw-bold" data-bs-toggle="tooltip" data-bs-trigger="hover" title="مشاهده همه">+{{ $course->users->count() - 8 }}</span>
                            </a>
                            <!--end::All users-->
                        @endif

                    </div>
                    <!--end::Users-->
                </div>
                <!--end::Info-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Details-->
        <div class="separator"></div>
        <!--begin::Nav-->
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ isActive('admin-show-course', 'active') }}" href="{{ route('admin-show-course', $course->id) }}">جزییات</a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ isActive('admin-course-sections', 'active') }}" href="{{ route('admin-course-sections', $course->id) }}">فصل ها</a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6" href="">کاربران</a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6" href="">کامنت ها</a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6" href="">امتیازات</a>
            </li>
            <!--end::Nav item-->
        </ul>
        <!--end::Nav-->
    </div>
</div>
