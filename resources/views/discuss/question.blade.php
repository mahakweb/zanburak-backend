@extends('layouts.master')
@section('title', $question->subject)
@section('head')
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
        <div class="page-section">
            <div class="container page__container">
                @if(session('success'))
                    <div class="alert border-success border-2 rounded-lg pb-0" style="background-color: rgba(65,228,0,0.22)" role="alert">
                        <div class="d-flex">
                            <div class="mr-8pt">
                                <i class="material-icons text-success">check_circle</i>
                            </div>
                            <div class="flex mt-1" style="min-width: 180px">
                                <h6 class="text-shadow">
                                    <strong> موفق - </strong> {{session('success')}}.
                                </h6>
                            </div>
                        </div>
                    </div>
                @endif
                @if($errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert border-accent border-2 rounded-lg pb-0" style="background-color: rgba(255,29,0,0.22)" role="alert">
                                <div class="d-flex">
                                    <div class="mr-8pt">
                                        <i class="material-icons text-accent">cancel</i>
                                    </div>
                                    <div class="flex mt-1" style="min-width: 180px">
                                        <h6 class="text-shadow">
                                            <strong> خطا! - </strong> {{ $error }}.
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                <div class="">
                    <div class="card border-0 shadow-none w-100">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-lg-row align-items-center mb-20pt">
                                <div class="d-flex flex-column flex-md-row align-items-center flex mb-4pt mb-lg-0 text-center text-md-left">
                                    <a href="{{ route('profile-index', $question->user->username) }}" class="mb-16pt mb-md-0 mr-md-24pt avatar avatar-xl {{ Cache::has('is_online' . $question->user->id) ? 'border-success' : 'border-light' }} border-3 rounded-circle">
                                        <img src="{{ $question->user->profile_pic }}" class="avatar-img rounded-circle shadow-lg" alt="{{ $question->user->username }}">
                                    </a>
                                    <div class="flex pt-md-4 pt-lg-4">
                                        <a href="{{ route('profile-index', $question->user->username) }}">
                                            <h5 class="mb-4pt">
                                                {{ $question->user->first_name.' '.$question->user->last_name }}
                                            </h5>
                                        </a>
                                        <div class="row d-flex ">
                                            <span class="col-sm-auto mb-2 px-2">
                                                <p class="text-muted"><span class="text-primary">{{ jdate($question->created_at)->ago() }}</span> توسط <a href="{{ route('profile-index', $question->user->username) }}" class="text-primary">{{ $question->user->first_name.' '.$question->user->last_name }}</a> مطرح شد</p>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-lg-16pt">
                                    <div class="d-flex text-center">
                                        @if( $question->best_answer )
                                        <button class="btn btn-sm btn-yellow p-1 rounded-lg" data-toggle="tooltip" data-title="دارای بهترین پاسخ">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 7.00018L10 17.0002L5 12.0002" stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-accent rounded-lg p-1 mx-1" data-toggle="modal" data-target="#modal-send-report" data-model="{{ get_class($question) }}" data-id="{{ $question->id }}">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="currentColor" d="M12 14.75C11.59 14.75 11.25 14.41 11.25 14V9C11.25 8.59 11.59 8.25 12 8.25C12.41 8.25 12.75 8.59 12.75 9V14C12.75 14.41 12.41 14.75 12 14.75Z"/>
                                                <path fill="currentColor" d="M12 18C11.94 18 11.87 17.99 11.8 17.98C11.74 17.97 11.68 17.95 11.62 17.92C11.56 17.9 11.5 17.87 11.44 17.83C11.39 17.79 11.34 17.75 11.29 17.71C11.11 17.52 11 17.26 11 17C11 16.74 11.11 16.48 11.29 16.29C11.34 16.25 11.39 16.21 11.44 16.17C11.5 16.13 11.56 16.1 11.62 16.08C11.68 16.05 11.74 16.03 11.8 16.02C11.93 15.99 12.07 15.99 12.19 16.02C12.26 16.03 12.32 16.05 12.38 16.08C12.44 16.1 12.5 16.13 12.56 16.17C12.61 16.21 12.66 16.25 12.71 16.29C12.89 16.48 13 16.74 13 17C13 17.26 12.89 17.52 12.71 17.71C12.66 17.75 12.61 17.79 12.56 17.83C12.5 17.87 12.44 17.9 12.38 17.92C12.32 17.95 12.26 17.97 12.19 17.98C12.13 17.99 12.06 18 12 18Z" />
                                                <path fill="currentColor" d="M18.06 22.16H5.93998C3.98998 22.16 2.49998 21.45 1.73998 20.17C0.989976 18.89 1.08998 17.24 2.03998 15.53L8.09998 4.63C9.09998 2.83 10.48 1.84 12 1.84C13.52 1.84 14.9 2.83 15.9 4.63L21.96 15.54C22.91 17.25 23.02 18.89 22.26 20.18C21.5 21.45 20.01 22.16 18.06 22.16ZM12 3.34C11.06 3.34 10.14 4.06 9.40998 5.36L3.35998 16.27C2.67998 17.49 2.56998 18.61 3.03998 19.42C3.50998 20.23 4.54998 20.67 5.94998 20.67H18.07C19.47 20.67 20.5 20.23 20.98 19.42C21.46 18.61 21.34 17.5 20.66 16.27L14.59 5.36C13.86 4.06 12.94 3.34 12 3.34Z"/>
                                            </svg>
                                        </button>
                                        @auth
                                        <div class="mx-1 p-1 btn btn-sm btn-light rounded-lg">
                                            <form id="send-bookmark" action="{{ route('send-bookmark') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="bookmarkable_id" value="{{ $question->id }}">
                                                <input type="hidden" name="bookmarkable_type" value="{{ get_class($question) }}">
                                                <button type="submit" class="btn p-0">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="bookmark">
                                                            <path id="fillOrEmpty" stroke-width="2" stroke="@auth {{ auth()->user()->hasBookmarked($question) ? 'none' : 'currentColor' }} @else currentColor @endauth" fill="@auth {{ auth()->user()->hasBookmarked($question) ? 'currentColor' : 'none' }} @else none @endauth" fill-rule="evenodd" clip-rule="evenodd" d="M8 1.25C5.37665 1.25 3.25 3.37665 3.25 6V20.4648C3.25 21.7269 4.27311 22.75 5.53518 22.75C5.98634 22.75 6.42739 22.6165 6.80278 22.3662L11.3066 19.3636C11.7265 19.0837 12.2735 19.0837 12.6934 19.3636L17.1972 22.3662C17.5726 22.6165 18.0137 22.75 18.4648 22.75C19.7269 22.75 20.75 21.7269 20.75 20.4648V6C20.75 3.37665 18.6234 1.25 16 1.25H8ZM9 6.75C8.58579 6.75 8.25 7.08579 8.25 7.5C8.25 7.91421 8.58579 8.25 9 8.25H15C15.4142 8.25 15.75 7.91421 15.75 7.5C15.75 7.08579 15.4142 6.75 15 6.75H9Z"/>
                                                        </g>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                        @endauth
                                        <button link="{{ route('discuss-question', $question->slug) }}" class="copy-link btn btn-sm btn-light rounded-lg p-1">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M11.1667 0.25C8.43733 0.25 6.25 2.50265 6.25 5.25H12.8333C15.5627 5.25 17.75 7.50265 17.75 10.25V18.75H17.8333C20.5627 18.75 22.75 16.4974 22.75 13.75V5.25C22.75 2.50265 20.5627 0.25 17.8333 0.25H11.1667Z">
                                                </path>
                                                <path fill="currentColor" opacity="0.5" d="M2 10.25C2 7.90279 3.86548 6 6.16667 6H12.8333C15.1345 6 17 7.90279 17 10.25V18.75C17 21.0972 15.1345 23 12.8333 23H6.16667C3.86548 23 2 21.0972 2 18.75V10.25Z">
                                                </path>
                                            </svg>
                                        </button>
                                        <a href="#answers-list" class="btn btn-sm btn-light p-1 ml-1 rounded-lg">
                                            <svg class="mr-2" fill="" width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10,19 C10,19.8897227 8.9391917,20.319213 8.3190139,19.7330526 L8.2407434,19.6507914 L2.2407434,12.6507914 C1.94650147,12.3075091 1.92198131,11.814562 2.16718292,11.4463356 L2.2407434,11.3492086 L8.2407434,4.34920863 C8.81976724,3.6736808 9.90470154,4.03795234 9.99410748,4.88660488 L10,5 L10,8 L11.0379967,8.08649972 C17.1341361,8.59451134 21.8458884,13.616576 21.9962945,19.6999759 L22,20 L19.3412157,18.4806947 C16.6386172,16.9363527 13.5975935,16.0874335 10.491017,16.0064025 L10,16 L10,19 Z"/>
                                            </svg>
                                            <span>{{ $question->answers->count() }}</span>
                                            <span class="ml-2">پاسخ</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="px-2">
                                <h1 class="font-size-24pt ">{{ $question->subject }}</h1>

                                <div class="content-area text-70 font-size-16pt my-8pt p-3 bg-light text-justify lh-28pt rounded-lg">
                                    {!!  Illuminate\Mail\Markdown::parse($question->question) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row mt-16pt mb-2 mb-lg-0">
                                <div class="col-sm-12 col-md-9 col-lg-9">
                                    <div class="d-flex align-items-center justify-content-center justify-content-sm-start" style="overflow-x: auto!important;">
                                        @if($question->tags->count())
                                            @foreach($question->tags as $tag)
                                                <a href="#" class="mx-1 badge-lg badge-light rounded">#{{ $tag }}</a>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-3 col-lg-3 mt-2 mt-lg-0 ">
                                    @auth
                                        <div class="d-flex align-items-center justify-content-center justify-content-sm-end pb-2">
                                            <a class="btn btn-sm btn-light rounded-lg" href="#send-answer">
                                                <svg class="mr-2" fill="currentColor" width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10,19 C10,19.8897227 8.9391917,20.319213 8.3190139,19.7330526 L8.2407434,19.6507914 L2.2407434,12.6507914 C1.94650147,12.3075091 1.92198131,11.814562 2.16718292,11.4463356 L2.2407434,11.3492086 L8.2407434,4.34920863 C8.81976724,3.6736808 9.90470154,4.03795234 9.99410748,4.88660488 L10,5 L10,8 L11.0379967,8.08649972 C17.1341361,8.59451134 21.8458884,13.616576 21.9962945,19.6999759 L22,20 L19.3412157,18.4806947 C16.6386172,16.9363527 13.5975935,16.0874335 10.491017,16.0064025 L10,16 L10,19 Z"/>
                                                </svg>
                                                 ارسال پاسخ
                                            </a>
                                        </div>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div id="answers-list" class="row pt-2">
                    <div class="col-lg-8">
                        @if(! count($question->answers))

                            <div class="alert alert-soft-warning border-2 border-yellow rounded-lg mb-16pt">
                                <div class="d-flex py-8pt">
                                    <div class="my-auto">
                                        <svg width="50" height="50" viewBox="0 0 82 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M37.0183 1.30894L28.9189 8.36141H40.5344V2.79688e-05C39.2808 -0.0040244 38.026 0.43228 37.0183 1.30894Z" fill="#FDF0BC"></path>
                                            <path d="M44.0058 1.30081C43.0089 0.437656 41.773 0.00405237 40.5356 0V8.36138H52.1511L44.0058 1.30081Z" fill="url(#paint0_linear_3240_36296)"></path>
                                            <path d="M12.2798 12.8717L10.6089 23.4807L19.5065 16.0149L14.1317 9.6095C13.1686 10.4119 12.4878 11.5533 12.2798 12.8717Z" fill="#FDF0BC"></path>
                                            <path d="M17.6278 8.37493C16.3094 8.35332 15.0829 8.81664 14.1333 9.60955L19.5081 16.015L28.4057 8.54918L17.6278 8.37493Z" fill="url(#paint1_linear_3240_36296)"></path>
                                            <path d="M0.763061 37.6316L6.3013 46.8332L8.31802 35.3947L0.084965 33.9413C-0.137915 35.1759 0.0741587 36.4875 0.763061 37.6316Z" fill="#FDF0BC"></path>
                                            <path d="M1.96796 30.7494C0.945413 31.5815 0.303789 32.7243 0.0849609 33.9413L8.31937 35.3934L10.3361 23.955L1.96796 30.7494Z" fill="url(#paint2_linear_3240_36296)"></path>
                                            <path d="M7.85456 64.0018L18.0125 67.4896L12.2054 57.4302L4.96387 61.6109C5.58658 62.6997 6.59157 63.5682 7.85456 64.0018Z" fill="url(#paint3_linear_3240_36296)"></path>
                                            <path d="M4.35488 57.9556C4.10499 59.2497 4.34813 60.5383 4.96274 61.6122L12.2043 57.4315L6.39728 47.3722L4.35488 57.9556Z" fill="url(#paint4_linear_3240_36296)"></path>
                                            <path d="M30.2375 79.6439L40.2603 75.7874L29.3459 71.8147L26.4863 79.6723C27.6629 80.1045 28.992 80.1234 30.2375 79.6439Z" fill="url(#paint5_linear_3240_36296)"></path>
                                            <path d="M23.6695 77.2611C24.3098 78.4133 25.3242 79.2441 26.4859 79.6709L29.3455 71.8134L18.4312 67.8407L23.6695 77.2611Z" fill="url(#paint6_linear_3240_36296)"></path>
                                            <path d="M57.4393 77.2395L62.6385 67.842L51.7241 71.8147L54.5837 79.6723C55.7616 79.2468 56.7923 78.4079 57.4393 77.2395Z" fill="url(#paint7_linear_3240_36296)"></path>
                                            <path d="M50.876 79.6358C52.1079 80.1072 53.4182 80.091 54.5826 79.6709L51.723 71.8134L40.8086 75.786L50.876 79.6358Z" fill="url(#paint8_linear_3240_36296)"></path>
                                            <path d="M76.73 57.9124L74.6728 47.3722L68.8657 57.4315L76.1073 61.6122C76.7368 60.5275 76.9867 59.2227 76.73 57.9124Z" fill="url(#paint9_linear_3240_36296)"></path>
                                            <path d="M73.2439 63.9667C74.4893 63.5358 75.4835 62.6808 76.1062 61.6109L68.8647 57.4302L63.0576 67.4896L73.2439 63.9667Z" fill="url(#paint10_linear_3240_36296)"></path>
                                            <path d="M79.0843 30.7062L70.7324 23.955L72.7491 35.3934L80.9836 33.9413C80.7715 32.7067 80.1231 31.5464 79.0843 30.7062Z" fill="url(#paint11_linear_3240_36296)"></path>
                                            <path d="M80.3058 37.5857C80.9839 36.4551 81.196 35.161 80.9839 33.9413L72.7495 35.3934L74.7662 46.8319L80.3058 37.5857Z" fill="url(#paint12_linear_3240_36296)"></path>
                                            <path d="M63.402 8.35197L52.6646 8.54783L61.5622 16.0136L66.937 9.6082C65.9793 8.80043 64.7379 8.32765 63.402 8.35197Z" fill="url(#paint13_linear_3240_36296)"></path>
                                            <path d="M68.759 12.8379C68.551 11.5358 67.8824 10.4092 66.9368 9.6109L61.562 16.0163L70.4597 23.4822L68.759 12.8379Z" fill="url(#paint14_linear_3240_36296)"></path>
                                            <path d="M40.534 69.4414C56.2005 69.4414 68.9006 56.7412 68.9006 41.0748C68.9006 25.4084 56.2005 12.7083 40.534 12.7083C24.8676 12.7083 12.1675 25.4084 12.1675 41.0748C12.1675 56.7412 24.8676 69.4414 40.534 69.4414Z" fill="url(#paint15_radial_3240_36296)"></path>
                                            <path d="M40.5339 10.1742C23.4681 10.1742 9.6333 24.0076 9.6333 41.0735C9.6333 58.1394 23.4681 71.9741 40.5339 71.9741C57.5998 71.9741 71.4346 58.1394 71.4346 41.0735C71.4346 24.0076 57.5998 10.1742 40.5339 10.1742ZM40.5339 69.4374C24.8688 69.4374 12.1701 56.7386 12.1701 41.0735C12.1701 25.4084 24.8688 12.7096 40.5339 12.7096C56.199 12.7096 68.8978 25.4084 68.8978 41.0735C68.8978 56.7386 56.199 69.4374 40.5339 69.4374Z" fill="url(#paint16_linear_3240_36296)"></path>
                                            <path d="M40.5341 6.2312C21.2908 6.2312 5.69189 21.8301 5.69189 41.0735C5.69189 60.3168 21.2908 75.9157 40.5341 75.9157C59.7775 75.9157 75.3764 60.3168 75.3764 41.0735C75.3764 21.8301 59.7775 6.2312 40.5341 6.2312ZM40.5341 71.9592C23.4764 71.9592 9.64836 58.1312 9.64836 41.0735C9.64836 24.0157 23.4764 10.189 40.5341 10.189C57.5919 10.189 71.4199 24.017 71.4199 41.0748C71.4199 58.1326 57.5919 71.9592 40.5341 71.9592Z" fill="url(#paint17_linear_3240_36296)"></path>
                                            <path d="M32.7133 55.2892C32.2405 55.2892 31.8015 55.1379 31.4138 54.8677C31.3638 54.834 31.3301 54.8002 31.2963 54.7664C31.2625 54.7502 31.245 54.7165 31.2288 54.6989C31.1612 54.6476 31.1275 54.5976 31.0775 54.5476C31.0437 54.5138 31.0261 54.4801 30.9937 54.4463L30.9775 54.4301C30.9437 54.3963 30.9262 54.3625 30.91 54.3288L30.8762 54.2774C30.8087 54.1761 30.7587 54.0748 30.7074 53.9735C30.6912 53.9235 30.6736 53.8722 30.656 53.8222C30.6398 53.7709 30.6223 53.7047 30.6061 53.6534C30.5885 53.5858 30.5723 53.5021 30.5547 53.417C30.5547 53.3832 30.5385 53.3332 30.5385 53.2995C30.5385 53.2319 30.5223 53.1644 30.5223 53.0969C30.5223 52.9793 30.5385 52.8605 30.5561 52.7254L31.8555 45.1664L26.3713 39.8172C26.27 39.7159 26.1687 39.5984 26.085 39.4796C25.9837 39.3283 25.9161 39.2094 25.8661 39.0743C25.8499 39.0243 25.8162 38.9568 25.7986 38.8717C25.7473 38.6691 25.7148 38.4665 25.7148 38.2476C25.7148 38.1801 25.7148 38.1301 25.7148 38.0626C25.7148 37.9613 25.7311 37.86 25.7648 37.7586C25.7648 37.7424 25.781 37.7087 25.781 37.6911C25.7986 37.6398 25.7986 37.5898 25.8148 37.5398C25.8148 37.5236 25.831 37.4885 25.8486 37.4385C25.8648 37.3872 25.8823 37.3372 25.9161 37.2872C25.9661 37.1859 26.0174 37.0846 26.1012 36.9671C26.1349 36.8995 26.1849 36.8496 26.2362 36.7982C26.2362 36.7982 26.2362 36.782 26.2525 36.782L26.5226 36.343H26.759C26.7928 36.3255 26.809 36.3093 26.8428 36.2917C26.8765 36.2755 26.9441 36.2417 26.994 36.2079C27.0616 36.1742 27.1453 36.1404 27.2466 36.1066C27.2804 36.0904 27.3304 36.0729 27.3817 36.0729C27.4493 36.0567 27.5168 36.0391 27.6005 36.0215L35.1933 34.9247L38.5338 28.0911C38.9053 27.3319 39.6644 26.8591 40.5073 26.8591C41.1152 26.8591 41.7055 27.1117 42.1269 27.5507C42.1945 27.6345 42.262 27.702 42.312 27.7871C42.3795 27.8884 42.4471 27.9897 42.497 28.0911L45.8889 34.9409L53.4641 36.0377C54.3083 36.1553 55 36.7469 55.2526 37.556C55.2701 37.6074 55.2863 37.6398 55.2863 37.6911C55.2863 37.7073 55.2863 37.7073 55.3025 37.7249C55.3187 37.7762 55.3363 37.8262 55.3363 37.8937C55.3363 37.9099 55.3363 37.9099 55.3363 37.9275C55.3525 37.995 55.3525 38.045 55.3525 38.1126C55.3701 38.4327 55.3187 38.7704 55.1837 39.0743C55.1323 39.2094 55.0661 39.3269 54.981 39.4458L54.9635 39.462C54.8797 39.5795 54.7784 39.6984 54.6771 39.7997L49.1929 45.1488L50.4924 52.6903C50.6274 53.517 50.3073 54.3436 49.6144 54.8502C49.4455 54.9677 49.2429 55.0866 49.0403 55.1541C49.0227 55.1541 49.0065 55.1717 48.9727 55.1717C48.7539 55.2392 48.5337 55.273 48.3149 55.273C47.961 55.273 47.6057 55.1892 47.2856 55.0204L40.5033 51.4597L33.721 55.0204C33.4211 55.2054 33.0672 55.2892 32.7133 55.2892Z" fill="#DF771E"></path>
                                            <path d="M40.5071 27.9384V41.8921L35.8496 35.9364L39.494 28.5638C39.7142 28.1586 40.1195 27.9384 40.5071 27.9384Z" fill="#FFFAF6"></path>
                                            <path d="M54.2245 37.9112L40.5234 41.8934L45.1796 35.9377L53.3127 37.1183C53.7693 37.2021 54.0894 37.5222 54.2245 37.9112Z" fill="#FEE0AC"></path>
                                            <path d="M48.9782 53.9898L40.5088 41.8921L48.034 44.7774L49.4172 52.8767C49.5172 53.3495 49.3146 53.7534 48.9782 53.9898Z" fill="#ECB76B"></path>
                                            <path d="M40.5074 41.8921V50.244L33.2348 54.0735C32.8133 54.2923 32.3743 54.2248 32.0366 53.9898L40.5074 41.8921Z" fill="#F1A341"></path>
                                            <path d="M45.1648 35.9364L40.5073 41.8921V27.9384C40.8113 27.9384 41.1152 28.0559 41.334 28.2923C41.3678 28.3261 41.4015 28.3761 41.4353 28.4274C41.4691 28.4787 41.5029 28.5287 41.5191 28.5787L45.1648 35.9364Z" fill="#FFE9BA"></path>
                                            <path d="M54.1908 38.6704C54.1571 38.738 54.1233 38.8055 54.0895 38.873C54.0382 38.9406 53.9882 38.9906 53.9382 39.0581L48.0501 44.7949L40.5249 41.9096L54.226 37.9275C54.226 37.9437 54.2422 37.9613 54.2422 37.9788C54.2422 37.9964 54.2597 38.0126 54.2597 38.0464C54.2597 38.0639 54.2597 38.0639 54.2597 38.0639C54.2597 38.0801 54.2597 38.0977 54.2597 38.1139C54.2597 38.1477 54.2597 38.1652 54.2597 38.1976C54.2759 38.3489 54.2584 38.5178 54.1908 38.6704Z" fill="#E49632"></path>
                                            <path d="M48.9781 53.9898C48.8944 54.0573 48.7931 54.1073 48.6742 54.141C48.658 54.141 48.658 54.141 48.6404 54.1573C48.3703 54.241 48.0663 54.2248 47.78 54.0735L40.5073 50.244V41.8921L48.9781 53.9898Z" fill="#E29136"></path>
                                            <path d="M40.5072 41.8921L32.0378 53.9898C32.0215 53.9722 32.004 53.956 31.9878 53.9384C31.9716 53.9222 31.954 53.9047 31.9364 53.8871C31.9202 53.8709 31.9027 53.8533 31.8689 53.8196C31.8513 53.8033 31.8351 53.7858 31.8176 53.7682C31.8 53.7507 31.8014 53.752 31.8014 53.7345C31.7852 53.7182 31.7676 53.7007 31.7676 53.6831C31.7338 53.6331 31.7001 53.5818 31.6838 53.5318C31.6676 53.5156 31.6676 53.4805 31.6501 53.4481C31.6339 53.4143 31.6339 53.3968 31.6163 53.3643C31.6001 53.3306 31.6001 53.2806 31.6001 53.2468C31.6001 53.2306 31.6001 53.1955 31.6001 53.1793C31.6001 53.1455 31.6001 53.1117 31.6001 53.078C31.6001 53.0104 31.6001 52.9429 31.6163 52.8754L32.9995 44.776L40.5072 41.8921Z" fill="#FED891"></path>
                                            <path d="M40.5072 41.8921L26.8062 37.9099C26.8062 37.8937 26.8224 37.8762 26.8224 37.8586C26.8386 37.8248 26.8386 37.7911 26.8561 37.7748C26.8899 37.7073 26.9237 37.6573 26.9574 37.606C26.9737 37.5722 26.9912 37.5547 27.025 37.5223C27.0425 37.506 27.0588 37.4709 27.0925 37.4547C27.1087 37.4385 27.1439 37.4047 27.1601 37.3872C27.1763 37.3696 27.21 37.3534 27.2276 37.3358C27.2614 37.3196 27.2951 37.2845 27.3289 37.2683C27.3451 37.2521 27.3789 37.2345 27.4127 37.2345C27.464 37.2183 27.4964 37.2008 27.5477 37.1832C27.5639 37.1832 27.5815 37.167 27.6153 37.167C27.649 37.1508 27.699 37.1508 27.7504 37.1332L35.8835 35.9526L40.5072 41.8921Z" fill="#FFF3D9"></path>
                                            <path d="M40.5074 41.8921L32.9997 44.7936L27.1116 39.0568C27.0441 39.0054 27.0103 38.9392 26.9603 38.8717C26.9266 38.8042 26.8766 38.7366 26.859 38.6691C26.8428 38.6353 26.8253 38.6015 26.8253 38.5678C26.7915 38.4665 26.7739 38.349 26.7739 38.2476C26.7739 38.2139 26.7739 38.1801 26.7739 38.1463C26.7739 38.0964 26.7739 38.0626 26.7915 38.0113C26.7915 37.995 26.8077 37.9775 26.8077 37.9437C26.8077 37.9275 26.8239 37.9099 26.8239 37.8937L40.5074 41.8921Z" fill="#ECB76B"></path>
                                            <defs>
                                                <linearGradient id="paint0_linear_3240_36296" x1="41.1036" y1="4.18069" x2="51.7699" y2="4.18069" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FEE998"></stop>
                                                    <stop offset="1" stop-color="#FCC15B"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint1_linear_3240_36296" x1="17.3811" y1="12.3392" x2="28.0262" y2="3.40617" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FEE998"></stop>
                                                    <stop offset="1" stop-color="#FCC15B"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint2_linear_3240_36296" x1="10.5266" y1="23.931" x2="4.51958" y2="33.7997" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FCC15B"></stop>
                                                    <stop offset="1" stop-color="#FEE998"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint3_linear_3240_36296" x1="7.70257" y1="59.2865" x2="18.1434" y2="66.1517" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FFC05F"></stop>
                                                    <stop offset="1" stop-color="#ED892B"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint4_linear_3240_36296" x1="6.51635" y1="47.5911" x2="8.37567" y2="59.3192" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FCC15B"></stop>
                                                    <stop offset="1" stop-color="#FEE998"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint5_linear_3240_36296" x1="28.4389" y1="75.8835" x2="38.7366" y2="78.1719" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FEAC3E"></stop>
                                                    <stop offset="1" stop-color="#F08223"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint6_linear_3240_36296" x1="17.9161" y1="67.7511" x2="27.3557" y2="75.4744" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FFA135"></stop>
                                                    <stop offset="1" stop-color="#FEB431"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint7_linear_3240_36296" x1="62.6794" y1="67.7748" x2="53.2637" y2="75.8796" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FFAA3D"></stop>
                                                    <stop offset="1" stop-color="#ED8B1C"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint8_linear_3240_36296" x1="40.8499" y1="76.5382" x2="52.864" y2="75.8708" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FF962D"></stop>
                                                    <stop offset="1" stop-color="#E56005"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint9_linear_3240_36296" x1="75.1143" y1="47.4145" x2="72.6987" y2="59.1743" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FDBD41"></stop>
                                                    <stop offset="1" stop-color="#FEB334"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint10_linear_3240_36296" x1="78.2796" y1="49.4848" x2="66.6643" y2="69.6026" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#F6942F"></stop>
                                                    <stop offset="1" stop-color="#EA6F11"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint11_linear_3240_36296" x1="70.1494" y1="24.2932" x2="77.1417" y2="34.6546" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FDBD41"></stop>
                                                    <stop offset="1" stop-color="#FEB334"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint12_linear_3240_36296" x1="74.3309" y1="46.8026" x2="77.0643" y2="34.725" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FD9D24"></stop>
                                                    <stop offset="1" stop-color="#FF9515"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint13_linear_3240_36296" x1="64.1149" y1="12.6534" x2="53.3085" y2="7.441" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FCC15B"></stop>
                                                    <stop offset="1" stop-color="#FEE998"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint14_linear_3240_36296" x1="64.1667" y1="11.9916" x2="69.3156" y2="23.4335" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FF9515"></stop>
                                                    <stop offset="1" stop-color="#FFBE41"></stop>
                                                </linearGradient>
                                                <radialGradient id="paint15_radial_3240_36296" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(40.5342 41.0743) scale(28.3666)">
                                                    <stop offset="0.8428" stop-color="#FAAA31"></stop>
                                                    <stop offset="1" stop-color="#F57E16"></stop>
                                                </radialGradient>
                                                <linearGradient id="paint16_linear_3240_36296" x1="40.5342" y1="10.1737" x2="40.5342" y2="71.9749" gradientUnits="userSpaceOnUse">
                                                    <stop offset="0.0226" stop-color="#FD9A18"></stop>
                                                    <stop offset="0.3764" stop-color="#FEC752"></stop>
                                                    <stop offset="1" stop-color="#FFA422"></stop>
                                                </linearGradient>
                                                <linearGradient id="paint17_linear_3240_36296" x1="40.5344" y1="6.12908" x2="40.5344" y2="75.8139" gradientUnits="userSpaceOnUse">
                                                    <stop stop-color="#FFDB69"></stop>
                                                    <stop offset="0.1799" stop-color="#FEE7B0"></stop>
                                                    <stop offset="0.24" stop-color="#FEE9BA"></stop>
                                                    <stop offset="0.2592" stop-color="#FDECD0"></stop>
                                                    <stop offset="0.2953" stop-color="#FEDA92"></stop>
                                                    <stop offset="0.3254" stop-color="#FFCD64"></stop>
                                                    <stop offset="0.3405" stop-color="#FFC853"></stop>
                                                    <stop offset="0.7613" stop-color="#FF9114"></stop>
                                                    <stop offset="1" stop-color="#F57E16"></stop>
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                    </div>
                                    <div class="ml-16pt d-flex flex-column">
                                        <div class="my-auto">
                                            <div class="mb-8pt font-size-16pt text-orange font-heavy">
                                                ثبت اولین پاسخ و دریافت 100 امتیاز
                                            </div>
                                            <div class="font-size-14pt text-70">
                                                اولین نفری باش که به {{ $question->user->first_name.' '.$question->user->last_name }} کمک میکنه تا مشکلش حل بشه و 100 امتیاز بگیر.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{--  <div class="text-center p-3">
                                <p class="font-size-20pt font-bold text-50">تاکنون پاسخی برای این پرسش ثبت نشده است!</p>
                                <svg xmlns="http://www.w3.org/2000/svg" class="my-3" width="200" height="200" viewBox="0 0 797.5 834.5" xmlns:xlink="http://www.w3.org/1999/xlink"><title>void</title><ellipse cx="308.5" cy="780" rx="308.5" ry="54.5" fill="#3f3d56"/><circle cx="496" cy="301.5" r="301.5" fill="#3f3d56"/><circle cx="496" cy="301.5" r="248.89787" opacity="0.05"/><circle cx="496" cy="301.5" r="203.99362" opacity="0.05"/><circle cx="496" cy="301.5" r="146.25957" opacity="0.05"/><path d="M398.42029,361.23224s-23.70394,66.72221-13.16886,90.42615,27.21564,46.52995,27.21564,46.52995S406.3216,365.62186,398.42029,361.23224Z" transform="translate(-201.25 -32.75)" fill="#d0cde1"/><path d="M398.42029,361.23224s-23.70394,66.72221-13.16886,90.42615,27.21564,46.52995,27.21564,46.52995S406.3216,365.62186,398.42029,361.23224Z" transform="translate(-201.25 -32.75)" opacity="0.1"/><path d="M415.10084,515.74682s-1.75585,16.68055-2.63377,17.55847.87792,2.63377,0,5.26754-1.75585,6.14547,0,7.02339-9.65716,78.13521-9.65716,78.13521-28.09356,36.8728-16.68055,94.81576l3.51169,58.82089s27.21564,1.75585,27.21564-7.90132c0,0-1.75585-11.413-1.75585-16.68055s4.38962-5.26754,1.75585-7.90131-2.63377-4.38962-2.63377-4.38962,4.38961-3.51169,3.51169-4.38962,7.90131-63.2105,7.90131-63.2105,9.65716-9.65716,9.65716-14.92471v-5.26754s4.38962-11.413,4.38962-12.29093,23.70394-54.43127,23.70394-54.43127l9.65716,38.62864,10.53509,55.3092s5.26754,50.04165,15.80262,69.356c0,0,18.4364,63.21051,18.4364,61.45466s30.72733-6.14547,29.84941-14.04678-18.4364-118.5197-18.4364-118.5197L533.62054,513.991Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><path d="M391.3969,772.97846s-23.70394,46.53-7.90131,48.2858,21.94809,1.75585,28.97148-5.26754c3.83968-3.83968,11.61528-8.99134,17.87566-12.87285a23.117,23.117,0,0,0,10.96893-21.98175c-.463-4.29531-2.06792-7.83444-6.01858-8.16366-10.53508-.87792-22.826-10.53508-22.826-10.53508Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><path d="M522.20753,807.21748s-23.70394,46.53-7.90131,48.28581,21.94809,1.75584,28.97148-5.26754c3.83968-3.83969,11.61528-8.99134,17.87566-12.87285a23.117,23.117,0,0,0,10.96893-21.98175c-.463-4.29531-2.06792-7.83444-6.01857-8.16367-10.53509-.87792-22.826-10.53508-22.826-10.53508Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><circle cx="295.90488" cy="215.43252" r="36.90462" fill="#ffb8b8"/><path d="M473.43048,260.30832S447.07,308.81154,444.9612,308.81154,492.41,324.62781,492.41,324.62781s13.70743-46.39439,15.81626-50.61206Z" transform="translate(-201.25 -32.75)" fill="#ffb8b8"/><path d="M513.86726,313.3854s-52.67543-28.97148-57.943-28.09356-61.45466,50.04166-60.57673,70.2339,7.90131,53.55335,7.90131,53.55335,2.63377,93.05991,7.90131,93.93783-.87792,16.68055.87793,16.68055,122.90931,0,123.78724-2.63377S513.86726,313.3854,513.86726,313.3854Z" transform="translate(-201.25 -32.75)" fill="#d0cde1"/><path d="M543.2777,521.89228s16.68055,50.91958,2.63377,49.16373-20.19224-43.89619-20.19224-43.89619Z" transform="translate(-201.25 -32.75)" fill="#ffb8b8"/><path d="M498.50359,310.31267s-32.48318,7.02339-27.21563,50.91957,14.9247,87.79237,14.9247,87.79237l32.48318,71.11182,3.51169,13.16886,23.70394-6.14547L528.353,425.32067s-6.14547-108.86253-14.04678-112.37423A33.99966,33.99966,0,0,0,498.50359,310.31267Z" transform="translate(-201.25 -32.75)" fill="#d0cde1"/><polygon points="277.5 414.958 317.885 486.947 283.86 411.09 277.5 414.958" opacity="0.1"/><path d="M533.896,237.31585l.122-2.82012,5.6101,1.39632a6.26971,6.26971,0,0,0-2.5138-4.61513l5.97581-.33413a64.47667,64.47667,0,0,0-43.1245-26.65136c-12.92583-1.87346-27.31837.83756-36.182,10.43045-4.29926,4.653-7.00067,10.57018-8.92232,16.60685-3.53926,11.11821-4.26038,24.3719,3.11964,33.40938,7.5006,9.18513,20.602,10.98439,32.40592,12.12114,4.15328.4,8.50581.77216,12.35457-.83928a29.721,29.721,0,0,0-1.6539-13.03688,8.68665,8.68665,0,0,1-.87879-4.15246c.5247-3.51164,5.20884-4.39635,8.72762-3.9219s7.74984,1.20031,10.062-1.49432c1.59261-1.85609,1.49867-4.559,1.70967-6.99575C521.28248,239.785,533.83587,238.70653,533.896,237.31585Z" transform="translate(-201.25 -32.75)" fill="#2f2e41"/><circle cx="559" cy="744.5" r="43" fill="#fed700"/><circle cx="54" cy="729.5" r="43" fill="#fed700"/><circle cx="54" cy="672.5" r="31" fill="#fed700"/><circle cx="54" cy="624.5" r="22" fill="#fed700"/></svg>
                            </div>  --}}
                        @else
                            @foreach($answers = $question->answers()->paginate(10) as $answer)
                                <div id="{{ $answer->id }}" class="card card-body {{ $answer->isBest() ? ' alert-yellow border-2 border-yellow' : 'border-0 shadow-none' }}">
                                    @include('discuss.component.answer-card', ['answer' => $answer, 'question' => $question])
                                </div>
                            @endforeach
                            <div class="mb-32pt d-flex flex-row">
                                {{ $answers->appends($_GET)->links('vendor.pagination.custom') }}
                            </div>
                        @endif

                        <div id="send-answer" class="pt-16pt">
                            @auth

                                <div class="card card-body rounded-lg border-0 shadow-none">
                                    <div class="mb-24pt font-size-16pt font-bold">
                                        <span class="mr-1">
                                            <i class="fa fa-circle text-primary font-size-8pt"></i>
                                        </span>
                                        ارسال پاسخ
                                    </div>
                                    <div class="">
                                        <div class="d-flex mb-12pt border-bottom">
                                            <div class="pb-8pt">
                                                <a href="{{ route('profile-index', auth()->user()->username) }}" class="avatar avatar-lg border-yellow border-2 rounded-circle mr-12pt">
                                                    <img src="{{ auth()->user()->profile_pic }}" alt="{{ auth()->user()->username.'@' }}" class="avatar-img rounded-circle shadow">
                                                </a>
                                            </div>
                                            <div class="d-flex pb-8pt border-bottom-2 border-bottom-primary">
                                                <div class="my-auto">
                                                    <a href="{{ route('profile-index', auth()->user()->username) }}" class="text-body font-size-16pt">
                                                        <strong>
                                                            {{ auth()->user()->first_name.' '.auth()->user()->last_name }}
                                                        </strong>
                                                    </a>
                                                    <div class="text-muted font-size-12pt">{{ auth()->user()->username.'@' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex pt-2">
                                            <form class="send-answer" action="{{ route('discuss-store-answer') }}" method="post">
                                                @csrf
                                                <div class="form-group mb-32pt">
                                                    <textarea id="editor" name="answer" class="form-control" placeholder="کامنت یا سوال خود را اینجا وارد کنید..."></textarea>

                                                    <span class="invalid-feedback error-text answer_error" role="alert">
                                                        <strong></strong>
                                                    </span>
                                                </div>

                                                <div class="form-group d-flex">
                                                    <label class="my-auto mr-2 font-size-14pt font-bold cursor-pointer" for="preview-btn">پیش نمایش متن</label>
                                                    <div class="custom-control custom-checkbox-toggle custom-control-inline my-auto">
                                                        <input type="checkbox" id="preview-btn" class="custom-control-input">
                                                        <label class="custom-control-label cursor-pointer" for="preview-btn"></label>
                                                    </div>
                                                </div>
                                                <hr/>
                                                <input type="hidden" name="parent_id" value="0">
                                                <input type="hidden" name="question_id" value="{{ $question->id }}">
                                                <div class="d-flex">
                                                    <h1 class="mr-auto"></h1>
                                                    <button class="btn btn-yellow rounded-lg" type="submit"><span>ثبت پاسخ</span></button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>

                            @else
                                <div class="alert alert-primary rounded-lg border-0 p-3 font-size-16pt">
                                    <div class="d-flex flex-column flex-lg-row">
                                        <div class="d-flex">
                                            <svg class="mr-2 my-auto" width="21" height="18" viewBox="0 0 21 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="currentColor" d="M0 17.4167C0 21.191 1.77971 22 10.0833 22C18.387 22 20.1667 21.191 20.1667 17.4167C20.1667 13.6423 18.387 12.8333 10.0833 12.8333C1.77971 12.8333 0 13.6423 0 17.4167Z"></path>
                                                <path fill="currentColor" d="M4.58333 5.5C4.58333 8.53757 7.04577 11 10.0833 11C13.1209 11 15.5833 8.53757 15.5833 5.5C15.5833 2.46243 13.1209 0 10.0833 0C7.04577 0 4.58333 2.46243 4.58333 5.5Z"></path>
                                            </svg>
                                            <div class="">
                                                برای ارسال پاسخ لازم است وارد شده یا ثبت‌نام کنید
                                            </div>
                                        </div>
                                        <div class="ml-auto mt-4 mt-lg-0">
                                            <a class="" href="{{ route('login') }}">
                                                ورود یا ثبت نام
                                                <svg class="ml-2" width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill="currentColor" opacity="0.4" d="M12.7975 4.80957L16.4967 4.48242C17.3269 4.48242 18 5.16206 18 6.00032C18 6.83858 17.3269 7.51822 16.4967 7.51822L12.7975 7.19107C12.1463 7.19107 11.6183 6.65793 11.6183 6.00032C11.6183 5.34161 12.1463 4.80957 12.7975 4.80957Z"></path>
                                                    <path fill="currentColor" d="M0.37534 4.86984C0.433157 4.81146 0.649155 4.56471 0.852061 4.35983C2.03568 3.07656 5.12619 0.978153 6.7429 0.335965C6.98835 0.233523 7.60907 0.0154213 7.94179 0C8.25924 0 8.56251 0.0738021 8.8516 0.219203C9.21269 0.422985 9.50068 0.74463 9.65995 1.12355C9.76141 1.38572 9.92068 2.17331 9.92068 2.18763C10.0789 3.04792 10.165 4.44685 10.165 5.99339C10.165 7.46503 10.0789 8.80668 9.94904 9.68129C9.93486 9.69671 9.77559 10.6738 9.60214 11.0086C9.28469 11.6211 8.66397 12 7.99961 12H7.94179C7.50871 11.9857 6.5989 11.6057 6.5989 11.5924C5.06837 10.9502 2.05096 8.95319 0.837879 7.62585C0.837879 7.62585 0.495338 7.28438 0.346976 7.07178C0.115706 6.76556 7.15256e-05 6.38663 7.15256e-05 6.00771C7.15256e-05 5.58473 0.129888 5.19148 0.37534 4.86984Z"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endauth
                        </div>


                    </div>
                    <div class="col-lg-4">
                        <a href="{{ route('discuss-create-question') }}" class="btn btn-yellow btn-block mb-2 rounded-lg"><span class="mr-2">ایجاد پرسش جدید</span><i class="fa fa-plus"></i></a>

                        @include('discuss.component.selection-question')

                        <div class="card card-body border-0 shadow-none">
                            <h5 class="mb-1">گفتگو‌های مرتبط</h5>
                            <hr>

                            @forelse (App\Models\Question::where('category_id', $question->category_id)->where('id', '<>', $question->id)->take(20)->get() as $relatedQuestion)
                                <div class="mb-2">
                                    <a href="{{ route('discuss-question', $relatedQuestion->slug) }}" class="text-left p-2 btn-accent-pickled-bluewood rounded-lg d-flex">
                                        <div class="mr-2 my-auto">
                                            <svg fill="currentColor" height="20" width="20" version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32" xml:space="preserve">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path d="M25,8.2c-0.3-4-3.6-7.2-7.7-7.2H8.8C4.5,1,1,4.5,1,8.7V23c0,0.4,0.2,0.7,0.6,0.9C1.7,24,1.9,24,2,24c0.2,0,0.5-0.1,0.7-0.2 c1.3-1.1,2.8-2,4.3-2.7V30c0,0.4,0.2,0.7,0.6,0.9C7.7,31,7.9,31,8,31c0.2,0,0.5-0.1,0.7-0.2c2.9-2.6,6.7-4,10.6-4h4 c4.3,0,7.8-3.5,7.8-7.7v-3.4C31,12.1,28.4,9,25,8.2z M29,19.1c0,3.2-2.6,5.7-5.8,5.7h-4C15.6,24.8,12,25.9,9,28v-7.6 c1.4-0.4,2.8-0.6,4.3-0.6h4c4.3,0,7.8-3.5,7.8-7.7v-1.8c2.3,0.7,4,2.9,4,5.4V19.1z"></path>
                                                </g>
                                            </svg>
                                        </div>
                                        <div class="border-left pl-2 my-auto text-left font-size-14pt font-weight-light">
                                           {{ $relatedQuestion->subject }}
                                        </div>
                                    </a>
                                </div>
                            @empty
                            <div class="text-center py-8pt font-size-14pt font-fat text-50">
                                گفتگوی مرتبطی پیدا نشد!
                            </div>
                            @endforelse
                        </div>

                        <div class="card card-body border-0 shadow-none">
                            <h5 class="mb-1"> کاربران برتر 30 روز قبل </h5>
                            <hr>
                            @include('discuss.component.top-users', [
                                ($numberOfDays = 30),
                                ($numberOfUsers = 15),
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')

    <div class="modal fade" id="modal-upload-image" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="m-2">
                    <div class="mx-2">
                        <div class="font-size-20pt font-bold mb-2">
                            آپلود تصویر
                        </div>
                        <div class="text-50 font-size-16pt">
                            پسوند‌های مجاز :‌ jpg , jpeg , png
                        </div>
                    </div>
                    <hr>
                    <div class="bg-light rounded-lg mt-4 p-2">
                        <form id="editor-upload-image" action="{{ route('editor-upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <input type="file" name="image" accept=".jpg, .jpeg, .png" class="form-control rounded-lg">
                                <span class="invalid-feedback error-text image_error" role="alert">
                                    <strong class="text-danger"></strong>
                                </span>
                            </div>
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-yellow rounded-lg mx-2">آپلود تصویر</button>
                                <button data-dismiss="modal" class="btn btn-light rounded-lg">انصراف</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <div class="modal fade" id="modal-send-report" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="d-flex flex-column py-4" style="max-width: 100%">
                    <div class="mx-auto">
                        <svg width="70" height="70" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier"> <rect width="24" height="24" fill="white"></rect>
                                <path fill="red" fill-rule="evenodd" clip-rule="evenodd" d="M7.10711 2.87868C7.66972 2.31607 8.43278 2 9.22843 2H14.7716C15.5672 2 16.3303 2.31607 16.8929 2.87868L21.1213 7.10711C21.6839 7.66972 22 8.43278 22 9.22843V14.7716C22 15.5672 21.6839 16.3303 21.1213 16.8929L16.8929 21.1213C16.3303 21.6839 15.5672 22 14.7716 22H9.22843C8.43278 22 7.66972 21.6839 7.10711 21.1213L2.87868 16.8929C2.31607 16.3303 2 15.5672 2 14.7716V9.22843C2 8.43278 2.31607 7.66972 2.87868 7.10711L7.10711 2.87868ZM13 8C13 7.44772 12.5523 7 12 7C11.4477 7 11 7.44772 11 8V13C11 13.5523 11.4477 14 12 14C12.5523 14 13 13.5523 13 13V8ZM13 15.9888C13 15.4365 12.5523 14.9888 12 14.9888C11.4477 14.9888 11 15.4365 11 15.9888V16C11 16.5523 11.4477 17 12 17C12.5523 17 13 16.5523 13 16V15.9888Z"></path>
                            </g>
                        </svg>
                    </div>
                    @auth
                    <form action="{{ route('send-report') }}" method="POST" class="send-report my-3 mx-auto">
                        @csrf
                        <input type="hidden" name="reportable_id" value="">
                        <input type="hidden" name="reportable_type" value="">
                        <p class="text-center font-size-16pt font-bold">
                            گزارش این مطلب به عنوان یک:
                        </p>
                        <div class="d-flex flex-column">
                            <div class="custom-control custom-radio mb-12pt font-size-16pt">
                                <input id="report-type1" type="radio" name="report" value="spam"  class="custom-control-input">
                                <label for="report-type1" class="custom-control-label">اسپم</label>
                            </div>
                            <div class="custom-control custom-radio mb-12pt font-size-16pt">
                                <input id="report-type2" type="radio" name="report" value="offensive-writing"  class="custom-control-input">
                                <label for="report-type2" class="custom-control-label">نوشته توهین آمیز</label>
                            </div>
                            <div class="custom-control custom-radio mb-12pt font-size-16pt">
                                <input id="report-type3" type="radio" name="report" value="violation-of-rules"  class="custom-control-input">
                                <label for="report-type3" class="custom-control-label">نقض قوانین</label>
                            </div>
                            <div class="custom-control custom-radio mb-12pt font-size-16pt">
                                <input id="report-type4" type="radio" name="report" value="other"  class="custom-control-input">
                                <label for="report-type4" class="custom-control-label">موارد دیگر</label>
                            </div>
                        </div>
                        <div class="form-group mt-4 d-flex">
                            <button class="btn btn-light rounded-lg mr-2 " data-dismiss="modal">انصراف</button>
                            <button class="btn btn-accent rounded-lg" type="submit">ارسال گزارش</button>
                        </div>
                    </form>
                    @endauth
                    @guest
                        <div class="mt-16pt d-flex flex-column alert alert-soft-accent border-0 rounded-lg mx-auto">
                            <p class="font-size-14pt font-bold">
                                برای ثبت گزارش تخلف وارد شده یا ثبت نام کنید
                            </p>
                            <a class="font-size-14pt font-bold mx-auto" href="{{ route('login') }}">ورود | ثبت نام</a>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>





@endsection

@section('script')
    <script src="/assets/js/editor/easymde/config.js"></script>
    <script type="text/javascript" src="/assets/js/manage-discuss.js"></script>
    <script type="text/javascript" src="/assets/js/discuss-like-dislike.js"></script>
    <script src="/assets/js/manage-bookmark.js"></script>
    <script src="/assets/js/copy-link.js"></script>

    <script src="/assets/js/scrollTo.js"></script>

@endsection

