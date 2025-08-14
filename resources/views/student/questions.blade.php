@extends('student.layouts.master')

@section('title', 'لیست پرسش‌ها')

@section('student-content')

        @include('student.layouts.user-data-header')

        <hr>
        <style>
            .tabbable .nav-pills {
                overflow-x: auto;
                overflow-y:hidden;
                flex-wrap: nowrap;
            }
            .tabbable .nav-pills .nav-link {
                white-space: nowrap;
            }
        </style>



        <div class="p-2">
            <nav class="tabbable">
                <div class="card-header-tabs-basic nav" role="tablist">

                    <a class="active align-items-center mb-0 font-bold" data-toggle="tab" href="#questions">پرسش‌ها</a>

                    <a class="align-items-center mb-0 font-bold" data-toggle="tab" href="#questions-locked">پرسش‌های قفل شده</a>

                    <a class="align-items-center mb-0 font-bold" data-toggle="tab" href="#answers">پاسخ‌ها</a>

                </div>
            </nav>
        </div>



        <div class="tab-content mt-3 text-70">
            <div class="tab-pane fade show active" id="questions">
                @if(!count($questions))
                    <div class="text-center my-3 p-3">
                        <h5 class="text-muted">موردی برای نمایش وجود ندارد!</h5>
                        <object data="/assets/images/other/svg/yellow/empty/no_data.svg" width="200" height="200"> </object>
                    </div>
                @else
                    <div class="row card-group-row my-3">
                        @foreach($questions as $question)
                            <div class="col-sm-6 card-group-row__col">

                                <div class="card border-0 shadow-none card-group-row__card">

                                    <div class="card-body d-flex flex-column py-lg-0">
                                        <div class="d-flex flex-column flex-lg-row align-items-center mb-8pt">
                                            <div class="d-flex flex-column flex-md-row align-items-center flex mb-4pt mb-lg-0 text-center text-md-left">
                                                <a href="{{ route('profile-index', $question->user->username) }}" class="avatar avatar-lg border-primary border-2 mb-16pt mb-md-0 mr-md-24pt rounded-circle">
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

                                        <div class="mb-3">
                                            <a href="{{ route('discuss-question', $question->slug) }}" class="font-size-16pt font-bold">
                                                {!! $question->subject !!}
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="tab-pane fade" id="questions-locked">
                <div class="text-center my-3 p-3">
                    <h5 class="text-muted">موردی برای نمایش وجود ندارد!</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="130" viewBox="0 0 647.63626 632.17383" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M687.3279,276.08691H512.81813a15.01828,15.01828,0,0,0-15,15v387.85l-2,.61005-42.81006,13.11a8.00676,8.00676,0,0,1-9.98974-5.31L315.678,271.39691a8.00313,8.00313,0,0,1,5.31006-9.99l65.97022-20.2,191.25-58.54,65.96972-20.2a7.98927,7.98927,0,0,1,9.99024,5.3l32.5498,106.32Z" transform="translate(-276.18187 -133.91309)" fill="#f2f2f2"/><path d="M725.408,274.08691l-39.23-128.14a16.99368,16.99368,0,0,0-21.23-11.28l-92.75,28.39L380.95827,221.60693l-92.75,28.4a17.0152,17.0152,0,0,0-11.28028,21.23l134.08008,437.93a17.02661,17.02661,0,0,0,16.26026,12.03,16.78926,16.78926,0,0,0,4.96972-.75l63.58008-19.46,2-.62v-2.09l-2,.61-64.16992,19.65a15.01489,15.01489,0,0,1-18.73-9.95l-134.06983-437.94a14.97935,14.97935,0,0,1,9.94971-18.73l92.75-28.4,191.24024-58.54,92.75-28.4a15.15551,15.15551,0,0,1,4.40966-.66,15.01461,15.01461,0,0,1,14.32032,10.61l39.0498,127.56.62012,2h2.08008Z" transform="translate(-276.18187 -133.91309)" fill="#3f3d56"/><path d="M398.86279,261.73389a9.0157,9.0157,0,0,1-8.61133-6.3667l-12.88037-42.07178a8.99884,8.99884,0,0,1,5.9712-11.24023l175.939-53.86377a9.00867,9.00867,0,0,1,11.24072,5.9707l12.88037,42.07227a9.01029,9.01029,0,0,1-5.9707,11.24072L401.49219,261.33887A8.976,8.976,0,0,1,398.86279,261.73389Z" transform="translate(-276.18187 -133.91309)" fill="#fed700"/><circle cx="190.15351" cy="24.95465" r="20" fill="#fed700"/><circle cx="190.15351" cy="24.95465" r="12.66462" fill="#fff"/><path d="M878.81836,716.08691h-338a8.50981,8.50981,0,0,1-8.5-8.5v-405a8.50951,8.50951,0,0,1,8.5-8.5h338a8.50982,8.50982,0,0,1,8.5,8.5v405A8.51013,8.51013,0,0,1,878.81836,716.08691Z" transform="translate(-276.18187 -133.91309)" fill="#e6e6e6"/><path d="M723.31813,274.08691h-210.5a17.02411,17.02411,0,0,0-17,17v407.8l2-.61v-407.19a15.01828,15.01828,0,0,1,15-15H723.93825Zm183.5,0h-394a17.02411,17.02411,0,0,0-17,17v458a17.0241,17.0241,0,0,0,17,17h394a17.0241,17.0241,0,0,0,17-17v-458A17.02411,17.02411,0,0,0,906.81813,274.08691Zm15,475a15.01828,15.01828,0,0,1-15,15h-394a15.01828,15.01828,0,0,1-15-15v-458a15.01828,15.01828,0,0,1,15-15h394a15.01828,15.01828,0,0,1,15,15Z" transform="translate(-276.18187 -133.91309)" fill="#3f3d56"/><path d="M801.81836,318.08691h-184a9.01015,9.01015,0,0,1-9-9v-44a9.01016,9.01016,0,0,1,9-9h184a9.01016,9.01016,0,0,1,9,9v44A9.01015,9.01015,0,0,1,801.81836,318.08691Z" transform="translate(-276.18187 -133.91309)" fill="#fed700"/><circle cx="433.63626" cy="105.17383" r="20" fill="#fed700"/><circle cx="433.63626" cy="105.17383" r="12.18187" fill="#fff"/></svg>
                </div>
            </div>
            <div class="tab-pane fade" id="answers">
                @if(!count($answers))
                    <div class="text-center my-3 p-3">
                        <h5 class="text-muted">موردی برای نمایش وجود ندارد!</h5>
                        <svg xmlns="http://www.w3.org/2000/svg" width="130" viewBox="0 0 647.63626 632.17383" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M687.3279,276.08691H512.81813a15.01828,15.01828,0,0,0-15,15v387.85l-2,.61005-42.81006,13.11a8.00676,8.00676,0,0,1-9.98974-5.31L315.678,271.39691a8.00313,8.00313,0,0,1,5.31006-9.99l65.97022-20.2,191.25-58.54,65.96972-20.2a7.98927,7.98927,0,0,1,9.99024,5.3l32.5498,106.32Z" transform="translate(-276.18187 -133.91309)" fill="#f2f2f2"/><path d="M725.408,274.08691l-39.23-128.14a16.99368,16.99368,0,0,0-21.23-11.28l-92.75,28.39L380.95827,221.60693l-92.75,28.4a17.0152,17.0152,0,0,0-11.28028,21.23l134.08008,437.93a17.02661,17.02661,0,0,0,16.26026,12.03,16.78926,16.78926,0,0,0,4.96972-.75l63.58008-19.46,2-.62v-2.09l-2,.61-64.16992,19.65a15.01489,15.01489,0,0,1-18.73-9.95l-134.06983-437.94a14.97935,14.97935,0,0,1,9.94971-18.73l92.75-28.4,191.24024-58.54,92.75-28.4a15.15551,15.15551,0,0,1,4.40966-.66,15.01461,15.01461,0,0,1,14.32032,10.61l39.0498,127.56.62012,2h2.08008Z" transform="translate(-276.18187 -133.91309)" fill="#3f3d56"/><path d="M398.86279,261.73389a9.0157,9.0157,0,0,1-8.61133-6.3667l-12.88037-42.07178a8.99884,8.99884,0,0,1,5.9712-11.24023l175.939-53.86377a9.00867,9.00867,0,0,1,11.24072,5.9707l12.88037,42.07227a9.01029,9.01029,0,0,1-5.9707,11.24072L401.49219,261.33887A8.976,8.976,0,0,1,398.86279,261.73389Z" transform="translate(-276.18187 -133.91309)" fill="#fed700"/><circle cx="190.15351" cy="24.95465" r="20" fill="#fed700"/><circle cx="190.15351" cy="24.95465" r="12.66462" fill="#fff"/><path d="M878.81836,716.08691h-338a8.50981,8.50981,0,0,1-8.5-8.5v-405a8.50951,8.50951,0,0,1,8.5-8.5h338a8.50982,8.50982,0,0,1,8.5,8.5v405A8.51013,8.51013,0,0,1,878.81836,716.08691Z" transform="translate(-276.18187 -133.91309)" fill="#e6e6e6"/><path d="M723.31813,274.08691h-210.5a17.02411,17.02411,0,0,0-17,17v407.8l2-.61v-407.19a15.01828,15.01828,0,0,1,15-15H723.93825Zm183.5,0h-394a17.02411,17.02411,0,0,0-17,17v458a17.0241,17.0241,0,0,0,17,17h394a17.0241,17.0241,0,0,0,17-17v-458A17.02411,17.02411,0,0,0,906.81813,274.08691Zm15,475a15.01828,15.01828,0,0,1-15,15h-394a15.01828,15.01828,0,0,1-15-15v-458a15.01828,15.01828,0,0,1,15-15h394a15.01828,15.01828,0,0,1,15,15Z" transform="translate(-276.18187 -133.91309)" fill="#3f3d56"/><path d="M801.81836,318.08691h-184a9.01015,9.01015,0,0,1-9-9v-44a9.01016,9.01016,0,0,1,9-9h184a9.01016,9.01016,0,0,1,9,9v44A9.01015,9.01015,0,0,1,801.81836,318.08691Z" transform="translate(-276.18187 -133.91309)" fill="#fed700"/><circle cx="433.63626" cy="105.17383" r="20" fill="#fed700"/><circle cx="433.63626" cy="105.17383" r="12.18187" fill="#fff"/></svg>
                    </div>
                @else
                    <div class="row card-group-row my-3">
                        @foreach($answers as $answer)
                            <div class="col-sm-6 card-group-row__col">

                                <div class="card border-0 shadow-none card-group-row__card">

                                    <div class="card-body d-flex flex-column py-lg-0">
                                        <div class="d-flex flex-column flex-lg-row align-items-center mb-8pt">
                                            <div class="d-flex flex-column flex-md-row align-items-center flex mb-4pt mb-lg-0 text-center text-md-left">
                                                <a href="{{ route('profile-index', $answer->user->username) }}" class="avatar avatar-lg border-primary border-2 mb-16pt mb-md-0 mr-md-24pt rounded-circle">
                                                    <img src="{{ $answer->user->profile_pic }}" class="avatar-img rounded-circle  shadow-lg" alt="{{ $answer->user->username }}">
                                                </a>
                                                <div class="flex pt-md-4 pt-lg-4">
                                                    <a href="{{ route('profile-index', $answer->user->username) }}">
                                                        <h5 class="mb-4pt">
                                                            {{ $answer->user->first_name.' '.$answer->user->last_name }}
                                                        </h5>
                                                    </a>
                                                    <div class="row d-flex ">
                                                        <span class="col-sm-auto mb-2 px-2">
                                                            <p class="text-muted"><span class="">{{ $answer->user->username.'@' }}</span></p>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="ml-lg-16pt">
                                                <div class="text-center">
                                                    <a href="{{ route('discuss-question', $answer->question->slug) }}" class="btn btn-sm btn-light p-0 px-4pt">
                                                        <i class="material-icons mr-2 font-size-16pt ">reply</i>

                                                        <span class="ml-2">مشاهده پرسش</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="text-black-50 flex text-justify font-size-16pt">
                                            {!! Str::words($answer->answer, 30, '...') !!}
                                        </p>

                                        <div class="mt-auto bg-light rounded-lg p-2 my-3 font-size-12pt">
                                            پاسخ مربوط به گفتگو: <a href="{{ route('discuss-question', $answer->question->slug) }}" class="text-primary">{{ $answer->question->subject }}</a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

@endsection
@section('script')
    <script src="/assets/js/manage-follow.js"></script>
@endsection
