<div class="mdk-drawer js-mdk-drawer" id="default-drawer">
    <div class="mdk-drawer__content">
        <div class="sidebar sidebar-light sidebar-light-dodger-blue sidebar-left" data-perfect-scrollbar>
            <a href="/" class="sidebar-brand ">
                <!-- <img class="sidebar-brand-icon" src="/assets/images/illustration/student/128/white.svg" alt="Luma"> -->
                <img src="/assets/images/logo/logo-dark.png" class="w-75" alt="logo" />
            </a>
            <div class="mx-auto w-75 border-bottom pb-3">
                <form class="search-form form-control rounded-lg navbar-search bg-light" method="GET" action="{{ route('search') }}">
                    <input type="text" name="search" class="form-control " placeholder="دنبال چی میگردی؟">
                    <button class="btn" type="submit">
                        <svg opacity="0.6" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M11 18C14.866 18 18 14.866 18 11C18 7.13401 14.866 4 11 4C7.13401 4 4 7.13401 4 11C4 14.866 7.13401 18 11 18ZM11 6C10.3434 6 9.69321 6.12933 9.08658 6.3806C8.47995 6.63188 7.92876 7.00017 7.46447 7.46447C7.00017 7.92876 6.63188 8.47996 6.3806 9.08658C6.12933 9.69321 6 10.3434 6 11C6 11.5523 6.44772 12 7 12C7.55228 12 8 11.5523 8 11C8 10.606 8.0776 10.2159 8.22836 9.85195C8.37913 9.48797 8.6001 9.15726 8.87868 8.87868C9.15726 8.6001 9.48797 8.37913 9.85195 8.22836C10.2159 8.0776 10.606 8 11 8C11.5523 8 12 7.55228 12 7C12 6.44772 11.5523 6 11 6Z" fill="#333"></path> <path d="M20 20L18 18" stroke="#333" stroke-width="2" stroke-linecap="round"></path> </g></svg>
                    </button>
                </form>
            </div>
            <div class="w-75 mx-auto border-bottom py-3">
                <a href="#" class="d-flex align-items-center">
                    <div class="w-25">
                        <span style="width: 40px; height: 40px" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center">
                            <svg width="18" height="18" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                <path opacity="0.6" d="M12,22 C17.5228475,22 22,17.5228475 22,12 C22,6.4771525 17.5228475,2 12,2 C6.4771525,2 2,6.4771525 2,12 C2,17.5228475 6.4771525,22 12,22 Z M12,20 L12,4 C16.418278,4 20,7.581722 20,12 C20,16.418278 16.418278,20 12,20 Z" id="🎨-Color"> </path>
                            </svg>
                        </span>
                    </div>
                    <span class="ml-1 w-75 font-size-12pt font-bold">تم دارک</span>
                </a>
                @auth
                    <a href="{{ route('cart') }}" class="d-flex my-8pt">
                        <div class="d-flex align-items-center w-25">
                            <span style="width: 40px; height: 40px" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" class="" fill-rule="evenodd" clip-rule="evenodd" d="M19.1705 14.9551L18.4657 9.27669C18.0363 7.25046 16.8211 6.41663 15.6626 6.41663H6.35407C5.17937 6.41663 3.92365 7.1921 3.55909 9.27669L2.84616 14.9551C2.26286 19.1243 4.40973 20.1666 7.21282 20.1666H14.8119C17.6069 20.1666 19.689 18.6574 19.1705 14.9551ZM8.33901 11.1362C7.89158 11.1362 7.52887 10.7629 7.52887 10.3024C7.52887 9.84187 7.89158 9.46855 8.33901 9.46855C8.78644 9.46855 9.14915 9.84187 9.14915 10.3024C9.14915 10.7629 8.78644 11.1362 8.33901 11.1362ZM12.8353 10.3024C12.8353 10.7629 13.198 11.1362 13.6454 11.1362C14.0928 11.1362 14.4555 10.7629 14.4555 10.3024C14.4555 9.84187 14.0928 9.46855 13.6454 9.46855C13.198 9.46855 12.8353 9.84187 12.8353 10.3024Z"></path>
                                    <path fill="currentColor" class="" opacity="0.4" d="M15.5594 6.20972C15.5623 6.28082 15.5486 6.35162 15.5195 6.41658H14.2021C14.1766 6.35053 14.1631 6.28049 14.1622 6.20972C14.1622 4.45201 12.7324 3.0271 10.9686 3.0271C9.20486 3.0271 7.77504 4.45201 7.77504 6.20972C7.78713 6.27815 7.78713 6.34815 7.77504 6.41658H6.42575C6.41367 6.34815 6.41367 6.27815 6.42575 6.20972C6.52827 3.76367 8.54793 1.83325 11.0046 1.83325C13.4612 1.83325 15.4808 3.76367 15.5834 6.20972H15.5594Z"></path>
                                </svg>
                            </span>
                            <div class="count-cart mt-n4 ml-n3">
                                @if(Cart::content()->count() > 0)
                                    <span class="badge badge-notifications badge-accent">{{ Cart::content()->count() }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="ml-1 w-75 font-size-12pt font-bold my-auto">سبد خرید</span>
                    </a>
                    <a href="{{ route('student-notifications') }}" class="d-flex my-8pt">
                        <div class="d-flex align-items-center w-25">
                            <span style="width: 40px; height: 40px" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center">
                                <svg width="17" height="15" viewBox="0 0 14 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" d="M13.3014 8.50275C12.709 7.81089 12.4397 7.21133 12.4397 6.19274V5.8464C12.4397 4.51905 12.1342 3.66382 11.47 2.8086C10.4463 1.48043 8.72294 0.679932 7.03584 0.679932H6.9641C5.31247 0.679932 3.64311 1.44367 2.60167 2.71793C1.90119 3.59031 1.56023 4.48229 1.56023 5.8464V6.19274C1.56023 7.21133 1.30873 7.81089 0.698539 8.50275C0.249559 9.01245 0.106079 9.66755 0.106079 10.3766C0.106079 11.0864 0.339033 11.7586 0.806552 12.3051C1.41675 12.9602 2.27843 13.3784 3.15866 13.4511C4.43305 13.5965 5.70744 13.6512 7.00038 13.6512C8.2925 13.6512 9.5669 13.5598 10.8421 13.4511C11.7215 13.3784 12.5832 12.9602 13.1934 12.3051C13.6601 11.7586 13.8939 11.0864 13.8939 10.3766C13.8939 9.66755 13.7504 9.01245 13.3014 8.50275"></path>
                                    <path fill="currentColor" opacity="0.4" d="M8.62912 14.653C8.22367 14.5664 5.75307 14.5664 5.34762 14.653C5.00101 14.733 4.62619 14.9192 4.62619 15.3277C4.64634 15.7173 4.87446 16.0612 5.19044 16.2793L5.18963 16.2801C5.59831 16.5986 6.07792 16.8012 6.5801 16.8739C6.84771 16.9107 7.12016 16.909 7.39745 16.8739C7.89883 16.8012 8.37844 16.5986 8.78711 16.2801L8.78631 16.2793C9.10229 16.0612 9.3304 15.7173 9.35055 15.3277C9.35055 14.9192 8.97573 14.733 8.62912 14.653"></path>
                                </svg>
                            </span>
                            <div class="mt-n4 ml-n3">
                                <span class="badge badge-notifications badge-yellow">{{ auth()->user()->unreadNotifications()->count() }}</span>
                            </div>
                        </div>
                        <span class="ml-1 w-75 font-size-12pt font-bold my-auto">اعلانات</span>
                    </a>
                @endauth
            </div>
            <ul class="sidebar-menu px-2 pt-2" style="">
                <li class="sidebar-menu-item {{ isActive('index', 'active') }}">
                    <a class="sidebar-menu-button" href="/">
                        <svg class="sidebar-menu-icon sidebar-menu-icon--left" width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#000000">
                            <path opacity="0.4" d="M9.02 2.84016L3.63 7.04016C2.73 7.74016 2 9.23016 2 10.3602V17.7702C2 20.0902 3.89 21.9902 6.21 21.9902H17.79C20.11 21.9902 22 20.0902 22 17.7802V10.5002C22 9.29016 21.19 7.74016 20.2 7.05016L14.02 2.72016C12.62 1.74016 10.37 1.79016 9.02 2.84016Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M10.5 18H13.5C15.15 18 16.5 16.65 16.5 15V12C16.5 10.35 15.15 9 13.5 9H10.5C8.85 9 7.5 10.35 7.5 12V15C7.5 16.65 8.85 18 10.5 18Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M12 9V18" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M7.5 13.5H16.5" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="sidebar-menu-text">زنبورک</span>
                    </a>
                </li>
                <li class="sidebar-menu-item {{ isActive('all-course', 'active') }}">
                    <a class="sidebar-menu-button" href="{{ route('all-course') }}">
                        <svg class="sidebar-menu-icon sidebar-menu-icon--left" width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M19.0698 19.8201C18.8798 19.8201 18.6898 19.7501 18.5398 19.6001C18.2498 19.3101 18.2498 18.8301 18.5398 18.5401C22.1498 14.9301 22.1498 9.06011 18.5398 5.46011C18.2498 5.17011 18.2498 4.69012 18.5398 4.40012C18.8298 4.11012 19.3098 4.11012 19.5998 4.40012C23.7898 8.59012 23.7898 15.4101 19.5998 19.6001C19.4498 19.7501 19.2598 19.8201 19.0698 19.8201Z" fill="#292D32"></path>
                            <path opacity="0.4" d="M4.93031 19.8201C4.74031 19.8201 4.55031 19.7501 4.40031 19.6001C0.210312 15.4101 0.210312 8.59012 4.40031 4.40012C4.69031 4.11012 5.17031 4.11012 5.46031 4.40012C5.75031 4.69012 5.75031 5.17011 5.46031 5.46011C1.85031 9.07011 1.85031 14.9401 5.46031 18.5401C5.75031 18.8301 5.75031 19.3101 5.46031 19.6001C5.31031 19.7501 5.12031 19.8201 4.93031 19.8201Z" fill="#292D32"></path>
                            <path opacity="0.4" d="M12.0007 22.71C10.7507 22.7 9.56076 22.4999 8.45076 22.1099C8.06076 21.9699 7.85073 21.54 7.99073 21.15C8.13073 20.76 8.55076 20.55 8.95076 20.69C9.91075 21.02 10.9308 21.2 12.0108 21.2C13.0808 21.2 14.1107 21.02 15.0607 20.69C15.4507 20.56 15.8807 20.76 16.0207 21.15C16.1607 21.54 15.9507 21.9699 15.5607 22.1099C14.4407 22.4999 13.2507 22.71 12.0007 22.71Z" fill="#292D32"></path>
                            <path opacity="0.4" d="M15.3009 3.34009C15.2209 3.34009 15.1309 3.33005 15.0509 3.30005C14.0909 2.97005 13.061 2.79004 11.991 2.79004C10.9209 2.79004 9.90096 2.97005 8.94096 3.30005C8.55096 3.43005 8.12097 3.23009 7.98097 2.84009C7.84097 2.45009 8.05096 2.02007 8.44096 1.88007C9.55096 1.49007 10.751 1.29004 11.991 1.29004C13.231 1.29004 14.431 1.49007 15.541 1.88007C15.931 2.02007 16.141 2.45009 16.001 2.84009C15.901 3.15009 15.6109 3.34009 15.3009 3.34009Z" fill="#292D32"></path>
                            <path d="M8.74023 12.0001V10.3302C8.74023 8.25016 10.2103 7.40014 12.0103 8.44014L13.4602 9.28017L14.9102 10.1201C16.7102 11.1601 16.7102 12.8602 14.9102 13.9002L13.4602 14.7401L12.0103 15.5802C10.2103 16.6202 8.74023 15.7701 8.74023 13.6901V12.0001Z" fill="#292D32"></path>
                        </svg>
                        <span class="sidebar-menu-text">دوره‌های آموزشی</span>
                    </a>
                </li>
                <li class="sidebar-menu-item {{ isActive('paths', 'active') }}">
                    <a class="sidebar-menu-button" href="{{ route('paths') }}">
                        <svg class="sidebar-menu-icon sidebar-menu-icon--left" width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M7.33008 14.4898L9.71008 11.3998C10.0501 10.9598 10.6801 10.8798 11.1201 11.2198L12.9501 12.6598C13.3901 12.9998 14.0201 12.9198 14.3601 12.4898L16.6701 9.50977" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="sidebar-menu-text">مسیرهای آموزشی</span>
                    </a>
                </li>
                <li class="sidebar-menu-item {{ isActive('discuss-all', 'active') }}">
                    <a class="sidebar-menu-button" href="{{ route('discuss-all') }}">
                        <svg class="sidebar-menu-icon sidebar-menu-icon--left" width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M8.5 19H8C4 19 2 18 2 13V8C2 4 4 2 8 2H16C20 2 22 4 22 8V13C22 17 20 19 16 19H15.5C15.19 19 14.89 19.15 14.7 19.4L13.2 21.4C12.54 22.28 11.46 22.28 10.8 21.4L9.3 19.4C9.14 19.18 8.77 19 8.5 19Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M7 8H17" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M7 13H13" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="sidebar-menu-text">پرسش و پاسخ</span>
                    </a>
                </li>
                <li class="sidebar-menu-item {{ isActive(['request-project', 'vip', 'about-us', 'contact-us'], 'open active') }}">
                    <a class="sidebar-menu-button js-sidebar-collapse" data-toggle="collapse" href="#home_menu" aria-expanded="true">
                        <svg class="sidebar-menu-icon sidebar-menu-icon--left" width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.34" d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M15.9965 12H16.0054" stroke="#292D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M11.9945 12H12.0035" stroke="#292D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M7.99451 12H8.00349" stroke="#292D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        بیشتر
                        <span class="ml-auto sidebar-menu-toggle-icon">
                            <svg class="" width="17" height="17" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                                <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                            </svg>
                        </span>
                    </a>
                    <ul class="sidebar-submenu sm-indent collapse " id="home_menu" style="">
                        <li class="sidebar-menu-item {{ isActive('request-project', 'active') }}">
                            <a class="sidebar-menu-button" href="{{ route('request-project') }}">
                                <span class="sidebar-menu-text">درخواست پروژه</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item {{ isActive('vip', 'active') }}">
                            <a class="sidebar-menu-button" href="{{ route('vip') }}">
                                <span class="sidebar-menu-text">عضویت ویژه</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-button" href="#">
                                <span class="sidebar-menu-text">سوالات متداول</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item {{ isActive('about-us', 'active') }}">
                            <a class="sidebar-menu-button" href="{{ route('about-us') }}">
                                <span class="sidebar-menu-text">درباره ما</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item {{ isActive('contact-us', 'active') }}">
                            <a class="sidebar-menu-button" href="{{ route('contact-us') }}">
                                <span class="sidebar-menu-text">ارتباط با ما</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a class="sidebar-menu-button" href="#">
                                <span class="sidebar-menu-text">همکاری با ما</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
