<div class="card border-0 shadow-none">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-center mb-20pt">
            <div class="d-flex flex-column flex-md-row align-items-center flex mb-4pt mb-lg-0 text-center text-md-left">
                <a href="{{ route('profile-index', $question->user->username) }}" class="mb-16pt mb-md-0 mr-md-24pt avatar avatar-lg {{ Cache::has('is_online' . $question->user->id) ? 'border-success' : 'border-light' }} border-3 rounded-circle">
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
                                <p class="text-muted"><span class="text-primary">{{ jdate($question->created_at)->ago() }}</span> توسط <a href="{{ route('profile-index', $question->user->username) }}" class="text-primary">{{ $question->user->first_name.' '.$question->user->last_name }}</a> مطرح شد</p>
                            @else
                                <p class="text-muted"><span class="text-primary">{{ jdate($question->answers->last()->created_at)->ago() }}</span> توسط <a href="{{ route('profile-index', $question->answers->last()->user->username) }}" class="text-primary">{{ $question->answers->last()->user->first_name.' '.$question->answers->last()->user->last_name }}</a> آپدیت شد </p>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            <div class="ml-lg-16pt">
                <div class="text-center">
                    @if( $question->best_answer )
                        <button class="btn btn-sm btn-yellow p-1 rounded-lg" data-toggle="tooltip" data-title="دارای بهترین پاسخ">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 7.00018L10 17.0002L5 12.0002" stroke="#292929" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    @endif
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
                    <a href="{{ route('discuss-question', $question->slug) }}" class="btn btn-sm btn-light p-1 rounded-lg">
                        <svg class="mr-2" fill="" width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10,19 C10,19.8897227 8.9391917,20.319213 8.3190139,19.7330526 L8.2407434,19.6507914 L2.2407434,12.6507914 C1.94650147,12.3075091 1.92198131,11.814562 2.16718292,11.4463356 L2.2407434,11.3492086 L8.2407434,4.34920863 C8.81976724,3.6736808 9.90470154,4.03795234 9.99410748,4.88660488 L10,5 L10,8 L11.0379967,8.08649972 C17.1341361,8.59451134 21.8458884,13.616576 21.9962945,19.6999759 L22,20 L19.3412157,18.4806947 C16.6386172,16.9363527 13.5975935,16.0874335 10.491017,16.0064025 L10,16 L10,19 Z"/>
                        </svg>
                        <span>{{ $question->answers->count() }}</span>
                        <span class="ml-2">پاسخ</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="px-2 mb-16pt">
            <h4 class="">
                <a href="{{ route('discuss-question', $question->slug) }}" class="text-dark">{{ $question->subject }}</a>
            </h4>

            <div class="text-70 font-size-16pt my-8pt text-justify lh-28pt">
                {!! Str::words($question->question, 50, '...') !!}
            </div>
        </div>
        @if($question->tags->count())
            <hr>
            <div class="mt-16pt">
                <div class="col-sm-12 col-md-8 col-lg-8">
                    <div class="d-flex align-items-start " style="overflow-x: auto!important;">
                        @foreach($question->tags as $tag)
                            <a href="" class="mx-1  badge-lg badge-light rounded">#{{ $tag }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
