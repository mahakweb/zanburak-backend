@extends('layouts.master')

@section('title', $episode->title)
@section('head')
<link type="text/css" href="https://cdn.plyr.io/3.5.6/plyr.css" rel="stylesheet">
<link type="text/css" href="/assets/css/player/player.css" rel="stylesheet">
<link type="text/css" href="/assets/css/read-more.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/comments/comment-style.css">
<style>
    .episode-number{
        display: none!important;
    }
    .iconOrNumber:hover .episode-icon{
        display: none!important;
    }
    .iconOrNumber:hover .episode-number{
        display: flex!important;
    }
</style>


<link rel="stylesheet" href="/assets/css/editor/easymde/easymde-v2.18.0.css">
<script src="/assets/js/editor/easymde/easymde-v2.18.0.js"></script>

<link href="/assets/css/highlight/11.7.0/rainbow.min.css" rel="stylesheet">
<script src="/assets/js/highlight/11.7.0/highlight.min.js"></script>
<script src="/assets/js/highlight/highlightjs-line-numbers.min.js"></script>
<script>
    hljs.highlightAll();
    hljs.initLineNumbersOnLoad();
</script>

<link href="/assets/css/editor/easymde/custom-style.css" rel="stylesheet">
@endsection

@section('content')

<div class="mdk-header-layout__content page-content ">
    <div class="page-section py-0">
        <div class="container page__container">
            <div class="card card-body border-0 shadow-none pb-0">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="d-flex flex-column">
                            <a href="{{ route('course-single', $course->slug) }}" class="font-bold text-50">
                                <svg class="mr-1" width="18" height="18" viewBox="0 0 25 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" opacity="0.4" d="M19.3329 2.44257C18.9139 2.17861 18.3989 2.15526 17.9599 2.37861L16.4779 3.12683C15.9298 3.40297 15.5898 3.96135 15.5898 4.58267V10.4162C15.5898 11.0375 15.9298 11.5949 16.4779 11.873L17.9589 12.6202C18.1599 12.7238 18.3749 12.7735 18.5899 12.7735C18.8479 12.7735 19.1039 12.7004 19.3329 12.5573C19.7519 12.2943 20.0019 11.8385 20.0019 11.339V3.66186C20.0019 3.16236 19.7519 2.70653 19.3329 2.44257Z"></path>
                                    <path fill="currentColor" d="M9.9051 15H4.11304C1.69102 15 0 13.3299 0 10.9391V4.06091C0 1.66904 1.69102 0 4.11304 0H9.9051C12.3271 0 14.0181 1.66904 14.0181 4.06091V10.9391C14.0181 13.3299 12.3271 15 9.9051 15Z"></path>
                                </svg>
                                رفتن به دوره
                            </a>
                            <h5 class="mt-2">
                                {{ $course->title }}
                            </h5>
                        </div>
                    </div>
                    <div class="col-lg-4 d-flex flex-column mb-2">
                        <div class="my-auto d-flex flex-row border-bottom-2 border-yellow">
                            <div class="font-bold text-50">
                                تعداد جلسات این دوره:
                            </div>
                            <div class="ml-auto font-bold">
                                {{ $course->numberOfEpisode() }}
                                 جلسه
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 px-0">
                        @auth
                            @if(auth()->user()->canGetEpisode($episode)['can'] == false && auth()->user()->canGetEpisode($episode)['type'] == 'cash')
                                <div style="background-image: url({{ $course->poster }});background-repeat: no-repeat;background-size: cover;width:100%!important;" class="mb-24pt">
                                    <div class="d-flex">

                                        <div class="card card-body border-0 py-32pt col-sm-6 mx-auto my-lg-64pt mb-0" style="background-color: rgba(51,65,85, 0.8)">
                                            <div class="d-flex flex-column">
                                                <div class="mx-auto">
                                                    <svg class="text-white" width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path id="Stroke 1" fill-rule="evenodd" clip-rule="evenodd" d="M2.74976 12C2.74976 5.063 5.06276 2.75 11.9998 2.75C18.9368 2.75 21.2498 5.063 21.2498 12C21.2498 18.937 18.9368 21.25 11.9998 21.25C5.06276 21.25 2.74976 18.937 2.74976 12Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path id="Stroke 3" d="M11.9998 8.10498V12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path id="Stroke 15" d="M11.9955 15.5H12.0045" stroke="currentColor" stroke-width="1.7" stroke-opacity="0.6" stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg>
                                                </div>
                                                <div class="my-4 mx-auto text-center font-size-16pt text-white">
                                                    برای مشاهده این دوره ابتدا نیاز است، این دوره را خریداری کنید
                                                </div>
                                                <div class="mx-auto">
                                                    <a class="btn btn-yellow" href="{{ route('course-single', $course->slug) }}">
                                                        خرید دوره
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @elseif (auth()->user()->canGetEpisode($episode)['can'] == false && auth()->user()->canGetEpisode($episode)['type'] == 'cash-vip')
                                <div style="background-image: url({{ $course->poster }});background-repeat: no-repeat;background-size: cover;width:100%!important;" class="mb-24pt">
                                    <div class="d-flex">

                                        <div class="card card-body border-0 py-32pt col-sm-6 mx-auto my-lg-64pt mb-0" style="background-color: rgba(51,65,85, 0.8)">
                                            <div class="d-flex flex-column">
                                                <div class="mx-auto">
                                                    <svg class="text-white" width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path id="Stroke 1" fill-rule="evenodd" clip-rule="evenodd" d="M2.74976 12C2.74976 5.063 5.06276 2.75 11.9998 2.75C18.9368 2.75 21.2498 5.063 21.2498 12C21.2498 18.937 18.9368 21.25 11.9998 21.25C5.06276 21.25 2.74976 18.937 2.74976 12Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path id="Stroke 3" d="M11.9998 8.10498V12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        <path id="Stroke 15" d="M11.9955 15.5H12.0045" stroke="currentColor" stroke-width="1.7" stroke-opacity="0.6" stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg>
                                                </div>
                                                <div class="my-4 mx-auto text-center font-size-16pt text-white">
                                                    عضو ویژه سایت شوید و به شکل رایگان مشاهده کنید یا به شکل نقدی این دوره را خریداری کنید
                                                </div>
                                                <div class="mx-auto d-flex flex-row">
                                                    <a class="btn btn-light" href="{{ route('vip') }}">
                                                        عضویت ویژه
                                                    </a>
                                                    <a class="btn btn-yellow ml-2" href="{{ route('course-single', $course->slug) }}">
                                                        خرید نقدی دوره
                                                        </a>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @else
                                <div class="text-center p-0 mb-24pt">

                                    <video id="video" controls crossorigin playsinline poster="{{ $course->poster }}" class="js-player w-100">

                                    </video>

                                    @section('script')
                                    @parent
                                        <script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=es6,Array.prototype.includes,CustomEvent,Object.entries,Object.values,URL"></script>
                                        <script src="https://unpkg.com/plyr@3"></script>
                                        <script src="https://cdn.rawgit.com/video-dev/hls.js/18bb552/dist/hls.min.js"></script>
                                        <script>
                                            var source = "{{ route('episode-video', $episode) }}";
                                        </script>
                                        <script src="/assets/js/video-source.js"></script>
                                    @endsection

                                </div>
                                <div class="mx-1">
                                    <a href="#" class="text-50 font-bold">
                                        ویدیو‌ها رو آنلاین مشاهده کنید و از مزیت‌های مشاهده آنلاین بهرمند بشید
                                    </a>
                                </div>
                                <hr>
                            @endif

                        @endauth
                        @guest
                            <div style="background-image: url({{ $course->poster }});background-repeat: no-repeat;background-size: cover;width:100%!important;" class="mb-24pt">
                                <div class="d-flex">

                                    <div class="card card-body border-0 py-32pt col-sm-6 mx-auto my-lg-64pt mb-0" style="background-color: rgba(51,65,85, 0.8)">
                                        <div class="d-flex flex-column">
                                            <div class="mx-auto">
                                                <svg width="40" height="42" viewBox="0 0 40 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.4" d="M10.2007 9.42888C10.2007 4.32263 14.4919 0.166382 19.7662 0.166382H30.2501C35.5115 0.166382 39.792 4.31221 39.792 9.40805V32.5664C39.792 37.6747 35.5029 41.833 30.2286 41.833H19.7447C14.4833 41.833 10.2007 37.6851 10.2007 32.5872V30.6289V9.42888Z" fill="white"></path>
                                                    <path d="M28.4119 19.8619L22.2288 13.8015C21.5897 13.1765 20.5613 13.1765 19.9244 13.8056C19.2895 14.4348 19.2917 15.4494 19.9286 16.0744L23.3143 19.3931H1.83835C0.939027 19.3931 0.208984 20.1119 0.208984 20.9994C0.208984 21.8848 0.939027 22.6015 1.83835 22.6015H23.3143L19.9286 25.9223C19.2917 26.5473 19.2895 27.5619 19.9244 28.1911C20.2439 28.5057 20.6608 28.664 21.0797 28.664C21.4945 28.664 21.9113 28.5056 22.2288 28.1952L28.4119 22.1348C28.7187 21.8327 28.8922 21.4244 28.8922 20.9994C28.8922 20.5723 28.7187 20.164 28.4119 19.8619" fill="white"></path>
                                                </svg>
                                            </div>
                                            <div class="my-4 mx-auto text-center font-size-16pt text-white">
                                                برای مشاهده دوره ابتدا لازمه وارد بشی یا ثبت‌نام کنی
                                            </div>
                                            <div class="mx-auto">
                                                <a class="btn btn-yellow" href="{{ route('login') }}">
                                                    ورود و عضویت
                                                    <svg class="ml-2" width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path opacity="0.4" d="M17.3829 6.78063H16.2968V5.71874C16.2968 5.26539 15.9332 4.89575 15.4852 4.89575C15.0382 4.89575 14.6737 5.26539 14.6737 5.71874V6.78063H13.5894C13.1414 6.78063 12.7778 7.15027 12.7778 7.60362C12.7778 8.05696 13.1414 8.4266 13.5894 8.4266H14.6737V9.48943C14.6737 9.94278 15.0382 10.3124 15.4852 10.3124C15.9332 10.3124 16.2968 9.94278 16.2968 9.48943V8.4266H17.3829C17.83 8.4266 18.1945 8.05696 18.1945 7.60362C18.1945 7.15027 17.83 6.78063 17.3829 6.78063Z" fill="currentColor"></path>
                                                        <path d="M6.9095 11.6807C3.25707 11.6807 0.138672 12.2646 0.138672 14.5976C0.138672 16.9297 3.23809 17.5347 6.9095 17.5347C10.561 17.5347 13.6803 16.9508 13.6803 14.6178C13.6803 12.2847 10.5809 11.6807 6.9095 11.6807Z" fill="currentColor"></path>
                                                        <path opacity="0.4" d="M6.90984 9.45868C9.39661 9.45868 11.39 7.4396 11.39 4.92078C11.39 2.40196 9.39661 0.381958 6.90984 0.381958C4.42308 0.381958 2.42969 2.40196 2.42969 4.92078C2.42969 7.4396 4.42308 9.45868 6.90984 9.45868Z" fill="currentColor"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endguest
                        <div class="mx-2 pb-3">

                            <div class="d-flex flex-column flex-md-row flex-lg-row">
                                <div class="d-flex flex-column flex-lg-row">
                                    @auth
                                        @if (auth()->user()->canGetEpisode($episode)['can'])
                                        <div class="mx-auto mb-4 mb-lg-0">
                                            <form id="download-video" action="{{ route('episode-download-check', $episode) }}" method="GET">
                                                @csrf
                                                <button type="submit" class="btn btn-yellow">
                                                    ایجاد لینک دانلود
                                                    <svg class="ml-1 mb-1" width="17" height="18" viewBox="0 0 17 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect fill="gray" class="" x="9.20898" y="5.95801" width="5.66667" height="1.41667" rx="0.708333" transform="rotate(90 9.20898 5.95801)"></rect>
                                                        <path fill="gray" class="" d="M7.79102 13.0413C7.79102 12.6501 8.10815 12.333 8.49935 12.333V12.333C8.89055 12.333 9.20768 12.6501 9.20768 13.0413V13.0413C9.20768 13.4325 8.89055 13.7497 8.49935 13.7497V13.7497C8.10815 13.7497 7.79102 13.4325 7.79102 13.0413V13.0413Z"></path>
                                                        <path fill="gray" class="" fill-rule="evenodd" clip-rule="evenodd" d="M0 9.5C0 16.4997 1.50025 18 8.5 18C15.4997 18 17 16.4997 17 9.5C17 2.50025 15.4997 1 8.5 1C1.50025 1 0 2.50025 0 9.5ZM1.41667 9.5C1.41667 11.2176 1.51055 12.5012 1.72671 13.4738C1.9393 14.4304 2.25086 14.9972 2.62683 15.3732C3.0028 15.7491 3.56964 16.0607 4.5262 16.2733C5.49884 16.4895 6.7824 16.5833 8.5 16.5833C10.2176 16.5833 11.5012 16.4895 12.4738 16.2733C13.4304 16.0607 13.9972 15.7491 14.3732 15.3732C14.7491 14.9972 15.0607 14.4304 15.2733 13.4738C15.4895 12.5012 15.5833 11.2176 15.5833 9.5C15.5833 7.7824 15.4895 6.49884 15.2733 5.5262C15.0607 4.56964 14.7491 4.0028 14.3732 3.62683C13.9972 3.25086 13.4304 2.9393 12.4738 2.72671C11.5012 2.51055 10.2176 2.41667 8.5 2.41667C6.7824 2.41667 5.49884 2.51055 4.5262 2.72671C3.56964 2.9393 3.0028 3.25086 2.62683 3.62683C2.25086 4.0028 1.9393 4.56964 1.72671 5.5262C1.51055 6.49884 1.41667 7.7824 1.41667 9.5Z" fill-opacity="0.4"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                        @endif
                                    @endauth

                                    <div class="d-flex flex-row align-items-center justify-content-center justify-content-md-start justify-content-lg-start mx-2">
                                        <div class="bg-light rounded-lg">
                                            <form id="send-like" action="{{ route('send-like') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="likeable_id" value="{{ $episode->id }}">
                                                <input type="hidden" name="likeable_type" value="{{ get_class($episode) }}">
                                                <button type="submit" class="mx-2 px-1 py-1 rounded btn text-50">
                                                    <svg class="mr-1 text-accent" width="20" height="20"  viewBox="0 0 15 13" xmlns="http://www.w3.org/2000/svg">
                                                        <path id="fillOrEmpty" fill="@auth {{ auth()->user()->hasLiked($course) ? 'currentColor' : 'none' }} @else none @endauth" stroke="currentColor" d="M4.75 0.624878C5.80649 0.624878 6.77021 1.15065 7.5 1.74964C8.22979 1.15065 9.19351 0.624878 10.25 0.624878C12.5282 0.624878 14.375 2.31858 14.375 4.40774C14.375 8.62007 9.57964 11.0733 7.99879 11.7676C7.68036 11.9075 7.31964 11.9075 7.00121 11.7676C5.42036 11.0733 0.625 8.61997 0.625 4.40764C0.625 2.31848 2.47183 0.624878 4.75 0.624878Z" stroke-width="0.771644"></path>
                                                    </svg>
                                                    <span class="ml-1 text-accent" id="countOfLike">{{ $episode->likes()->count() }}</span>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="bg-light rounded-lg mx-2">
                                            <a href="#comments-list" class="btn px-2 py-1 text-50">
                                                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill="gray" fill-rule="evenodd" d="M2 6a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-4.172a1 1 0 0 0-.707.293l-1.867 1.867C11.054 22.361 9 21.51 9 19.812A.812.812 0 0 0 8.188 19H5a3 3 0 0 1-3-3V6zm5 0a1 1 0 0 0 0 2h10a1 1 0 1 0 0-2H7zm0 4a1 1 0 1 0 0 2h10a1 1 0 1 0 0-2H7zm0 4a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2H7z" clip-rule="evenodd"></path></g></svg>
                                                <span class="ml-1">{{ $episode->comments()->where('approved', 1)->count() }}</span>
                                            </a>
                                        </div>
                                        <div class="bg-light rounded-lg">
                                            <form id="send-bookmark" action="{{ route('send-bookmark') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="bookmarkable_id" value="{{ $episode->id }}">
                                                <input type="hidden" name="bookmarkable_type" value="{{ get_class($episode) }}">
                                                <button type="submit" class="mx-2 px-1 py-1 rounded btn">
                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="style=fill">
                                                            <g id="bookmark">
                                                                <path id="fillOrEmpty" stroke-width="2" stroke="@auth {{ auth()->user()->hasBookmarked($episode) ? 'none' : 'currentColor' }} @else currentColor @endauth" fill="@auth {{ auth()->user()->hasBookmarked($episode) ? 'currentColor' : 'none' }} @else none @endauth" fill-rule="evenodd" clip-rule="evenodd" d="M8 1.25C5.37665 1.25 3.25 3.37665 3.25 6V20.4648C3.25 21.7269 4.27311 22.75 5.53518 22.75C5.98634 22.75 6.42739 22.6165 6.80278 22.3662L11.3066 19.3636C11.7265 19.0837 12.2735 19.0837 12.6934 19.3636L17.1972 22.3662C17.5726 22.6165 18.0137 22.75 18.4648 22.75C19.7269 22.75 20.75 21.7269 20.75 20.4648V6C20.75 3.37665 18.6234 1.25 16 1.25H8ZM9 6.75C8.58579 6.75 8.25 7.08579 8.25 7.5C8.25 7.91421 8.58579 8.25 9 8.25H15C15.4142 8.25 15.75 7.91421 15.75 7.5C15.75 7.08579 15.4142 6.75 15 6.75H9Z"/>
                                                            </g>
                                                        </g>
                                                    </svg>
                                                    <span class="ml-1" id="countOfBookmark">{{ $episode->bookmarkersCount() }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-lg-auto ml-md-auto d-flex flex-row align-items-center justify-content-center">
                                   <div class="d-none d-md-block d-lg-block">
                                        <span class="font-size-16pt text-50">
                                            اشتراک گذاری:
                                        </span>
                                        <a href="https://telegram.me/share/url?url={{ url()->current() }}&text={{ $episode->title }}" target="_blank" class="mx-2">
                                            <i class="fab fa-telegram font-size-20pt"></i>
                                        </a>
                                        <a href="https://twitter.com/share?text={{ $episode->title }}&url={{ url()->current() }}" target="_blank" class="mx-2">
                                            <i class="fab fa-twitter font-size-20pt"></i>
                                        </a>
                                        <a href="https://www.instagram.com/?url={{ url()->current() }}" target="_blank" class="mx-2">
                                            <i class="fab fa-instagram font-size-20pt"></i>
                                        </a>
                                   </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 px-0 pb-1">
                        <div class="accordion js-accordion accordion--boxed pr-1" id="parent" style="max-height: 525px; overflow-y: auto">
                            @php
                            $number = array("","اول","دوم","سوم","چهارم","پنجم","ششم","هفتم","هشتم","نهم","دهم","یازدهم","دوازدهم","سیزدهم","چهاردهم","پانزدهم","شانزدهم","هفدهم","هجدهم","نوزدهم","بیستم","بیست و یکم","بیست و دوم","بیست و سوم","بیست و چهارم","بیست و پنجم")
                            @endphp
                            @foreach($course->section as $section)
                            <div class="accordion__item rounded-0 my-0 bg-light border-0 shadow-none @if($section->episode->contains($episode)) open @endif">
                                <a href="#" class="accordion__toggle collapsed" data-toggle="collapse" data-target="#course-toc-{{ $loop->iteration }}" data-parent="#parent">
                                    <span class="flex font-size-14pt font-bold">{{ ' بخش '.$number[$loop->iteration]}} <span class="mr-2 ml-2">|</span> {{$section->title }}</span>
                                    <span class="accordion__toggle-icon ">
                                        <svg class="" width="15" height="15" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                                            <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                                        </svg>
                                    </span>
                                </a>
                                <div class="accordion__menu @if($section->episode->contains($episode)) show @endif collapse" id="course-toc-{{ $loop->iteration }}">
                                    @foreach($section->episode as $sectionEpisode)
                                    <div class="accordion__menu-link {{ ($episode->id == $sectionEpisode->id) ? 'bg-yellow here' : '' }}">
                                        <span class="icon-holder icon-holder--small icon-holder--dark rounded-circle d-inline-flex icon--left">
                                            <i class="material-icons icon-16pt">
                                                @auth
                                                    @if (auth()->user()->canGetEpisode($sectionEpisode)['can'])
                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier">
                                                                <path fill="currentColor" d="M11.9688 2C6.44875 2 1.96875 6.48 1.96875 12C1.96875 17.52 6.44875 22 11.9688 22C17.4888 22 21.9688 17.52 21.9688 12C21.9688 6.48 17.4988 2 11.9688 2ZM14.9688 14.23L12.0687 15.9C11.7087 16.11 11.3088 16.21 10.9187 16.21C10.5188 16.21 10.1287 16.11 9.76875 15.9C9.04875 15.48 8.61875 14.74 8.61875 13.9V10.55C8.61875 9.72 9.04875 8.97 9.76875 8.55C10.4888 8.13 11.3487 8.13 12.0787 8.55L14.9787 10.22C15.6987 10.64 16.1287 11.38 16.1287 12.22C16.1287 13.06 15.6987 13.81 14.9688 14.23Z"></path>
                                                            </g>
                                                        </svg>
                                                    @else
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier">
                                                                <path fill="currentColor" d="M12 7.75C10.11 7.75 9.75 8.54 9.75 10V10.62H14.25V10C14.25 8.54 13.89 7.75 12 7.75Z"></path>
                                                                <path fill="currentColor" d="M11.9984 15.0984C12.606 15.0984 13.0984 14.606 13.0984 13.9984C13.0984 13.3909 12.606 12.8984 11.9984 12.8984C11.3909 12.8984 10.8984 13.3909 10.8984 13.9984C10.8984 14.606 11.3909 15.0984 11.9984 15.0984Z"></path>
                                                                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM17.38 14.5C17.38 16.7 16.7 17.38 14.5 17.38H9.5C7.3 17.38 6.62 16.7 6.62 14.5V13.5C6.62 11.79 7.03 11 8.25 10.73V10C8.25 9.07 8.25 6.25 12 6.25C15.75 6.25 15.75 9.07 15.75 10V10.73C16.97 11 17.38 11.79 17.38 13.5V14.5Z"></path>
                                                            </g>
                                                        </svg>
                                                    @endif
                                                @endauth
                                                @guest
                                                    @if ($sectionEpisode->lock())
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier">
                                                                <path fill="currentColor" d="M12 7.75C10.11 7.75 9.75 8.54 9.75 10V10.62H14.25V10C14.25 8.54 13.89 7.75 12 7.75Z"></path>
                                                                <path fill="currentColor" d="M11.9984 15.0984C12.606 15.0984 13.0984 14.606 13.0984 13.9984C13.0984 13.3909 12.606 12.8984 11.9984 12.8984C11.3909 12.8984 10.8984 13.3909 10.8984 13.9984C10.8984 14.606 11.3909 15.0984 11.9984 15.0984Z"></path>
                                                                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM17.38 14.5C17.38 16.7 16.7 17.38 14.5 17.38H9.5C7.3 17.38 6.62 16.7 6.62 14.5V13.5C6.62 11.79 7.03 11 8.25 10.73V10C8.25 9.07 8.25 6.25 12 6.25C15.75 6.25 15.75 9.07 15.75 10V10.73C16.97 11 17.38 11.79 17.38 13.5V14.5Z"></path>
                                                            </g>
                                                        </svg>
                                                    @else
                                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                            <g id="SVGRepo_iconCarrier">
                                                                <path fill="currentColor" d="M11.9688 2C6.44875 2 1.96875 6.48 1.96875 12C1.96875 17.52 6.44875 22 11.9688 22C17.4888 22 21.9688 17.52 21.9688 12C21.9688 6.48 17.4988 2 11.9688 2ZM14.9688 14.23L12.0687 15.9C11.7087 16.11 11.3088 16.21 10.9187 16.21C10.5188 16.21 10.1287 16.11 9.76875 15.9C9.04875 15.48 8.61875 14.74 8.61875 13.9V10.55C8.61875 9.72 9.04875 8.97 9.76875 8.55C10.4888 8.13 11.3487 8.13 12.0787 8.55L14.9787 10.22C15.6987 10.64 16.1287 11.38 16.1287 12.22C16.1287 13.06 15.6987 13.81 14.9688 14.23Z"></path>
                                                            </g>
                                                        </svg>
                                                    @endif
                                                @endguest
                                            </i>
                                        </span>
                                        <div class="d-flex flex-column">
                                            <a class="flex font-bold" href="{{ route('episode-single', [$course->slug, $sectionEpisode->id]) }}">{{ Str::words($sectionEpisode->title, "7", "...") }}</a>
                                            <span class="text-muted mt-4pt">ویدیو آموزشی | {{ gmdate("i:s", $sectionEpisode->total_time) }}</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    @auth
                    @if ($episode->attachs->count() > 0 && auth()->user()->canGetEpisode($episode)['can'])
                    <div id="attachments" class="card card-body border-0 shadow-none">
                        <h5>
                            <i class="fa fa-circle mr-1 font-size-8pt"></i>
                            فایل پیوست
                        </h5>
                        @foreach ($episode->attachs as $attach)
                            <div class="card card-body py-2 my-1 border-0 shadow-none bg-light d-flex flex-row">
                                <span class="icon-holder icon-holder--small rounded-lg d-inline-flex icon--left">
                                    <span class="border-bottom-3 border-dark px-1">
                                        <span class="font-size-16pt font-fat text-dark">{{ $loop->iteration }}</span>
                                    </span>
                                </span>
                                <div class="border-left-1 pl-2 d-flex flex-row w-100">
                                    <a href="#" class="font-size-16pt my-auto">{{ $attach->title }}</a>
                                    <a href="#" class="btn btn-sm btn-light rounded-lg ml-auto my-auto">
                                        دانلود
                                        <svg class="ml-2" width="16" height="15" viewBox="0 0 16 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="currentColor" d="M6.97978 8.74408L7.83333 9.59763V8.39052V0.666667C7.83333 0.574619 7.90795 0.5 8 0.5C8.09205 0.5 8.16667 0.57462 8.16667 0.666667V8.39052V9.59763L9.02022 8.74408L11.2155 6.54882C11.2806 6.48373 11.3861 6.48373 11.4512 6.54882C11.5163 6.6139 11.5163 6.71943 11.4512 6.78452L8.11785 10.1179C8.05276 10.1829 7.94724 10.1829 7.88215 10.1179L4.54882 6.78452C4.48373 6.71943 4.48373 6.6139 4.54882 6.54882L4.20389 6.20389L4.54882 6.54882C4.6139 6.48373 4.71943 6.48373 4.78452 6.54882L6.97978 8.74408Z"></path>
                                            <path fill="currentColor" d="M3.33333 3.53069C3.33333 3.08579 2.91247 2.75401 2.4991 2.9185C0.537971 3.69885 0 5.32099 0 8.47052C0 13.573 1.412 14.6666 8 14.6666C14.588 14.6666 16 13.573 16 8.47052C16 5.32099 15.462 3.69885 13.5009 2.9185C13.0875 2.75401 12.6667 3.08579 12.6667 3.53069C12.6667 3.83233 12.8673 4.0911 13.1427 4.21415C13.3515 4.30748 13.5184 4.4081 13.6541 4.51316C14.2793 4.9974 14.6667 5.92139 14.6667 8.47052C14.6667 11.0196 14.2793 11.9436 13.6541 12.4279C13.3051 12.6982 12.7492 12.9392 11.8031 13.102C10.8574 13.2648 9.62397 13.3333 8 13.3333C6.37603 13.3333 5.14257 13.2648 4.19687 13.102C3.25081 12.9392 2.69494 12.6982 2.34594 12.4279C1.72071 11.9436 1.33333 11.0196 1.33333 8.47052C1.33333 5.92139 1.72071 4.9974 2.34594 4.51316C2.48158 4.4081 2.64846 4.30748 2.85735 4.21415C3.13275 4.0911 3.33333 3.83233 3.33333 3.53069Z" opacity="0.4"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                    @endauth
                    @if($episode->description)
                    <div class="card card-body border-0 shadow-none">
                        <h5>
                            <i class="fa fa-circle mr-1 font-size-8pt"></i>
                            توضیحات
                        </h5>
                        <p class=" text-justify text-70 mb-24pt font-size-16pt more">{!! $episode->description !!}</p>
                    </div>
                    @endif
                    <div id="comments-list">
                        <div class="page-separator">
                            <div class="page-separator__text">دیدگاه و پرسش سوال</div>
                        </div>
                        @include('comment.comments', ['model' => $episode])
                    </div>

                </div>
                <div class="col-lg-4">

                    <div class="card card-body border-0 shadow-none">
                        <a href="{{ route('course-single', $course->slug) }}" class="d-flex flex-nowrap">
                            <span class="avatar rounded mr-16pt">
                                <img src="{{ $course->poster }}" alt="{{ $course->title }}" class="avatar-img rounded">
                            </span>
                            <span class="flex d-flex flex-column align-items-start">
                                <span class="card-title mb-2">{{ $course->title }}</span>
                                <span class="card-subtitle text-50">{{ $course->numberOfEpisode().'قسمت | '.$course->numberOfSection().' فصل ' }} </span>
                            </span>
                        </a>
                    </div>


                    <div class="card card-body border-0 shadow-none">
                        <div class="d-flex flex-row">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

                                <path fill="#fed700" fill-rule="evenodd" clip-rule="evenodd" d="M7.75024 5C7.75024 2.65279 9.65303 0.75 12.0002 0.75C14.3475 0.75 16.2502 2.65279 16.2502 5V12C16.2502 14.3472 14.3475 16.25 12.0002 16.25C9.65303 16.25 7.75024 14.3472 7.75024 12V5Z"/>
                                <path fill="#fed700" fill-rule="evenodd" clip-rule="evenodd" d="M12.0002 17.75C12.4145 17.75 12.7502 18.0858 12.7502 18.5V22.5C12.7502 22.9142 12.4145 23.25 12.0002 23.25C11.586 23.25 11.2502 22.9142 11.2502 22.5V18.5C11.2502 18.0858 11.586 17.75 12.0002 17.75Z"/>
                                <path fill="#fed700" fill-rule="evenodd" clip-rule="evenodd" d="M5.00024 10.75C5.41446 10.75 5.75024 11.0858 5.75024 11.5C5.75024 12.3208 5.91191 13.1335 6.226 13.8918C6.54009 14.6501 7.00046 15.3391 7.58083 15.9194C8.16119 16.4998 8.85019 16.9602 9.60847 17.2742C10.3668 17.5883 11.1795 17.75 12.0002 17.75C12.821 17.75 13.6337 17.5883 14.392 17.2742C15.1503 16.9602 15.8393 16.4998 16.4197 15.9194C17 15.3391 17.4604 14.6501 17.7745 13.8918C18.0886 13.1335 18.2502 12.3208 18.2502 11.5C18.2502 11.0858 18.586 10.75 19.0002 10.75C19.4145 10.75 19.7502 11.0858 19.7502 11.5C19.7502 12.5177 19.5498 13.5255 19.1603 14.4658C18.7708 15.4061 18.2 16.2604 17.4803 16.9801C16.7607 17.6997 15.9063 18.2706 14.966 18.6601C14.0258 19.0495 13.018 19.25 12.0002 19.25C10.9825 19.25 9.97472 19.0495 9.03445 18.6601C8.09417 18.2706 7.23982 17.6997 6.52017 16.9801C5.80051 16.2604 5.22965 15.4061 4.84018 14.4658C4.4507 13.5255 4.25024 12.5177 4.25024 11.5C4.25024 11.0858 4.58603 10.75 5.00024 10.75Z"/>
                                <defs>
                                    <rect fill="#fed700" width="24" height="24" fill="white" transform="matrix(-4.37114e-08 1 1 4.37114e-08 0 0)"/>
                                </defs>
                            </svg>
                            <div class="font-bold ml-2 my-auto ">
                                <span class="text-50 mr-2">وضعیت دوره: </span>
                                {{ $course->status->title }}
                            </div>
                        </div>
                    </div>

                    @if ($course->type == 'cash-vip')
                        <div class="card card-body border-0 shadow-none">
                            <div class="d-flex flex-row">
                                <span class="rounded-circle d-flex justify-center items-center mr-3" style="width: 40px!important;height:30px!important;background-color: #fffbe3">
                                    <svg class="m-auto" width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.51948 0C7.20651 0 6.97288 0.170496 6.84031 0.290642C6.69449 0.422804 6.56604 0.590066 6.45512 0.757278C6.23117 1.09488 6.0115 1.53595 5.81242 1.98501C5.41124 2.88996 5.04899 3.93049 4.85966 4.49853C4.85688 4.50686 4.8487 4.51341 4.8388 4.51375C4.24561 4.5338 3.15848 4.587 2.21094 4.72596C1.74282 4.79461 1.27138 4.88927 0.903294 5.02633C0.722052 5.09382 0.524397 5.1864 0.361363 5.3207C0.196358 5.45663 0 5.69486 0 6.0384C0 6.27599 0.0898927 6.48632 0.171263 6.63641C0.258481 6.79729 0.373748 6.95673 0.497041 7.10728C0.744163 7.40905 1.07092 7.73208 1.40876 8.03931C2.08766 8.65669 2.87387 9.26634 3.32118 9.60341C3.32824 9.60872 3.33152 9.6176 3.3286 9.62718C3.15682 10.1912 2.85847 11.2231 2.66868 12.1802C2.57441 12.6556 2.50162 13.1382 2.49068 13.5458C2.48525 13.7485 2.49418 13.9584 2.53471 14.1508C2.57247 14.33 2.65544 14.5816 2.86777 14.7693C3.10609 14.9799 3.39291 15.0078 3.59151 14.9955C3.7946 14.983 3.99799 14.9242 4.17924 14.8558C4.54528 14.7176 4.95846 14.4865 5.35463 14.2369C6.15498 13.7327 7.00245 13.0782 7.4844 12.6934C7.49312 12.6864 7.50593 12.6863 7.51508 12.6936C7.99693 13.0787 8.84509 13.7336 9.65143 14.2381C10.0508 14.488 10.4682 14.7192 10.8406 14.8572C11.0254 14.9256 11.2309 14.9834 11.4358 14.9956C11.637 15.0076 11.9159 14.9794 12.1539 14.784C12.3769 14.601 12.4695 14.3495 12.5125 14.163C12.5578 13.9666 12.5683 13.7533 12.5633 13.549C12.5532 13.1384 12.4766 12.654 12.3774 12.1788C12.1776 11.2215 11.8614 10.1893 11.6785 9.62293C11.6752 9.61289 11.6787 9.60353 11.686 9.59801C12.1355 9.25907 12.92 8.65026 13.5966 8.03439C13.9333 7.72792 14.2588 7.4058 14.5049 7.10485C14.6278 6.95469 14.7426 6.79564 14.8294 6.6351C14.9105 6.48527 15 6.27544 15 6.0384C15 5.69522 14.804 5.45711 14.6392 5.32114C14.4763 5.18683 14.2789 5.09425 14.0979 5.02677C13.7303 4.88973 13.2595 4.79507 12.792 4.72639C11.8457 4.58739 10.7594 4.53404 10.1649 4.51387C10.1549 4.51353 10.1468 4.507 10.1441 4.4986C9.95906 3.92888 9.6059 2.88924 9.21283 1.98553C9.0178 1.53715 8.80202 1.09651 8.58089 0.759047C8.47137 0.59192 8.34406 0.424329 8.19875 0.291738C8.06628 0.170864 7.83292 0 7.51948 0Z" fill="#fed700"></path>
                                    </svg>
                                </span>
                                <div class="font-bold text-70">
                                   اعضای ویژه می‌توانند بصورت رایگان این دوره را مشاهده نمایند.
                                    <a href="{{ route('vip') }}" class="font-bold text-primary ml-2">
                                        عضویت ویژه
                                        <svg class="ml-1" width="15" height="15" viewBox="0 0 21 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="currentColor" opacity="0.4" d="M14.9442 6.2784L19.146 5.9068C20.089 5.9068 20.8535 6.67878 20.8535 7.63094C20.8535 8.5831 20.089 9.35508 19.146 9.35508L14.9442 8.98348C14.2044 8.98348 13.6047 8.3779 13.6047 7.63094C13.6047 6.88273 14.2044 6.2784 14.9442 6.2784"></path>
                                            <path fill="currentColor" d="M0.834251 6.3467C0.899925 6.28039 1.14527 6.00012 1.37575 5.7674C2.72019 4.30976 6.23061 1.92624 8.06699 1.1968C8.34579 1.08044 9.05085 0.832702 9.42878 0.815186C9.78936 0.815186 10.1338 0.899015 10.4622 1.06417C10.8724 1.29564 11.1995 1.66099 11.3804 2.0914C11.4956 2.38918 11.6765 3.28378 11.6765 3.30005C11.8562 4.27723 11.9541 5.86624 11.9541 7.62291C11.9541 9.2945 11.8562 10.8185 11.7088 11.8119C11.6926 11.8294 11.5117 12.9392 11.3147 13.3196C10.9541 14.0152 10.2491 14.4457 9.49445 14.4457H9.42878C8.93685 14.4294 7.90342 13.9977 7.90342 13.9827C6.16494 13.2533 2.73754 10.9849 1.35964 9.47718C1.35964 9.47718 0.970554 9.08931 0.802034 8.84783C0.539341 8.5 0.407995 8.06959 0.407995 7.63918C0.407995 7.15872 0.55545 6.71205 0.834251 6.3467"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                    <div class="row">
                        <div class="col-4">
                            <div class="card card-body border-0 shadow-none">
                                <div class="d-flex flex-column">
                                    <span class="mx-auto">
                                        <svg class="mb-3" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="#fed700" fill-rule="evenodd" clip-rule="evenodd" d="M12.1792 23.9333C2.54046 23.9333 0.474609 21.8674 0.474609 12.2287C0.474609 2.59002 2.54046 0.52417 12.1792 0.52417C21.8178 0.52417 23.8837 2.59002 23.8837 12.2287C23.8837 21.8674 21.8178 23.9333 12.1792 23.9333ZM11.2038 6.37644C11.2038 5.83773 11.6405 5.40106 12.1792 5.40106C12.7178 5.40106 13.1545 5.83773 13.1545 6.37644V11.2533H18.0314C18.5701 11.2533 19.0068 11.69 19.0068 12.2287C19.0068 12.7674 18.5701 13.2041 18.0314 13.2041H12.1792C11.6405 13.2041 11.2038 12.7674 11.2038 12.2287V6.37644Z"></path>
                                        </svg>
                                    </span>
                                    <div class="mx-auto text-50">مدت دوره:</div>
                                    <p class="mx-auto font-bold mb-0">{{ gmdate("H:i:s", $course->totalTime()) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card card-body border-0 shadow-none">
                                <div class="d-flex flex-column">
                                    <span class="mx-auto">
                                        <svg class="mb-3" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="#fed700" d="M0.452148 5.7989C0.452148 10.1426 1.38314 11.0736 5.72688 11.0736C10.0706 11.0736 11.0016 10.1426 11.0016 5.7989C11.0016 1.45516 10.0706 0.52417 5.72688 0.52417C1.38314 0.52417 0.452148 1.45516 0.452148 5.7989Z"></path>
                                            <path fill="#fed700" d="M0.452148 18.2664C0.452148 22.6102 1.38314 23.5412 5.72688 23.5412C10.0706 23.5412 11.0016 22.6102 11.0016 18.2664C11.0016 13.9227 10.0706 12.9917 5.72688 12.9917C1.38314 12.9917 0.452148 13.9227 0.452148 18.2664Z"></path>
                                            <path fill="#fed700" d="M12.9197 5.7989C12.9197 10.1426 13.8507 11.0736 18.1944 11.0736C22.5382 11.0736 23.4691 10.1426 23.4691 5.7989C23.4691 1.45516 22.5382 0.52417 18.1944 0.52417C13.8507 0.52417 12.9197 1.45516 12.9197 5.7989Z"></path>
                                            <path fill="#fed700" d="M12.9197 18.2664C12.9197 22.6102 13.8507 23.5412 18.1944 23.5412C22.5382 23.5412 23.4691 22.6102 23.4691 18.2664C23.4691 13.9227 22.5382 12.9917 18.1944 12.9917C13.8507 12.9917 12.9197 13.9227 12.9197 18.2664Z"></path>
                                        </svg>
                                    </span>
                                    <div class="mx-auto text-50 text-nowrap">تعداد جلسات:</div>
                                    <p class="mx-auto font-bold mb-0">{{ $course->numberOfEpisode() }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card card-body border-0 shadow-none">
                                <div class="d-flex flex-column">
                                    <span class="mx-auto">
                                        <svg class="mb-3" width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="#fed700" fill-rule="evenodd" clip-rule="evenodd" d="M8.57753 1.67007C9.81824 0.0338038 12.2781 0.0338038 13.5188 1.67007L14.5518 3.03246L16.2456 2.79957C18.28 2.51986 20.0193 4.25924 19.7396 6.29356L19.5067 7.98737L20.8691 9.0204C22.5054 10.2611 22.5054 12.721 20.8691 13.9617L19.5067 14.9947L19.7396 16.6885C20.0193 18.7228 18.28 20.4622 16.2456 20.1825L14.5518 19.9496L13.5188 21.312C12.2781 22.9483 9.81824 22.9483 8.57753 21.312L7.5445 19.9496L5.85069 20.1825C3.81636 20.4622 2.07699 18.7228 2.3567 16.6885L2.58958 14.9947L1.2272 13.9617C-0.409067 12.721 -0.409067 10.2611 1.2272 9.0204L2.58958 7.98737L2.3567 6.29356C2.07699 4.25923 3.81637 2.51986 5.85069 2.79957L7.5445 3.03246L8.57753 1.67007ZM15.3819 10.3007C15.7415 9.94114 15.7415 9.3582 15.3819 8.99865C15.0224 8.6391 14.4394 8.6391 14.0799 8.99865L10.1275 12.951L8.93717 11.7607C8.57762 11.4011 7.99468 11.4011 7.63513 11.7607C7.27558 12.1202 7.27558 12.7032 7.63513 13.0627L9.47649 14.9041C9.83604 15.2636 10.419 15.2636 10.7785 14.9041L15.3819 10.3007Z"></path>
                                        </svg>
                                    </span>
                                    <div class="mx-auto text-50">نوع دوره:</div>
                                    <p class="mx-auto font-bold mb-0">
                                        @if ($course->type == 'free')
                                            رایگان
                                        @elseif ($course->type == 'cash')
                                            فقط نقدی
                                        @elseif ($course->type == 'cash-vip')
                                            ویژه / نقدی
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card card-body border-0 shadow-none">
                                <div class="d-flex flex-column">
                                    <span class="mx-auto">
                                        <svg class="mb-3" width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="#fed700" fill-rule="evenodd" clip-rule="evenodd" d="M20.125 7.66667C17.4786 7.66667 15.3333 5.52136 15.3333 2.875C15.3333 1.90785 15.6199 1.00763 16.1127 0.254587C14.817 0.0726507 13.2917 0 11.5 0C2.02975 0 0 2.02975 0 11.5C0 20.9702 2.02975 23 11.5 23C20.9702 23 23 20.9702 23 11.5C23 9.70834 22.9273 8.183 22.7454 6.88733C21.9924 7.38013 21.0921 7.66667 20.125 7.66667ZM5.39746 14.3078C5.88929 14.5024 6.4457 14.2617 6.64075 13.7702C6.64075 13.7702 6.64113 13.7692 5.75 13.4167L6.64113 13.7692L6.64511 13.7594C6.64891 13.7501 6.65517 13.7349 6.6638 13.7144C6.68109 13.6733 6.70783 13.6111 6.74348 13.5322C6.81496 13.3739 6.92116 13.1506 7.05756 12.8963C7.33667 12.376 7.7155 11.7765 8.15035 11.3302C8.60321 10.8655 8.96617 10.7168 9.23262 10.7343C9.47541 10.7502 9.99147 10.9311 10.6951 12.0201C11.5395 13.327 12.5162 14.1044 13.6419 14.1783C14.7438 14.2506 15.6248 13.6207 16.2224 13.0073C16.8381 12.3755 17.3162 11.5974 17.6315 11.0096C17.7923 10.7099 17.9176 10.4465 18.0033 10.2567C18.0463 10.1615 18.0796 10.0842 18.1027 10.0294C18.1142 10.0019 18.1233 9.98002 18.1297 9.96426L18.1374 9.94528L18.1398 9.93935L18.1406 9.9373C18.1406 9.9373 18.1411 9.93587 17.25 9.58333L18.1411 9.93587C18.3358 9.44371 18.0947 8.8869 17.6025 8.6922C17.1107 8.49762 16.5542 8.73834 16.3592 9.2299L16.3589 9.2308L16.3549 9.2406L16.3454 9.26355L16.3362 9.28564C16.3189 9.3267 16.2922 9.38886 16.2565 9.46779C16.185 9.62607 16.0788 9.84937 15.9424 10.1037C15.6633 10.624 15.2845 11.2235 14.8497 11.6698C14.3968 12.1345 14.0339 12.2832 13.7674 12.2657C13.5246 12.2498 13.0086 12.0689 12.3049 10.9799C11.4605 9.67304 10.4838 8.89562 9.35819 8.82172C8.25625 8.74937 7.37521 9.37932 6.77758 9.99267C6.16194 10.6245 5.68379 11.4026 5.36854 11.9904C5.20775 12.2901 5.08243 12.5535 4.99669 12.7433C4.95372 12.8385 4.92042 12.9158 4.89732 12.9706C4.88576 12.9981 4.87674 13.02 4.87031 13.0357L4.86262 13.0547L4.86025 13.0606L4.85944 13.0627C4.85944 13.0627 4.85887 13.0641 5.75 13.4167L4.85887 13.0641C4.66416 13.5563 4.9053 14.1131 5.39746 14.3078Z"></path>
                                            <path fill="#fed700" d="M20.125 5.75C18.5372 5.75 17.25 4.46282 17.25 2.875C17.25 1.28718 18.5372 0 20.125 0C21.7128 0 23 1.28718 23 2.875C23 4.46282 21.7128 5.75 20.125 5.75Z"></path>
                                        </svg>
                                    </span>
                                    <div class="mx-auto text-50">آخرین آپدیت:</div>
                                    <p class="mx-auto font-bold mb-0">{{ jdate($course->updated_at)->format('Y/m/d') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-body border-0 shadow-none">
                        <div class="d-flex flex-column">
                            <div class="">
                                <a class="d-flex justify-content-center" href="{{ route('profile-index', $course->teacher->username) }}">
                                    <span class="avatar avatar-lg {{ Cache::has('is_online' . $course->teacher->id) ? 'border-success' : 'border-light' }} border-2 rounded-circle">
                                        <img src="{{ $course->teacher->profile_pic }}"  alt="avatar" class="avatar-img rounded-circle shadow">
                                    </span>
                                </a>

                                <h5 class="my-2 d-flex justify-content-center">
                                    <a class="" href="{{ route('profile-index', $course->teacher->username) }}">
                                        {{ $course->teacher->first_name.' '.$course->teacher->last_name }}
                                        <svg class="ml-1 text-yellow" width="17" height="18" viewBox="0 0 17 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16.6936 9.39221C16.6936 9.97583 16.5534 10.5171 16.273 11.0127C15.9926 11.5083 15.6176 11.8962 15.1448 12.1669C15.1579 12.2549 15.1644 12.3918 15.1644 12.5777C15.1644 13.4613 14.8677 14.2112 14.2808 14.8307C13.6907 15.4534 12.9799 15.7632 12.1485 15.7632C11.7768 15.7632 11.4214 15.6947 11.0856 15.5577C10.8247 16.0925 10.4498 16.5228 9.95745 16.8521C9.46838 17.1847 8.9304 17.3477 8.34678 17.3477C7.75012 17.3477 7.20888 17.188 6.72633 16.8619C6.24052 16.5391 5.86883 16.1055 5.60799 15.5577C5.27217 15.6947 4.92004 15.7632 4.54508 15.7632C3.71367 15.7632 2.99962 15.4534 2.40296 14.8307C1.8063 14.2112 1.50959 13.458 1.50959 12.5777C1.50959 12.4799 1.52264 12.3429 1.54546 12.1669C1.07269 11.893 0.697739 11.5083 0.417339 11.0127C0.1402 10.5171 0 9.97583 0 9.39221C0 8.77272 0.156502 8.20214 0.466246 7.68699C0.77599 7.17184 1.19333 6.79036 1.715 6.54257C1.57806 6.17088 1.50959 5.79592 1.50959 5.42423C1.50959 4.5439 1.8063 3.79074 2.40296 3.17125C2.99962 2.55176 3.71367 2.23876 4.54508 2.23876C4.91678 2.23876 5.27217 2.30723 5.60799 2.44417C5.86883 1.90945 6.24378 1.47907 6.73611 1.14976C7.22518 0.820458 7.76316 0.654175 8.34678 0.654175C8.9304 0.654175 9.46838 0.820458 9.95745 1.1465C10.4465 1.47581 10.8247 1.90619 11.0856 2.44091C11.4214 2.30397 11.7735 2.2355 12.1485 2.2355C12.9799 2.2355 13.6907 2.54524 14.2808 3.16799C14.871 3.79074 15.1644 4.54064 15.1644 5.42097C15.1644 5.83179 15.1025 6.20348 14.9786 6.53931C15.5002 6.7871 15.9176 7.16858 16.2273 7.68373C16.5371 8.20214 16.6936 8.77272 16.6936 9.39221ZM7.99139 11.906L11.4377 6.74472C11.5257 6.60778 11.5518 6.4578 11.5225 6.29803C11.4899 6.13827 11.4084 6.01111 11.2714 5.92634C11.1345 5.83831 10.9845 5.80896 10.8247 5.83179C10.6617 5.85787 10.5313 5.93612 10.4335 6.07306L7.39799 10.6377L5.99925 9.24223C5.87535 9.11833 5.73189 9.05964 5.57213 9.06616C5.4091 9.07269 5.26891 9.13137 5.14501 9.24223C5.03415 9.35308 4.97872 9.49328 4.97872 9.66283C4.97872 9.82911 5.03415 9.96931 5.14501 10.0834L7.06542 12.0038L7.15997 12.0788C7.27083 12.1538 7.38494 12.1897 7.4958 12.1897C7.71425 12.1864 7.88053 12.0951 7.99139 11.906Z" fill="currentColor"></path>
                                        </svg>
                                    </a>
                                </h5>
                                <span class="d-flex justify-content-center text-muted">مدرس دوره</span>
                            </div>
                            <p class="d-flex justify-content-center mt-3 text-70 text-justify mx-3">{{ $course->teacher->info->about }}</p>
                        </div>

                        {{--  @if(auth()->check() && auth()->user()->id == $course->teacher->id)
                            <a href="{{ route('instructor-profile') }}" class="btn btn-primary">ویرایش پروفایل</a>
                        @else
                            <form id="send-follow" action="{{ route('send-follow') }}" method="post">
                                @csrf
                                <input type="hidden" name="followable_id" value="{{ $course->teacher->id }}">
                                <button type="submit" class="btn btn-white mb-24pt">
                                    <span id="follow-check">
                                        @auth
                                            {{ auth()->user()->isFollowing($course->teacher) ? 'آنفالو کردن' : 'فالو کردن' }}
                                        @else
                                            فالو کردن
                                        @endauth
                                    </span>
                                </button>
                            </form>
                        @endif  --}}
                    </div>
                    <div class="card card-body border-0 shadow-none">
                        <div class="d-flex flex-column">
                            <div class="">
                                <div class="d-flex justify-content-center">
                                    <span class="">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 722.11262 558.1509" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M892.0771,705.04148h-585.082a68.4964,68.4964,0,0,1-66.89649-83.21289l26.13379-118.78711H932.83979l26.13379,118.78711a68.49639,68.49639,0,0,1-66.89648,83.21289Zm-624.23731-200-25.78808,117.2168a66.49673,66.49673,0,0,0,64.94336,80.7832h585.082a66.49674,66.49674,0,0,0,64.94336-80.7832l-25.78809-117.2168Z" transform="translate(-238.47977 -171.03678)" fill="#f2f2f2"/><path d="M817.7855,249.41514l30.69046-4.5739-4.84758-21.34916-18.4,9.26659-37.961-14.30669a7.71684,7.71684,0,1,0-5.0485,10.199Z" transform="translate(-238.47977 -171.03678)" fill="#ffb6b6"/><path d="M880.164,233.45546c-2.37275,8.99484-57.04774,19.14085-57.02046,17.19492.07752-5.53051,3.5933-17.135,1.58543-20.14555-1.14827-1.72168,14.31267-6.121,14.31267-6.121s8.53341-3.28453,19.59642-7.29317a19.72107,19.72107,0,0,1,18.85049,2.60441S882.5367,224.46062,880.164,233.45546Z" transform="translate(-238.47977 -171.03678)" fill="#fed700"/><path d="M351.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M412.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M473.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M534.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M595.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M656.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M717.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M778.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M839.24671,608.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M351.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M412.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M473.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#fed700"/><path d="M534.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M595.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M656.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M717.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M778.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M839.24671,541.03678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M379.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M440.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M501.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M562.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M623.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M684.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M745.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M806.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M867.74671,575.53678h-19a16,16,0,0,0,0,32h19a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M797.37855,504.03678H402.61488a23.64479,23.64479,0,0,1-23.61817-23.61816l.02588-.22559c13.96582-60.42773,13.96045-136.18164-.0166-238.40234l-.00928-.13574a23.64512,23.64512,0,0,1,23.61817-23.61817H797.37855A23.64511,23.64511,0,0,1,820.99671,241.655l-.022.209a566.87235,566.87235,0,0,0,0,238.3457l.022.209A23.64478,23.64478,0,0,1,797.37855,504.03678Z" transform="translate(-238.47977 -171.03678)" fill="#fff"/><path d="M797.37855,504.03678H402.61488a23.64479,23.64479,0,0,1-23.61817-23.61816l.02588-.22559c13.96582-60.42773,13.96045-136.18164-.0166-238.40234l-.00928-.13574a23.64512,23.64512,0,0,1,23.61817-23.61817H797.37855A23.64511,23.64511,0,0,1,820.99671,241.655l-.022.209a566.87235,566.87235,0,0,0,0,238.3457l.022.209A23.64478,23.64478,0,0,1,797.37855,504.03678ZM380.9972,480.53092a21.64307,21.64307,0,0,0,21.61768,21.50586H797.37855a21.64279,21.64279,0,0,0,21.61767-21.51367,568.84463,568.84463,0,0,1,0-238.97266,21.64279,21.64279,0,0,0-21.61767-21.51367H402.61488a21.64308,21.64308,0,0,0-21.61817,21.55078C394.98939,343.95622,394.99036,419.89274,380.9972,480.53092Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M755.99671,288.53678h-319a6.5,6.5,0,0,1,0-13h319a6.5,6.5,0,0,1,0,13Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M755.99671,320.03678h-319a6.5,6.5,0,0,1,0-13h319a6.5,6.5,0,0,1,0,13Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M755.99671,351.53678h-319a6.5,6.5,0,0,1,0-13h319a6.5,6.5,0,0,1,0,13Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M755.99671,383.03678h-319a6.5,6.5,0,0,1,0-13h319a6.5,6.5,0,0,1,0,13Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M755.99671,414.53678h-319a6.5,6.5,0,0,1,0-13h319a6.5,6.5,0,0,1,0,13Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M579.99671,446.03678h-143a6.5,6.5,0,0,1,0-13h143a6.5,6.5,0,0,1,0,13Z" transform="translate(-238.47977 -171.03678)" fill="#fed700"/><path d="M598.17835,495.53678H571.85511a2.65765,2.65765,0,0,1-2.06885-1.01953,3.174,3.174,0,0,1-.60058-2.65234l12.3872-56.0459a2.69956,2.69956,0,0,1,5.32032-.08106l13.936,56.04493a3.1748,3.1748,0,0,1-.55762,2.7041A2.65706,2.65706,0,0,1,598.17835,495.53678Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/><path d="M928.99671,516.53678h-658a13.5,13.5,0,0,1,0-27h658a13.5,13.5,0,0,1,0,27Z" transform="translate(-238.47977 -171.03678)" fill="#f2f2f2"/><polygon points="607.193 311.693 597.591 311.692 593.024 274.657 607.195 274.658 607.193 311.693" fill="#ffb6b6"/><path d="M848.12137,492.03678l-30.95975-.00114v-.39159a12.051,12.051,0,0,1,12.0504-12.05021h.00076l5.65521-4.29034,10.55135,4.291,2.7026.0001Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><polygon points="693.95 299.209 685.357 303.492 664.747 272.384 677.431 266.063 693.95 299.209" fill="#ffb6b6"/><path d="M938.77356,477.48361l-27.709,13.81-.17469-.35047a12.051,12.051,0,0,1,5.40937-16.16039l.00069-.00034,3.14742-6.36255,11.3575-.86655,2.41883-1.20552Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M841.41862,314.52925l52.94493,1.85308,3.95881,7.35208s4.14634,30.07924,2.02286,32.20272-3.18522,2.12348-2.12348,5.83957,3.80663,39.22214,3.80663,39.22214,21.87145,45.36626,22.93319,48.02061,2.12348,1.59261,1.06174,2.65435A39.09813,39.09813,0,0,0,923.369,454.859H906.0645s-9.31724-23.50265-17.846-32.59349a51.63525,51.63525,0,0,1-13.61219-29.178l-7.175-33.96517L852.036,411.14763s-2.65436,51.49442-2.12349,54.14877l.53087,2.65435-20.39562.22781s-2.12348-4.47477-1.06174-6.06738.998-1.15789-.29729-3.76417-.76445-7.02253-.76445-7.02253,7.65474-104.41215,7.65474-106.00476a5.29511,5.29511,0,0,0-.441-2.38825V340.7697l2.03361-7.66Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M864.24632,218.46691c-3.44619,2.04877-5.50772,5.81313-6.69112,9.64368a91.38867,91.38867,0,0,0-3.9272,21.83492l-1.24993,22.18655a91.3502,91.3502,0,0,1-11.627,40.78356,7.12994,7.12994,0,0,0,4.45968,10.32456c19.18914,5.03527,55.533-1.28668,55.533-1.28668s1.548-.516,0-2.064-5.02085-9.28825-5.02085-9.28825l4.12808-27.117,5.16009-54.69713c-6.19215-7.74017-18.64533-12.30294-18.64533-12.30294l-3.21775-5.79194-16.08872,1.28709Z" transform="translate(-238.47977 -171.03678)" fill="#fed700"/><circle cx="635.97946" cy="20.8342" r="15.4554" fill="#ffb8b8"/><path d="M893.50263,190.95324a26.87734,26.87734,0,0,1-3.67594,8.807,5.99486,5.99486,0,0,1-2.00508,2.27656,2.19153,2.19153,0,0,1-2.78481-.34811l-.376-.30633a7.97635,7.97635,0,0,0,1.9494-1.69177,2.71626,2.71626,0,0,0,.52216-2.42975,1.67252,1.67252,0,0,0-2.04685-1.093c-.9051.35507-1.45508,1.66393-2.40191,1.46205-.76585-.16712-.926-1.17659-.926-1.97027.02783-4.1494-1.97724-10.04623-3.52977-9.86521a12.51866,12.51866,0,0,1-4.62973-.61963,12.12965,12.12965,0,0,0-4.63675-.5918c-.11138.01394-.22279.03484-.34114.05571a10.267,10.267,0,0,0-1.03036-2.24873,12.01386,12.01386,0,0,1-.12532,2.54809,23.83181,23.83181,0,0,1-3.857,1.21836c-1.43418.20192-4.588,4.49053-4.73418,4.05191a10.2676,10.2676,0,0,0-1.03037-2.24872,12.01386,12.01386,0,0,1-.12531,2.54809c-.007.0348-.007.06267-.01394.09748-.69621-.926-1.12088-2.05382-.88419-1.19053-2.33227-5.2772-1.42722-9.05759,2.2418-13.50635a7.05517,7.05517,0,0,1,2.75-2.207,3.46792,3.46792,0,0,1,3.38359.33421,11.38389,11.38389,0,0,1,11.29242-2.40888c3.82216,1.32977,6.76713,2.10952,7.35192,6.11966a8.50874,8.50874,0,0,1,8.15257,3.96834A12.84965,12.84965,0,0,1,893.50263,190.95324Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M909.99671,291.38527l.02514-31.02941-21.83183,1.63,6.43726,19.57017-19.77475,35.42138a7.71685,7.71685,0,1,0,9.33816,6.5043Z" transform="translate(-238.47977 -171.03678)" fill="#ffb6b6"/><path d="M903.45817,227.33035c8.54385,3.67964,10.47463,59.25452,8.55424,58.93915-5.458-.89632-16.41325-6.09313-19.68811-4.55362-1.87284.88042-3.93214-15.06177-3.93214-15.06177s-1.98355-8.926-4.30831-20.4609a19.721,19.721,0,0,1,5.36941-18.25633S894.91432,223.65073,903.45817,227.33035Z" transform="translate(-238.47977 -171.03678)" fill="#fed700"/><path d="M375.81748,570.22737a5.23881,5.23881,0,0,1,.34211-8.02572l-5.8656-17.66857,9.34295,2.51,4.1377,16.39356a5.26714,5.26714,0,0,1-7.95716,6.79069Z" transform="translate(-238.47977 -171.03678)" fill="#ffb6b6"/><path d="M392.75058,474.23094c1.55025.88871,10.72239-1.31335,12.51367-.68693-.98881,6.03322,4.38179,3.76921-1.45026,11.69239s-17.57849,31.679-20.24693,40.55766-.02734,21.391-.427,25.599a43.66668,43.66668,0,0,0,.036,7.32415c-3.151-.03982-6.25679.61406-9.457.19641-3.41282-9.657-5.70752-23.40286-7.26568-30.99974s-1.61516-4.38419-.90051-8.18286.88769-1.03827.137-4.67233,2.28537-8.2,5.09062-10.80348c1.20952-3.47889,2.77265-6.72266,3.97008-10.16523C383.52546,486.4351,384.1024,482.01317,392.75058,474.23094Z" transform="translate(-238.47977 -171.03678)" fill="#3f3d56"/><path d="M474.88381,559.02692a5.23879,5.23879,0,0,1-5.97865-5.36516l-17.36876-6.70149,7.87608-5.61769,15.28618,7.2251a5.26715,5.26715,0,0,1,.18515,10.45924Z" transform="translate(-238.47977 -171.03678)" fill="#ffb6b6"/><path d="M411.53417,484.94c1.67143-.632,5.80107-9.11275,7.4232-10.09752,4.02934,4.598,5.69494-.98721,8.1051,8.55116s13.28449,33.70588,18.443,41.40914,16.49713,13.61695,19.49177,16.6a43.66635,43.66635,0,0,0,5.67736,4.62732c-2.03347,2.40735-3.50266,5.22071-5.85913,7.42595-9.62465-3.50308-21.69532-10.46818-28.55069-14.0937s-4.4113-1.53959-6.88976-4.50569-.23737-1.34524-3.52012-3.07542-4.87812-6.9762-5.10508-10.79665c-1.91706-3.14492-3.42783-6.4134-5.32453-9.52591C415.09278,499.81889,412.04562,496.563,411.53417,484.94Z" transform="translate(-238.47977 -171.03678)" fill="#3f3d56"/><polygon points="186.123 366.39 194.791 405.071 139.247 401.616 154.351 363.627 186.123 366.39" fill="#ffb6b6"/><polygon points="176.874 544.917 183.113 544.916 186.081 520.851 176.873 520.851 176.874 544.917" fill="#ffb6b6"/><path d="M413.24084,712.87544l9.86294-.58869v4.22572l9.377,6.47607a2.63953,2.63953,0,0,1-1.49987,4.81163h-11.7422l-2.024-4.17987-.79025,4.17987h-4.42727Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><polygon points="117.905 541.813 124.002 543.135 132.005 520.246 123.006 518.294 117.905 541.813" fill="#ffb6b6"/><path d="M354.97208,709.39342l9.76355,1.51568-.89587,4.12965,7.79087,8.31682a2.63954,2.63954,0,0,1-2.48586,4.38428l-11.47529-2.48939-1.09179-4.51394-1.65844,3.91732-4.32664-.93859Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M434.68851,608.453c-4.37873,24.06477-5.4734,34.45515-5.4734,34.45515s3.28406,3.82832,1.09467,6.01771,0,6.562,0,6.562l-4.923,53.05259-3.96291-.64217-9.5464-1.535-3.44307-.5565,2.18938-49.76853s-2.18938-4.37873-1.09467-5.47345c1.09467-1.09467-1.09471-7.65667-1.09471-7.65667l-2.73365-51.95791-16.95847,56.88094s1.64506,2.18939,0,3.82836c-1.639,1.639-4.91694,9.846-4.91694,9.846l-10.08457,35.28686-.8562,2.99661-5.25938-.9846-5.72416-1.07634-1.0519-.19567-1.87136-.35471-1.20475-.22628-.40977-.07338-1.98144-.37308.59935-2.66026.31186-1.40045,8.53123-37.8921.1957-.87452.20795-.92346s-1.09471-2.73364.54426-4.37873a4.27375,4.27375,0,0,0,1.09471-3.82836,1.49325,1.49325,0,0,1-.14677-.18346,2.27029,2.27029,0,0,1-.3914-.89288,2.96057,2.96057,0,0,1,1.08855-2.752,4.66973,4.66973,0,0,0,.77669-1.113c1.66345-3.14953,2.50128-9.82161,2.50128-9.82161s1.48-52.84464,6.32348-60.9172c.25689-.422,5.39871-8.523,5.66779-8.68811,5.46733-3.27793,48.06278,1.24967,48.06278,1.24967s2.57993,6.93083,2.69613,7.65247C434.07529,573.13556,438.40067,588.03921,434.68851,608.453Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M421.7457,481.16455s.92165-4.78429-7.93483-13.64077c-4.68873-1.56291-12.81266-.652-15.62908,0-13.02424,17.192-10.69228,14.15858-14.08253,23.7643a13.52866,13.52866,0,0,0-.928,6.83773c1.5629,7.81454,4.10025,47.92846,3.57928,50.01234s-4.16776,4.68872-.521,4.68872,50.67527-.82322,49.63333-2.38613S421.7457,481.16455,421.7457,481.16455Z" transform="translate(-238.47977 -171.03678)" fill="#3f3d56"/><circle cx="168.57659" cy="278.54491" r="14.93754" fill="#ffb6b6"/><path d="M394.40092,455.22963c-.08162,1.81151-1.21709,3.37347-2.09351,4.95938a19.78854,19.78854,0,0,0-.18369,18.45966c1.21928,2.31278,2.90747,4.37677,3.8749,6.80567a7.73112,7.73112,0,0,1-.06058,6.52172c-2.61823-2.51922-3.89582-1.189-6.869-3.388,1.16921,3.27673.17249,3.38345,1.34621,6.65666q-7.56916,2.16111-15.14392,4.33382a18.25213,18.25213,0,0,0,5.22148-21.48155c-1.846-3.95845-5.14554-7.22129-6.34675-11.4272a12.39871,12.39871,0,0,1,17.04247-14.71269C392.88618,451.472,394.4831,453.44685,394.40092,455.22963Z" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M413.16307,432.8393c-2.0088-4.38928-7.309-3.4962-12.10128-2.91722a14.894,14.894,0,0,0-11.15917,8.28094,20.35278,20.35278,0,0,0-1.10354,14.1278,15.47841,15.47841,0,0,0,4.88706,8.23865c2.53091,2.03537,14.42859,6.84906,15.419,2.70413-1.23813,5.40754-4.45963,8.48642-.77251,12.63124,1.90974-5.73678,9.9854-11.18566,11.89514-16.92244.28747-.86355-.89305-11.01274-.60558-11.87629.606-1.82026,1.21278-3.64358,1.65064-5.51142.55-2.34638.76172-5.03695-.69576-6.95627s-5.12912-1.994-5.86577.30066" transform="translate(-238.47977 -171.03678)" fill="#2f2e41"/><path d="M410.75815,451.10681l-.63851-4.06484c.43393-5.05554-.27442-13.097,3.04343-14.20267l2.029,2.029C413.90857,435.29624,411.46749,442.85627,410.75815,451.10681Z" transform="translate(-238.47977 -171.03678)" fill="#ff6584"/><path d="M556.26874,727.99768a1.18647,1.18647,0,0,1-1.19006,1.19h-280.29a1.19,1.19,0,1,1,0-2.38h280.29A1.18651,1.18651,0,0,1,556.26874,727.99768Z" transform="translate(-238.47977 -171.03678)" fill="#ccc"/><path d="M702.99671,654.04148h-206a16,16,0,0,0,0,32h206a16,16,0,0,0,0-32Z" transform="translate(-238.47977 -171.03678)" fill="#e6e6e6"/></svg>
                                    </span>
                                </div>

                                <h5 class="my-2 d-flex justify-content-center">
                                    بحث و گفتگوی برنامه نویسان
                                </h5>
                                <span class="d-flex justify-content-center mx-20pt text-muted text-center">
                                     وارد این بخش شو و مشکلت رو مطرح کن
                                </span>
                            </div>
                            <a href="{{ route('discuss-all') }}" target="_blank" class="d-flex justify-content-center mt-3 text-70 h5">
                                کلیک کن
                                <svg class="ml-2 " width="21" height="21" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                                    <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
@section('script')
    <script src="/assets/js/scrollTo.js"></script>
    <script src="/assets/js/editor/easymde/config.js"></script>
    <script src="/assets/js/send-comment.js"></script>
    {{-- <script src="/assets/js/send-rating.js"></script>--}}
    <script src="/assets/js/manage-like.js"></script>
    <script src="/assets/js/manage-bookmark.js"></script>
    <script src="/assets/js/read-more.js"></script>


    <script>
        $(document).on('submit', '#download-video', function(e){
            e.preventDefault();
            var x = $(this);
            e.preventDefault();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN' : $('meta[name="csrf-token"]').attr('content'),
                }
            });
            var info = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                async: false,
                data: info,
                datatype: "json",
                contentType: false,
                processData: false,
                beforeSend: function (){
                    $(x).find(":submit").addClass('is-loading');
                    $(x).find(":submit").prop('disabled', true);
                },
                success: function (data){
                    $(x).find(":submit").removeClass('is-loading');
                    $(x).find(":submit").prop('disabled', false);
                    if(data.status == 0) {
                        Toast.fire({
                            icon: 'warning',
                            title: data.msg
                        })
                    }else if(data.status == 1){
                        var parent = x.closest('div')
                        x.remove()
                        var icon = "<svg class=\"ml-1 mb-1\" width=\"17\" height=\"18\" viewBox=\"0 0 17 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">"+
                            "<rect fill=\"gray\" class=\"\" x=\"9.20898\" y=\"5.95801\" width=\"5.66667\" height=\"1.41667\" rx=\"0.708333\" transform=\"rotate(90 9.20898 5.95801)\"></rect>"+
                            "<path fill=\"gray\" class=\"\" d=\"M7.79102 13.0413C7.79102 12.6501 8.10815 12.333 8.49935 12.333V12.333C8.89055 12.333 9.20768 12.6501 9.20768 13.0413V13.0413C9.20768 13.4325 8.89055 13.7497 8.49935 13.7497V13.7497C8.10815 13.7497 7.79102 13.4325 7.79102 13.0413V13.0413Z\"></path>"+
                            "<path fill=\"gray\" class=\"\" fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M0 9.5C0 16.4997 1.50025 18 8.5 18C15.4997 18 17 16.4997 17 9.5C17 2.50025 15.4997 1 8.5 1C1.50025 1 0 2.50025 0 9.5ZM1.41667 9.5C1.41667 11.2176 1.51055 12.5012 1.72671 13.4738C1.9393 14.4304 2.25086 14.9972 2.62683 15.3732C3.0028 15.7491 3.56964 16.0607 4.5262 16.2733C5.49884 16.4895 6.7824 16.5833 8.5 16.5833C10.2176 16.5833 11.5012 16.4895 12.4738 16.2733C13.4304 16.0607 13.9972 15.7491 14.3732 15.3732C14.7491 14.9972 15.0607 14.4304 15.2733 13.4738C15.4895 12.5012 15.5833 11.2176 15.5833 9.5C15.5833 7.7824 15.4895 6.49884 15.2733 5.5262C15.0607 4.56964 14.7491 4.0028 14.3732 3.62683C13.9972 3.25086 13.4304 2.9393 12.4738 2.72671C11.5012 2.51055 10.2176 2.41667 8.5 2.41667C6.7824 2.41667 5.49884 2.51055 4.5262 2.72671C3.56964 2.9393 3.0028 3.25086 2.62683 3.62683C2.25086 4.0028 1.9393 4.56964 1.72671 5.5262C1.51055 6.49884 1.41667 7.7824 1.41667 9.5Z\" fill-opacity=\"0.4\"></path>"+
                            "</svg>";
                        var element = $('<a/>',{
                            html: 'دانلود این قسمت '+icon,
                            class: 'btn btn-yellow',
                            href: data.url
                        });

                        parent.append(element);
                    }


                }
            });
        })
    </script>


    <script type="text/javascript">

        {{--  $('.pagination a').click(function(e) {
            e.preventDefault();
            if ($(this).attr('href') && $(this).attr('href') != '#') {
                var url = $(this).attr('href');
                loadMoreData(url);
            }
        });  --}}



        $(window).on('hashchange', function() {
            if (window.location.hash) {
                var page = window.location.hash.replace('#', '');
                if (page == Number.NaN || page <= 0) {
                    return false;
                }else{
                    loadMoreData(page);
                }
            }
        });

        $(document).ready(function()
        {
            $(document).on('click', '.pagination a',function(event)
            {

                event.preventDefault();

                var myurl = $(this).attr('href');
                var page=$(this).attr('href').split('page=')[1];


                var currentUrl = window.location.href;
                var url = new URL(currentUrl);
                url.searchParams.set("page", page); // setting your param
                var newUrl = url.href;

                window.history.pushState('', '', newUrl);

                loadMoreData(page);

            });
        });

        // run function when user reaches to end of the page
        function loadMoreData(page) {
            $.ajax({
                    url: '?page=' + page,
                    type: 'get',
                    datatype: 'html',
                    beforeSend: function() {
                        $('.loading').show();
                    }
                })
                .done(function(data) {
                    $('.loading').hide();
                    if (data.length == 0) {
                        // alert('end')
                        return;
                    } else {
                        $('#comments-body').empty().html(data);

                        {{--  location.hash = page;  --}}

                        $('html, body').animate({
                            scrollTop:$('#comments-list').offset().top
                        }, 'slow');
                    }

                })
                .fail(function(jqXHR, ajaxOptions, thrownError) {
                    alert('Something went wrong.');
                });
        }
    </script>
@endsection
