
<div class="navbar navbar-expand pr-0 navbar-light bg-white py-8pt px-lg-24pt" id="default-navbar" data-primary>
    <!-- Navbar toggler -->
    <button class="navbar-toggler w-auto mr-16pt d-block d-lg-none rounded-0" type="button" data-toggle="sidebar">
        <svg width="24" class="text-biscay-700 dark:text-white" height="18" viewBox="0 0 24 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 2.5H21.5M2 9H21.5M2 15.5H21.5" stroke="currentColor" stroke-width="3.65625" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>


    <a href="{{ route('student-home') }}" class="d-none d-lg-block">
        <span class="d-flex flex-row mr-2" style="width: 230px">
            <img src="/assets/images/logo/logo-wide.svg" class="w-90 mx-auto mr-0" alt="logo" />
        </span>
    </a>

    <!-- Navbar Brand -->
    <a href="{{ route('student-home') }}" class="navbar-brand mr-16pt d-lg-none">

          <span class="avatar avatar-sm navbar-brand-icon mr-0 mr-lg-8pt">
              <span class="rounded"><img src="/assets/images/logo/logo-without-text.png" alt="logo" class="img-fluid" /></span>
          </span>
    </a>

    <span class="d-none d-md-flex align-items-center mx-24pt">

          <span class="avatar avatar-sm mr-12pt">

            <span class="avatar-title rounded-lg navbar-avatar">
                <svg width="20" height="20" fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12,2 C17.3333333,7.05448133 20,11.0544813 20,14 C20,18.418278 16.418278,22 12,22 C7.581722,22 4,18.418278 4,14 C4,11.0544813 6.66666667,7.05448133 12,2 Z M12.5401341,5.34306485 L12,4.793 L11.7832437,5.01193635 C8.50224504,8.34406715 6.63844327,11.052329 6.13806422,13.0012894 L17.8619358,13.0012894 C17.378236,11.1172943 15.6204935,8.52377427 12.5401341,5.34306485 L12.5401341,5.34306485 Z"></path> </g></svg>
            </span>

          </span>

          <small class="flex d-flex flex-column">
            <strong class="font-bold">تجربه کاربری</strong>
            <span class="navbar-text-50">{{ number_format(auth()->user()->currentScore(), 0, '.', ',') }} تجربه</span>
          </small>
        </span>

    <form class="search-form navbar-search d-none d-md-flex mr-16pt rounded-lg" action="#">
        <input type="text" class="form-control" placeholder="جستجو...">
        <button class="btn" type="submit">
            <svg opacity="0.6" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M11 18C14.866 18 18 14.866 18 11C18 7.13401 14.866 4 11 4C7.13401 4 4 7.13401 4 11C4 14.866 7.13401 18 11 18ZM11 6C10.3434 6 9.69321 6.12933 9.08658 6.3806C8.47995 6.63188 7.92876 7.00017 7.46447 7.46447C7.00017 7.92876 6.63188 8.47996 6.3806 9.08658C6.12933 9.69321 6 10.3434 6 11C6 11.5523 6.44772 12 7 12C7.55228 12 8 11.5523 8 11C8 10.606 8.0776 10.2159 8.22836 9.85195C8.37913 9.48797 8.6001 9.15726 8.87868 8.87868C9.15726 8.6001 9.48797 8.37913 9.85195 8.22836C10.2159 8.0776 10.606 8 11 8C11.5523 8 12 7.55228 12 7C12 6.44772 11.5523 6 11 6Z" fill="#333"></path> <path d="M20 20L18 18" stroke="#333" stroke-width="2" stroke-linecap="round"></path> </g></svg>
        </button>
    </form>

{{--    <div class="d-none d-lg-flex mt-3">--}}
{{--        <h5 class="border-right px-2">--}}
{{--            سلام {{ auth()->user()->first_name }} عزیز خوش اومدی 😍--}}
{{--        </h5>--}}
{{--        <h5 class="px-2 text-muted">--}}
{{--            {{ jdate(now())->format('l, d-M-Y') }}--}}
{{--        </h5>--}}
{{--        --}}{{--                    <h5 class="digital-clock text-muted">00:00:00</h5>--}}
{{--    </div>--}}



    <div class="flex"></div>

    <div class="nav navbar-nav flex-nowrap d-flex mr-16pt">

        <!-- cart -->
        <div class="nav-item ml-16pt d-none d-lg-flex dropdown-notifications">
            <a href="{{ route('cart') }}" style="width: 45px; height: 45px" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mr-16pt">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" class="" fill-rule="evenodd" clip-rule="evenodd" d="M19.1705 14.9551L18.4657 9.27669C18.0363 7.25046 16.8211 6.41663 15.6626 6.41663H6.35407C5.17937 6.41663 3.92365 7.1921 3.55909 9.27669L2.84616 14.9551C2.26286 19.1243 4.40973 20.1666 7.21282 20.1666H14.8119C17.6069 20.1666 19.689 18.6574 19.1705 14.9551ZM8.33901 11.1362C7.89158 11.1362 7.52887 10.7629 7.52887 10.3024C7.52887 9.84187 7.89158 9.46855 8.33901 9.46855C8.78644 9.46855 9.14915 9.84187 9.14915 10.3024C9.14915 10.7629 8.78644 11.1362 8.33901 11.1362ZM12.8353 10.3024C12.8353 10.7629 13.198 11.1362 13.6454 11.1362C14.0928 11.1362 14.4555 10.7629 14.4555 10.3024C14.4555 9.84187 14.0928 9.46855 13.6454 9.46855C13.198 9.46855 12.8353 9.84187 12.8353 10.3024Z"></path>
                    <path fill="currentColor" class="" opacity="0.4" d="M15.5594 6.20972C15.5623 6.28082 15.5486 6.35162 15.5195 6.41658H14.2021C14.1766 6.35053 14.1631 6.28049 14.1622 6.20972C14.1622 4.45201 12.7324 3.0271 10.9686 3.0271C9.20486 3.0271 7.77504 4.45201 7.77504 6.20972C7.78713 6.27815 7.78713 6.34815 7.77504 6.41658H6.42575C6.41367 6.34815 6.41367 6.27815 6.42575 6.20972C6.52827 3.76367 8.54793 1.83325 11.0046 1.83325C13.4612 1.83325 15.4808 3.76367 15.5834 6.20972H15.5594Z"></path>
                </svg>
            </a>
            <div class="count-cart mt-n4 ml-n3">
                @if(Cart::content()->count() > 0)
                    <span class="badge badge-notifications badge-accent">{{ Cart::content()->count() }}</span>
                @endif
            </div>
        </div>
        <!--  END cart -->

        <!-- Notifications dropdown -->
        <div class="nav-item ml-16pt d-none d-lg-flex dropdown dropdown-notifications dropdown-xs-down-full" data-toggle="tooltip" data-title="اعلانات" data-placement="bottom" data-boundary="window">
            <a style="width: 45px; height: 45px" class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mr-16pt" type="button" data-toggle="dropdown" data-caret="false">
                <svg width="18" height="18" opacity="0.7" viewBox="0 0 14 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M13.3014 8.50275C12.709 7.81089 12.4397 7.21133 12.4397 6.19274V5.8464C12.4397 4.51905 12.1342 3.66382 11.47 2.8086C10.4463 1.48043 8.72294 0.679932 7.03584 0.679932H6.9641C5.31247 0.679932 3.64311 1.44367 2.60167 2.71793C1.90119 3.59031 1.56023 4.48229 1.56023 5.8464V6.19274C1.56023 7.21133 1.30873 7.81089 0.698539 8.50275C0.249559 9.01245 0.106079 9.66755 0.106079 10.3766C0.106079 11.0864 0.339033 11.7586 0.806552 12.3051C1.41675 12.9602 2.27843 13.3784 3.15866 13.4511C4.43305 13.5965 5.70744 13.6512 7.00038 13.6512C8.2925 13.6512 9.5669 13.5598 10.8421 13.4511C11.7215 13.3784 12.5832 12.9602 13.1934 12.3051C13.6601 11.7586 13.8939 11.0864 13.8939 10.3766C13.8939 9.66755 13.7504 9.01245 13.3014 8.50275"></path>
                    <path fill="currentColor" opacity="0.4" d="M8.62912 14.653C8.22367 14.5664 5.75307 14.5664 5.34762 14.653C5.00101 14.733 4.62619 14.9192 4.62619 15.3277C4.64634 15.7173 4.87446 16.0612 5.19044 16.2793L5.18963 16.2801C5.59831 16.5986 6.07792 16.8012 6.5801 16.8739C6.84771 16.9107 7.12016 16.909 7.39745 16.8739C7.89883 16.8012 8.37844 16.5986 8.78711 16.2801L8.78631 16.2793C9.10229 16.0612 9.3304 15.7173 9.35055 15.3277C9.35055 14.9192 8.97573 14.733 8.62912 14.653"></path>
                </svg>
            </a>
            <div class="mt-n4 ml-n3">
                <span class="badge badge-notifications badge-yellow">{{ auth()->user()->unreadNotifications()->count() }}</span>
            </div>
            <div class="dropdown-menu dropdown-menu-right rounded-lg border-0">
                <div data-perfect-scrollbar class="position-relative py-3" style="height:350px">
                    {{--  <div class="dropdown-header"><strong>System notifications</strong></div>  --}}
                    <div class="d-flex flex-column">
                        @forelse (auth()->user()->unreadNotifications as $notification)

                            <div class="d-flex mt-2 rounded-lg border-1 border-light mx-8pt py-4pt px-8pt">
                                <div class="my-auto border-right-1 pr-4pt">
                                    <div class="rounded text-white bg-{{ $notification->data['icon-class'] }} p-1">
                                        {!! $notification->data['icon'] !!}
                                    </div>
                                </div>
                                <div class="ml-2 d-flex flex-column w-100">
                                    <div class="text-black-70 mb-4pt">
                                        {!! Str::words($notification->data['message'], 7, '...') !!}
                                    </div>
                                    <div class="d-flex">
                                        <div class="my-auto text-50">
                                            <svg class="mr-4pt" width="12" height="12" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M1.29921 7.10036C1.29921 8.47492 1.37342 9.55017 1.56095 10.394C1.74739 11.2329 2.04007 11.8164 2.45902 12.2353C2.87796 12.6543 3.4615 12.947 4.30041 13.1334C5.14419 13.3209 6.21945 13.3952 7.594 13.3952C8.96856 13.3952 10.0438 13.3209 10.8876 13.1334C11.7265 12.947 12.31 12.6543 12.729 12.2353C13.1479 11.8164 13.4406 11.2329 13.6271 10.394C13.8146 9.55017 13.8888 8.47492 13.8888 7.10036C13.8888 5.72581 13.8146 4.65055 13.6271 3.80677C13.4406 2.96786 13.1479 2.38432 12.729 1.96538C12.31 1.54643 11.7265 1.25375 10.8876 1.06731C10.0438 0.879784 8.96856 0.805572 7.594 0.805572C6.21945 0.805572 5.14419 0.879784 4.30041 1.06731C3.4615 1.25375 2.87796 1.54643 2.45902 1.96538C2.04007 2.38432 1.74739 2.96786 1.56095 3.80677C1.37342 4.65055 1.29921 5.72581 1.29921 7.10036Z" stroke="currentColor" stroke-width="0.858919" stroke-linecap="round" stroke-linejoin="round"></path>
                                                <path d="M7.59399 3.73825C7.59399 3.73825 7.59399 5.97967 7.59399 6.54002C7.59399 7.10038 7.59399 7.10038 8.15435 7.10038C8.71471 7.10038 10.9561 7.10038 10.9561 7.10038" stroke="currentColor" stroke-width="0.858919" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                            {{ jdate($notification->created_at)->ago() }}
                                        </div>
                                        <a href="{{ route('student-notifications') }}" class="ml-auto btn btn-sm btn-sm-light">
                                            مشاهده
                                            <svg class="ml-1" width="17" height="17" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="currentColor" opacity="0.4" d="M21.25 9.14993C18.94 5.51993 15.56 3.42993 12 3.42993C10.22 3.42993 8.49 3.94993 6.91 4.91993C5.33 5.89993 3.91 7.32993 2.75 9.14993C1.75 10.7199 1.75 13.2699 2.75 14.8399C5.06 18.4799 8.44 20.5599 12 20.5599C13.78 20.5599 15.51 20.0399 17.09 19.0699C18.67 18.0899 20.09 16.6599 21.25 14.8399C22.25 13.2799 22.25 10.7199 21.25 9.14993ZM12 16.0399C9.76 16.0399 7.96 14.2299 7.96 11.9999C7.96 9.76993 9.76 7.95993 12 7.95993C14.24 7.95993 16.04 9.76993 16.04 11.9999C16.04 14.2299 14.24 16.0399 12 16.0399Z"></path>
                                                <path fill="currentColor" d="M12.0004 9.13989C10.4304 9.13989 9.15039 10.4199 9.15039 11.9999C9.15039 13.5699 10.4304 14.8499 12.0004 14.8499C13.5704 14.8499 14.8604 13.5699 14.8604 11.9999C14.8604 10.4299 13.5704 9.13989 12.0004 9.13989Z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center p-3">
                                <div class="text-muted font-size-16pt font-bold mb-20pt">اعلان جدیدی ندارید!</div>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" data-name="Layer 1" width="140" height="140" opacity="0.8" viewBox="0 0 797.5 834.5"><title>void</title><ellipse cx="308.5" cy="780" rx="308.5" ry="54.5" fill="#3f3d56"/><circle cx="496" cy="301.5" r="301.5" fill="#3f3d56"/><circle cx="496" cy="301.5" r="248.89787" opacity="0.05"/><circle cx="496" cy="301.5" r="203.99362" opacity="0.05"/><circle cx="496" cy="301.5" r="146.25957" opacity="0.05"/><path d="M398.42029,361.23224s-23.70394,66.72221-13.16886,90.42615,27.21564,46.52995,27.21564,46.52995S406.3216,365.62186,398.42029,361.23224Z" transform="translate(-201.25 -32.75)" fill="#d0cde1"/><path d="M398.42029,361.23224s-23.70394,66.72221-13.16886,90.42615,27.21564,46.52995,27.21564,46.52995S406.3216,365.62186,398.42029,361.23224Z" transform="translate(-201.25 -32.75)" opacity="0.1"/><path d="M415.10084,515.74682s-1.75585,16.68055-2.63377,17.55847.87792,2.63377,0,5.26754-1.75585,6.14547,0,7.02339-9.65716,78.13521-9.65716,78.13521-28.09356,36.8728-16.68055,94.81576l3.51169,58.82089s27.21564,1.75585,27.21564-7.90132c0,0-1.75585-11.413-1.75585-16.68055s4.38962-5.26754,1.75585-7.90131-2.63377-4.38962-2.63377-4.38962,4.38961-3.51169,3.51169-4.38962,7.90131-63.2105,7.90131-63.2105,9.65716-9.65716,9.65716-14.92471v-5.26754s4.38962-11.413,4.38962-12.29093,23.70394-54.43127,23.70394-54.43127l9.65716,38.62864,10.53509,55.3092s5.26754,50.04165,15.80262,69.356c0,0,18.4364,63.21051,18.4364,61.45466s30.72733-6.14547,29.84941-14.04678-18.4364-118.5197-18.4364-118.5197L533.62054,513.991Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><path d="M391.3969,772.97846s-23.70394,46.53-7.90131,48.2858,21.94809,1.75585,28.97148-5.26754c3.83968-3.83968,11.61528-8.99134,17.87566-12.87285a23.117,23.117,0,0,0,10.96893-21.98175c-.463-4.29531-2.06792-7.83444-6.01858-8.16366-10.53508-.87792-22.826-10.53508-22.826-10.53508Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><path d="M522.20753,807.21748s-23.70394,46.53-7.90131,48.28581,21.94809,1.75584,28.97148-5.26754c3.83968-3.83969,11.61528-8.99134,17.87566-12.87285a23.117,23.117,0,0,0,10.96893-21.98175c-.463-4.29531-2.06792-7.83444-6.01857-8.16367-10.53509-.87792-22.826-10.53508-22.826-10.53508Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><circle cx="295.90488" cy="215.43252" r="36.90462" fill="#ffb8b8"/><path d="M473.43048,260.30832S447.07,308.81154,444.9612,308.81154,492.41,324.62781,492.41,324.62781s13.70743-46.39439,15.81626-50.61206Z" transform="translate(-201.25 -32.75)" fill="#ffb8b8"/><path d="M513.86726,313.3854s-52.67543-28.97148-57.943-28.09356-61.45466,50.04166-60.57673,70.2339,7.90131,53.55335,7.90131,53.55335,2.63377,93.05991,7.90131,93.93783-.87792,16.68055.87793,16.68055,122.90931,0,123.78724-2.63377S513.86726,313.3854,513.86726,313.3854Z" transform="translate(-201.25 -32.75)" fill="#d0cde1"/><path d="M543.2777,521.89228s16.68055,50.91958,2.63377,49.16373-20.19224-43.89619-20.19224-43.89619Z" transform="translate(-201.25 -32.75)" fill="#ffb8b8"/><path d="M498.50359,310.31267s-32.48318,7.02339-27.21563,50.91957,14.9247,87.79237,14.9247,87.79237l32.48318,71.11182,3.51169,13.16886,23.70394-6.14547L528.353,425.32067s-6.14547-108.86253-14.04678-112.37423A33.99966,33.99966,0,0,0,498.50359,310.31267Z" transform="translate(-201.25 -32.75)" fill="#d0cde1"/><polygon points="277.5 414.958 317.885 486.947 283.86 411.09 277.5 414.958" opacity="0.1"/><path d="M533.896,237.31585l.122-2.82012,5.6101,1.39632a6.26971,6.26971,0,0,0-2.5138-4.61513l5.97581-.33413a64.47667,64.47667,0,0,0-43.1245-26.65136c-12.92583-1.87346-27.31837.83756-36.182,10.43045-4.29926,4.653-7.00067,10.57018-8.92232,16.60685-3.53926,11.11821-4.26038,24.3719,3.11964,33.40938,7.5006,9.18513,20.602,10.98439,32.40592,12.12114,4.15328.4,8.50581.77216,12.35457-.83928a29.721,29.721,0,0,0-1.6539-13.03688,8.68665,8.68665,0,0,1-.87879-4.15246c.5247-3.51164,5.20884-4.39635,8.72762-3.9219s7.74984,1.20031,10.062-1.49432c1.59261-1.85609,1.49867-4.559,1.70967-6.99575C521.28248,239.785,533.83587,238.70653,533.896,237.31585Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><circle cx="559" cy="744.5" r="43" fill="#fed700"/><circle cx="54" cy="729.5" r="43" fill="#fed700"/><circle cx="54" cy="672.5" r="31" fill="#fed700"/><circle cx="54" cy="624.5" r="22" fill="#fed700"/></svg>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <!--  END Notifications dropdown -->

        <div class="nav-item dropdown dropdown-notifications">
            <a href="#" class="nav-link d-flex align-items-center dropdown-toggle" data-toggle="dropdown" data-caret="false">
                <span class="mr-8pt2 d-flex">
                    <span class="my-auto avatar border-2 border-light rounded-circle bg-transparent">
                        <img src="{{ auth()->user()->profile_pic }}" class="avatar-img rounded-circle ">
                    </span>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right rounded-lg px-2 py-3 shadow-lg">
                @include('student.layouts.dropdown-menu-profile')
            </div>
        </div>
    </div>
</div>
