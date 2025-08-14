
@foreach($comments as $comment)
    <ul class="list">
        <li>
            <div class="parent">
                <div class="card card-body bg-transparent shadow-none">
                    <div class="">
                        <div class="d-flex flex-column flex-lg-row flex-md-row">
                            <div class="d-flex">
                                <a href="{{ route('profile-index', $comment->user->username) }}" class="avatar border-3 rounded-circle {{ Cache::has('is_online' . $comment->user->id) ? 'border-success' : 'border-light' }} my-auto mr-12pt">
                                    <img src="{{ $comment->user->profile_pic }}" alt="{{ $comment->user->username }}" class="avatar avatar-img rounded-circle shadow">
                                </a>
                                <div class="my-auto">
                                    <a href="{{ route('profile-index', $comment->user->username) }}" class="font-size-14pt font-bold">{{ $comment->user->first_name.' '.$comment->user->last_name }}</a>
                                    <div class="text-muted font-size-12pt">{{ jdate($comment->created_at)->ago() }}</div>
                                </div>
                            </div>
                            <div class="ml-auto mt-3">
                                <div class="d-flex">
                                    @auth
                                        <button class="commentForm d-flex btn btn-sm-secondary rounded font-regular" data-parent-id="{{ $comment->id }}">
                                            <svg class="mr-2" width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path fill="currentcolor" fill-rule="evenodd" clip-rule="evenodd" d="M9.7071 3.29286C10.0976 3.68339 10.0976 4.31655 9.7071 4.70708L6.41421 7.99997H12C16.4183 7.99997 20 11.5817 20 16V20C20 20.5523 19.5523 21 19 21C18.4477 21 18 20.5523 18 20V16C18 12.6863 15.3137 9.99997 12 9.99997H6.41421L9.7071 13.2929C10.0976 13.6834 10.0976 14.3166 9.7071 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L3.29289 9.70708C2.90237 9.31655 2.90237 8.68339 3.29289 8.29286L8.29289 3.29286C8.68342 2.90234 9.31658 2.90234 9.7071 3.29286Z"></path>
                                                </g>
                                            </svg>
                                                پاسخ
                                            </button>
                                    @endauth
                                    <form id="send-like" action="{{ route('send-like') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="likeable_id" value="{{ $comment->id }}">
                                        <input type="hidden" name="likeable_type" value="{{ get_class($comment) }}">
                                        <button type="submit" class="ml-4pt rounded btn btn-sm-accent ">
                                            <svg class="mr-1" width="15" height="13"  viewBox="0 0 15 13" xmlns="http://www.w3.org/2000/svg">
                                                <path id="fillOrEmpty" fill="@auth {{ auth()->user()->hasLiked($comment) ? 'currentColor' : 'none' }} @else none @endauth" stroke="currentColor" d="M4.75 0.624878C5.80649 0.624878 6.77021 1.15065 7.5 1.74964C8.22979 1.15065 9.19351 0.624878 10.25 0.624878C12.5282 0.624878 14.375 2.31858 14.375 4.40774C14.375 8.62007 9.57964 11.0733 7.99879 11.7676C7.68036 11.9075 7.31964 11.9075 7.00121 11.7676C5.42036 11.0733 0.625 8.61997 0.625 4.40764C0.625 2.31848 2.47183 0.624878 4.75 0.624878Z" stroke-width="0.771644"></path>
                                            </svg>
                                            <span id="countOfLike">{{ $comment->likes()->count() }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-70 o-hidden">
                            {!!  Illuminate\Mail\Markdown::parse($comment->comment) !!}
                        </div>
                    </div>

                </div>
            </div>
            <!-- start Reply area -->
            @include('comment.reply-comment', ['reply_comments' => $comment->descendants()->sortBy('id')])
            <!-- end Reply area -->
        </li>
    </ul>
@endforeach
<div class="mb-32pt d-flex flex-row">
    {{ $comments->links('vendor.pagination.custom') }}
</div>



