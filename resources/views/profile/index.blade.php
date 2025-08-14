@extends('layouts.master')

@section('title', 'پروفایل')

@section('content')
    <div class="mdk-header-layout__content page-content ">
        <div class="page-section">
            <div class="container page__container">
                @include('profile.profile-details', ['user' => $user])

                <div class="card card-body border-0 d-flex shadow-none">
                    <div class="mx-auto d-flex flex-column flex-lg-row">
                        <div class="font-size-24pt text-center mb-8pt font-bold border-right-lg px-24pt">
                            {{ number_format($user->currentScore(), 0, '.', ',') }}
                            <span class="text-50 ml-2 font-size-16pt">میزان تجربه</span>
                        </div>
                        <div class="font-size-24pt text-center mb-8pt font-bold border-right-lg px-24pt">
                            {{ number_format($user->questions->count(), 0, '.', ',') }}
                            <span class="text-50 ml-2 font-size-16pt">تعداد پرسش‌ها</span>
                        </div>
                        <div class="font-size-24pt text-center mb-8pt font-bold border-right-lg px-24pt">
                            {{ number_format($user->answers->count(), 0, '.', ',') }}
                            <span class="text-50 ml-2 font-size-16pt">تعداد پاسخ‌ها</span>
                        </div>
                        <div class="font-size-24pt text-center mb-8pt font-bold px-24pt">
                            {{ $user->BestAnswers()->count() }}
                            <span class="text-50 ml-2 font-size-16pt">تعداد پاسخ‌های برتر</span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-none">
                   <div class="card-body">
                       <h4><span class="mr-2 text-secondary">&#x2022;</span>درباره من</h4>
                       <div class="px-3 text-justify font-size-16pt lh-28pt">
                           {!! $user->info->about !!}
                       </div>
                   </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script src="/assets/js/manage-follow.js"></script>

@endsection

