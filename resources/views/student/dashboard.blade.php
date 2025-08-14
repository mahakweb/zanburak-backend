@extends('student.layouts.master')

@section('title', 'داشبورد کاربری')

@section('head')
@endsection


@section('student-content')

     @include('student.layouts.user-data-header')


    @if ( ! count(auth()->user()->courses))

        <div class="card card-body bg-transparent">
            <div class="d-flex flex-column flex-lg-row align-items-center">
                <div class="avatar-xxl mb-4 mb-lg-0">
                    <object class="w-100" data="/assets/images/other/svg/yellow/learning/developer_activity.svg" type="image/svg+xml">
                    </object>
                </div>
                <div class="ml-32pt">
                    <h5>برای تبدیل شدن به یک برنامه‌نویس اولین دوره آموزشی‌ات از زنبورک رو شروع کن</h5>
                    <p class="text-70">اطمینان از کیفیت از طرف ما، تلاش و پشتکار از طرف شما، آخ نتیجه اش دیدنیه !</p>
                    <a href="{{ route('all-course') }}" class="text-primary font-size-14pt">
                        شروع یادگیری
                        <svg class="ml-2" width="15" height="15" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                            <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

    @else
        <div dir="ltr" class="position-relative carousel-card col-lg-12 p-0 mx-auto">
            <div class="row d-block js-mdk-carousel" id="carousel-courses">
                <div class="d-flex flex-row mx-12pt">
                    <div class="mb-2 text-center text-lg-right">
                        <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-courses" role="button" data-slide="prev">
                            <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                            </svg>
                            {{-- <i class="carousel-control-icon fa fa-chevron-right" aria-hidden="true"></i>  --}}
                            <span class="sr-only">previous</span>
                        </a>
                        <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-courses" role="button" data-slide="next">
                            <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4" d="M7.12872 6.34863L2.5608 5.94466C1.53567 5.94466 0.704529 6.7839 0.704529 7.81901C0.704529 8.85412 1.53567 9.69336 2.5608 9.69336L7.12872 9.28938C7.93292 9.28938 8.58491 8.63105 8.58491 7.81901C8.58491 7.00561 7.93292 6.34863 7.12872 6.34863" fill="currentColor"></path>
                                <path d="M22.4681 6.42292C22.3967 6.35083 22.13 6.04614 21.8795 5.79314C20.4179 4.20852 16.6016 1.61734 14.6053 0.824345C14.3022 0.697847 13.5357 0.428528 13.1248 0.409485C12.7328 0.409485 12.3583 0.500618 12.0014 0.680164C11.5555 0.931801 11.1999 1.32898 11.0032 1.79689C10.8779 2.12061 10.6812 3.09315 10.6812 3.11084C10.4859 4.17315 10.3795 5.9006 10.3795 7.81032C10.3795 9.62754 10.4859 11.2843 10.6462 12.3643C10.6637 12.3833 10.8604 13.5898 11.0746 14.0033C11.4666 14.7596 12.2331 15.2275 13.0534 15.2275H13.1248C13.6596 15.2098 14.7831 14.7405 14.7831 14.7242C16.673 13.9312 20.399 11.4652 21.897 9.82613C21.897 9.82613 22.3199 9.40447 22.5031 9.14195C22.7887 8.76381 22.9315 8.29591 22.9315 7.828C22.9315 7.30568 22.7712 6.82009 22.4681 6.42292" fill="currentColor"></path>
                            </svg>
                            {{-- <i class="carousel-control-icon fa fa-chevron-left" aria-hidden="true"></i>  --}}
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                    <div class="mr-auto d-flex flex-row">
                        <a class="ml-1 font-size-16pt" href="{{ route('student-courses') }}">
                            همه |
                        </a>
                        <h5>
                            دوره های جاری
                            <span class="mr-2 text-secondary">&#x2022;</span>
                        </h5>

                    </div>
                </div>

                <div class="mdk-carousel__content" dir="rtl">

                    @foreach(auth()->user()->courses()->limit(5)->get()  as $course)
                        <div class="col-md-4 col-sm-12 col-lg-3">
                            @include('course.course-card', ['course' => $course])
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    @endif

    <hr>


    @if ( ! count(auth()->user()->questions))

        <div class="card card-body bg-transparent">
            <div class="d-flex flex-column flex-lg-row align-items-center">
                <div class="avatar-xxl">
                    <object class="w-100" data="/assets/images/other/svg/yellow/question/question.svg" type="image/svg+xml">
                    </object>
                </div>
                <div class="ml-32pt">
                    <h5>سوالت رو بپرس تا دوستات بهت کمک کنن</h5>
                    <p class="text-70">سایر کاربران به پرسش‌های شما پاسخ میدن و می‌تونید جواب سوالات خودتون رو زودتر پیدا کنید.</p>
                    <a href="{{ route('discuss-create-question') }}" class="text-primary font-size-14pt">
                         ایجاد اولین پرسش
                         <svg class="ml-2" width="15" height="15" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                            <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

    @else

        <div dir="ltr" class="position-relative carousel-card col-lg-12 p-0 mx-auto">
            <div class="row d-block js-mdk-carousel" id="carousel-questions">
                <div class="d-flex flex-row mx-12pt">
                    <div class="mb-2 text-center text-lg-right">
                        <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-questions" role="button" data-slide="prev">
                            <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4" d="M16.5073 6.34863L21.0752 5.94466C22.1003 5.94466 22.9315 6.7839 22.9315 7.81901C22.9315 8.85412 22.1003 9.69336 21.0752 9.69336L16.5073 9.28938C15.7031 9.28938 15.0511 8.63105 15.0511 7.81901C15.0511 7.00561 15.7031 6.34863 16.5073 6.34863" fill="currentColor"></path>
                                <path d="M1.16786 6.42292C1.23926 6.35083 1.50598 6.04614 1.75653 5.79314C3.21811 4.20852 7.03437 1.61734 9.03073 0.824345C9.33382 0.697847 10.1003 0.428528 10.5112 0.409485C10.9032 0.409485 11.2776 0.500618 11.6346 0.680164C12.0805 0.931801 12.4361 1.32898 12.6328 1.79689C12.7581 2.12061 12.9548 3.09315 12.9548 3.11084C13.1501 4.17315 13.2565 5.9006 13.2565 7.81032C13.2565 9.62754 13.1501 11.2843 12.9898 12.3643C12.9723 12.3833 12.7756 13.5898 12.5614 14.0033C12.1694 14.7596 11.4029 15.2275 10.5826 15.2275H10.5112C9.97638 15.2098 8.85292 14.7405 8.85292 14.7242C6.96297 13.9312 3.23697 11.4652 1.73902 9.82613C1.73902 9.82613 1.31604 9.40447 1.13284 9.14195C0.84726 8.76381 0.70447 8.29591 0.70447 7.828C0.70447 7.30568 0.864772 6.82009 1.16786 6.42292" fill="currentColor"></path>
                            </svg>
                            {{-- <i class="carousel-control-icon fa fa-chevron-right" aria-hidden="true"></i>  --}}
                            <span class="sr-only">previous</span>
                        </a>
                        <a class="btn btn-sm btn-white js-mdk-carousel-control" href="#carousel-questions" role="button" data-slide="next">
                            <svg class="" width="20" height="20" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4" d="M7.12872 6.34863L2.5608 5.94466C1.53567 5.94466 0.704529 6.7839 0.704529 7.81901C0.704529 8.85412 1.53567 9.69336 2.5608 9.69336L7.12872 9.28938C7.93292 9.28938 8.58491 8.63105 8.58491 7.81901C8.58491 7.00561 7.93292 6.34863 7.12872 6.34863" fill="currentColor"></path>
                                <path d="M22.4681 6.42292C22.3967 6.35083 22.13 6.04614 21.8795 5.79314C20.4179 4.20852 16.6016 1.61734 14.6053 0.824345C14.3022 0.697847 13.5357 0.428528 13.1248 0.409485C12.7328 0.409485 12.3583 0.500618 12.0014 0.680164C11.5555 0.931801 11.1999 1.32898 11.0032 1.79689C10.8779 2.12061 10.6812 3.09315 10.6812 3.11084C10.4859 4.17315 10.3795 5.9006 10.3795 7.81032C10.3795 9.62754 10.4859 11.2843 10.6462 12.3643C10.6637 12.3833 10.8604 13.5898 11.0746 14.0033C11.4666 14.7596 12.2331 15.2275 13.0534 15.2275H13.1248C13.6596 15.2098 14.7831 14.7405 14.7831 14.7242C16.673 13.9312 20.399 11.4652 21.897 9.82613C21.897 9.82613 22.3199 9.40447 22.5031 9.14195C22.7887 8.76381 22.9315 8.29591 22.9315 7.828C22.9315 7.30568 22.7712 6.82009 22.4681 6.42292" fill="currentColor"></path>
                            </svg>
                            {{-- <i class="carousel-control-icon fa fa-chevron-left" aria-hidden="true"></i>  --}}
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                    <div class="mr-auto d-flex flex-row">
                        <a class="ml-1 font-size-16pt" href="{{ route('student-questions') }}">
                            همه |
                        </a>
                        <h5>
                            پرسش ها
                            <span class="mr-2 text-secondary">&#x2022;</span>
                        </h5>

                    </div>
                </div>

                <div class="mdk-carousel__content" dir="rtl">

                    @foreach(auth()->user()->questions()->limit(5)->get()  as $question)
                        <div class="col-md-6 col-lg-6 col-sm-6">
                            <div class="card-group-row__col">
                                <div dir="rtl" class="card border-0 shadow-none card-group-row__card">

                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex flex-column flex-lg-row align-items-center mb-8pt">
                                            <div class="d-flex flex-column flex-md-row align-items-center flex mb-4pt mb-lg-0 text-center text-md-left">
                                                <a href="{{ route('profile-index', $question->user->username) }}" class="avatar avatar-lg mb-16pt mb-md-0 mr-md-24pt border-primary border-2 rounded-circle">
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
                                                        @if( ! $question->answers->count() )
                                                            <p class="text-muted"><span class="">{{ jdate($question->created_at)->ago() }}</span> توسط <a href="{{ route('profile-index', $question->user->username) }}" class="text-primary">{{ $question->user->first_name.' '.$question->user->last_name }}</a> مطرح شد</p>
                                                        @else
                                                            <p class="text-muted"><span class="">{{ jdate($question->answers->last()->created_at)->ago() }}</span> توسط <a href="{{ route('profile-index', $question->answers->last()->user->username) }}" class="text-primary">{{ $question->answers->last()->user->first_name.' '.$question->answers->last()->user->last_name }}</a> آپدیت شد </p>
                                                        @endif
                                                    </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="ml-lg-16pt">
                                                <div class="text-center">
                                                    <a href="{{ route('discuss-question', $question->slug) }}" class="btn btn-sm btn-light p-0 px-4pt">
                                                        <i class="material-icons mr-2 font-size-16pt ">reply</i>
                                                        <span>{{ $question->answers->count() }}</span>
                                                        <span class="ml-2">پاسخ</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="font-size-16pt text-center text-md-left text-lg-left">
                                            <a href="{{ route('discuss-question', $question->slug) }}" class="font-bold">
                                                {!! Str::words($question->subject, 6, '...') !!}
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>



    @endif





@endsection



@section('script')

    <script src="/assets/js/manage-like.js"></script>

@endsection
