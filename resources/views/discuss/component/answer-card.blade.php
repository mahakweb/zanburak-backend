<div class="d-flex flex-column flex-lg-row align-items-center mb-20pt">
    <div class="d-flex flex-column flex-md-row align-items-center flex mb-4pt mb-lg-0 text-center text-md-left">
        <a href="{{ route('profile-index', $answer->user->username) }}" class="mb-16pt mb-md-0 mr-md-24pt avatar avatar-lg {{ Cache::has('is_online' . $answer->user->id) ? 'border-success' : 'border-light' }} border-3 rounded-circle">
            <img src="{{ $answer->user->profile_pic }}" class="avatar-img rounded-circle shadow-lg" alt="{{ $answer->user->username }}">
        </a>
        <div class="flex pt-md-4 pt-lg-4 text-muted">
            <a href="{{ route('profile-index', $answer->user->username) }}">
                <h5 class="mb-4pt">
                    {{ $answer->user->first_name.' '.$answer->user->last_name }}
                </h5>
            </a>
            <div class="row d-flex ">
                <span class="col-sm-auto mb-2 px-2 border-right-lg ">{{ $answer->user->username.' @ ' }}</span>
                <span class="col-sm-auto"><i class="material-icons mr-1 font-size-16pt">schedule</i><span class="text-primary">{{ ($answer->updated_at > $answer->created_at) ? jdate($answer->updated_at)->ago() : jdate($answer->created_at)->ago() }}</span> توسط <a href="{{ route('profile-index', $answer->user->username) }}" class="text-primary">{{ $answer->user->first_name.' '.$answer->user->last_name }}</a> {{ ($answer->updated_at > $answer->created_at) ? 'آپدیت' : 'مطرح' }} شد</span>
            </div>
        </div>
    </div>
    <div class="ml-lg-16pt">
        <div class="text-center">
            @if( $answer->isBest() )
                <button class="btn btn-sm btn-yellow p-1 rounded-lg font-bold" data-toggle="tooltip" data-title="بهترین پاسخ">
                    <svg class="mr-2" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M8 1.25C4.27208 1.25 1.25 4.27208 1.25 8L1.25 16C1.25 19.7279 4.27208 22.75 8 22.75L16 22.75C19.7279 22.75 22.75 19.7279 22.75 16L22.75 8C22.75 4.27208 19.7279 1.25 16 1.25L8 1.25ZM16.5303 10.0303C16.8232 9.73742 16.8232 9.26255 16.5303 8.96966C16.2374 8.67676 15.7626 8.67676 15.4697 8.96966L10.8434 13.5959C10.7458 13.6935 10.5875 13.6935 10.4899 13.5959L8.53033 11.6363C8.23744 11.3434 7.76256 11.3434 7.46967 11.6363C7.17678 11.9292 7.17678 12.4041 7.46967 12.697L9.42923 14.6565C10.1126 15.34 11.2207 15.34 11.9041 14.6565L16.5303 10.0303Z"/>
                    </svg>
                     بهترین پاسخ
                </button>
            @endif
            <button class="btn btn-sm btn-outline-accent rounded-lg p-1 mx-1" data-toggle="modal" data-target="#modal-send-report" data-model="{{ get_class($answer) }}" data-id="{{ $answer->id }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M12 14.75C11.59 14.75 11.25 14.41 11.25 14V9C11.25 8.59 11.59 8.25 12 8.25C12.41 8.25 12.75 8.59 12.75 9V14C12.75 14.41 12.41 14.75 12 14.75Z"/>
                    <path fill="currentColor" d="M12 18C11.94 18 11.87 17.99 11.8 17.98C11.74 17.97 11.68 17.95 11.62 17.92C11.56 17.9 11.5 17.87 11.44 17.83C11.39 17.79 11.34 17.75 11.29 17.71C11.11 17.52 11 17.26 11 17C11 16.74 11.11 16.48 11.29 16.29C11.34 16.25 11.39 16.21 11.44 16.17C11.5 16.13 11.56 16.1 11.62 16.08C11.68 16.05 11.74 16.03 11.8 16.02C11.93 15.99 12.07 15.99 12.19 16.02C12.26 16.03 12.32 16.05 12.38 16.08C12.44 16.1 12.5 16.13 12.56 16.17C12.61 16.21 12.66 16.25 12.71 16.29C12.89 16.48 13 16.74 13 17C13 17.26 12.89 17.52 12.71 17.71C12.66 17.75 12.61 17.79 12.56 17.83C12.5 17.87 12.44 17.9 12.38 17.92C12.32 17.95 12.26 17.97 12.19 17.98C12.13 17.99 12.06 18 12 18Z" />
                    <path fill="currentColor" d="M18.06 22.16H5.93998C3.98998 22.16 2.49998 21.45 1.73998 20.17C0.989976 18.89 1.08998 17.24 2.03998 15.53L8.09998 4.63C9.09998 2.83 10.48 1.84 12 1.84C13.52 1.84 14.9 2.83 15.9 4.63L21.96 15.54C22.91 17.25 23.02 18.89 22.26 20.18C21.5 21.45 20.01 22.16 18.06 22.16ZM12 3.34C11.06 3.34 10.14 4.06 9.40998 5.36L3.35998 16.27C2.67998 17.49 2.56998 18.61 3.03998 19.42C3.50998 20.23 4.54998 20.67 5.94998 20.67H18.07C19.47 20.67 20.5 20.23 20.98 19.42C21.46 18.61 21.34 17.5 20.66 16.27L14.59 5.36C13.86 4.06 12.94 3.34 12 3.34Z"/>
                </svg>
            </button>
            <button link="{{ route('discuss-question',$question->slug).'#'.$answer->id }}" class="copy-link btn btn-sm btn-outline-secondary rounded-lg p-1">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M11.1667 0.25C8.43733 0.25 6.25 2.50265 6.25 5.25H12.8333C15.5627 5.25 17.75 7.50265 17.75 10.25V18.75H17.8333C20.5627 18.75 22.75 16.4974 22.75 13.75V5.25C22.75 2.50265 20.5627 0.25 17.8333 0.25H11.1667Z">
                    </path>
                    <path fill="currentColor" opacity="0.5" d="M2 10.25C2 7.90279 3.86548 6 6.16667 6H12.8333C15.1345 6 17 7.90279 17 10.25V18.75C17 21.0972 15.1345 23 12.8333 23H6.16667C3.86548 23 2 21.0972 2 18.75V10.25Z">
                    </path>
                </svg>
            </button>
        </div>
    </div>
</div>
<div class="d-flex">
    <div class="like-dislike-section">
        <div class="d-flex flex-column mt-8pt">
            <div class="mx-auto">
                <form class="discuss-like" action="{{ route('discuss-like') }}" method="POST">
                    @csrf
                    <input type="hidden" name="likeable_type" value="{{ get_class($answer) }}">
                    <input type="hidden" name="likeable_id" value="{{ $answer->id }}">
                    <button type="submit" class="btn btn-sm text-hover-yellow bg-transparent p-0 border-0">
                        <svg width="25" height="25" class="cursor-pointer text-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                            <path fill="currentColor" d="M279 224H41c-21.4 0-32.1-25.9-17-41L143 64c9.4-9.4 24.6-9.4 33.9 0l119 119c15.2 15.1 4.5 41-16.9 41z" class=""></path>
                        </svg>
                    </button>
                </form>
            </div>
            <div class="mx-auto my-2">
                <span class="count font-size-16pt font-bold text-50" dir="ltr">
                    {{ $answer->likes()->where('type', 'like')->count() - $answer->likes()->where('type', 'dislike')->count() }}
                </span>
            </div>
            <div class="mx-auto">
                <form class="discuss-dislike" action="{{ route('discuss-dislike') }}" method="POST">
                    @csrf
                    <input type="hidden" name="likeable_type" value="{{ get_class($answer) }}">
                    <input type="hidden" name="likeable_id" value="{{ $answer->id }}">
                    <button type="submit" class="btn btn-sm text-hover-yellow bg-transparent p-0 border-0">
                        <svg width="25" height="25" class="text-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                            <path fill="currentColor" d="M41 288h238c21.4 0 32.1 25.9 17 41L177 448c-9.4 9.4-24.6 9.4-33.9 0L24 329c-15.1-15.1-4.4-41 17-41z" class=""></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="ml-1 ml-lg-2 w-100 o-hidden">
        <div class="px-1">
            <div class="content-area text-70 font-size-16pt mt-8pt mb-64pt lh-28pt text-justify">
                {!!  Illuminate\Mail\Markdown::parse($answer->answer) !!}
            </div>
        </div>
        @auth
            <hr>
            <div class="d-flex flex-row mt-4pt mb-2 mb-lg-0">
                @if( ($question->user_id == auth()->user()->id) && ( is_null($question->best_answer) ) )
                    <form class="set-best" action="{{ route('discuss-set-best-answer') }}" method="post">
                        @csrf
                        <input type="hidden" name="question_id" value="{{ $question->id }}">
                        <input type="hidden" name="answer_id" value="{{ $answer->id }}">
                        <button class="btn btn-sm btn-yellow font-bold p-1 rounded-lg mr-2">
                            <svg class="mr-2" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M8 1.25C4.27208 1.25 1.25 4.27208 1.25 8L1.25 16C1.25 19.7279 4.27208 22.75 8 22.75L16 22.75C19.7279 22.75 22.75 19.7279 22.75 16L22.75 8C22.75 4.27208 19.7279 1.25 16 1.25L8 1.25ZM16.5303 10.0303C16.8232 9.73742 16.8232 9.26255 16.5303 8.96966C16.2374 8.67676 15.7626 8.67676 15.4697 8.96966L10.8434 13.5959C10.7458 13.6935 10.5875 13.6935 10.4899 13.5959L8.53033 11.6363C8.23744 11.3434 7.76256 11.3434 7.46967 11.6363C7.17678 11.9292 7.17678 12.4041 7.46967 12.697L9.42923 14.6565C10.1126 15.34 11.2207 15.34 11.9041 14.6565L16.5303 10.0303Z"/>
                            </svg>
                            بهترین پاسخ
                        </button>
                    </form>
                @endif

                <div class="d-flex align-items-center justify-content-center justify-content-sm-end pb-2">
                    <a class="btn btn-sm btn-light rounded-lg" href="#send-answer">
                        <svg class="mr-2" fill="currentColor" width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10,19 C10,19.8897227 8.9391917,20.319213 8.3190139,19.7330526 L8.2407434,19.6507914 L2.2407434,12.6507914 C1.94650147,12.3075091 1.92198131,11.814562 2.16718292,11.4463356 L2.2407434,11.3492086 L8.2407434,4.34920863 C8.81976724,3.6736808 9.90470154,4.03795234 9.99410748,4.88660488 L10,5 L10,8 L11.0379967,8.08649972 C17.1341361,8.59451134 21.8458884,13.616576 21.9962945,19.6999759 L22,20 L19.3412157,18.4806947 C16.6386172,16.9363527 13.5975935,16.0874335 10.491017,16.0064025 L10,16 L10,19 Z"/>
                        </svg>
                            ارسال پاسخ
                    </a>
                </div>
            </div>
        @endauth
    </div>
</div>
