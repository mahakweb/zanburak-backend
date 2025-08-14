<div class="card border-0 shadow-none">
    <div class="card-header border-bottom-0">
        @guest
            <div class="alert alert-info rounded-lg border-0 font-bold font-size-16pt">
                برای ثبت دیدگاه یا سوال باید وارد شوید یا ثبت نام کنید. &nbsp;&nbsp;
                <a href="{{ route('login') }}" class="font-bold">
                    ورود / ثبت نام
                </a>
            </div>
        @endguest

        @auth
            <div class="d-flex mb-8pt list">
                <h4 class="mr-auto"></h4>
                <button class="btn btn-yellow commentForm" data-parent-id="0"><span>افزودن دیدگاه جدید</span><i
                        class="fa fa-plus font-size-12pt ml-2"></i></button>
            </div>
        @endauth
    </div>
    <div class="card-body">

        <!-- start new comment form -->
        @auth
            <div id="new-comment-form" class="card shadow-none d-none">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="mr-16pt my-auto">
                            <a href="{{ route('profile-index', auth()->user()->username) }}"
                               class="avatar border-yellow border-2 rounded-circle">
                                <img src="{{ auth()->user()->profile_pic }}" alt="{{ auth()->user()->username.'@' }}"
                                     class="avatar avatar-img rounded-circle shadow">
                            </a>
                        </div>
                        <div class="my-auto">
                            <div class="font-size-14pt font-bold">
                                <a href="{{ route('profile-index', auth()->user()->username) }}"
                                   class="font-size-14pt font-bold">{{ auth()->user()->first_name.' '.auth()->user()->last_name }}</a>
                            </div>
                            <div class="text-muted font-size-12pt">
                                {{ auth()->user()->username.'@' }}
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="">
                        <form class="send-comment" action="{{ route('send-comment') }}" method="post">
                            @csrf
                            <div class="form-group mb-32pt">
                                <textarea name="comment" id="editor" class="form-control" rows="5"
                                          placeholder="کامنت یا سوال خود را اینجا وارد کنید..."></textarea>
                            </div>
                            <span class="invalid-feedback error-text comment_error" role="alert">
                                <strong></strong>
                            </span>
                            <div class="form-group d-flex">
                                <label class="my-auto mr-2 font-size-14pt font-bold cursor-pointer" for="preview-btn">پیش
                                    نمایش متن</label>
                                <div class="custom-control custom-checkbox-toggle custom-control-inline my-auto">
                                    <input type="checkbox" id="preview-btn" class="custom-control-input">
                                    <label class="custom-control-label cursor-pointer" for="preview-btn"></label>
                                </div>
                            </div>
                            <hr>
                            <input type="hidden" name="parent_id" value="">
                            <input type="hidden" name="commentable_id" value="{{ $model->id }}">
                            <input type="hidden" name="commentable_type" value="{{ get_class($model) }}">
                            <div class="d-flex float-right">
                                <button class="btn btn-light rounded-lg mr-2" type="button" onclick="hideCommentForm()">
                                    انصراف
                                </button>
                                <button class="btn btn-yellow rounded-lg" type="submit">ثبت دیدگاه</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endauth
        <!-- end new comment form -->


        <div class="comments" id="comments-body">
            @if($comments->count() > 0)
                @include('comment.comments-list', ['comments' => $comments])
            @else
                <div class="text-center w-100 p-3">
                    <h5 class="text-muted">تاکنون دیدگاهی ثبت نشده است.</h5>
                    <svg width="54" height="54" viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                              d="M27 0C49.2345 0 54 4.00008 54 24C54 37.0001 51.7506 45 41.6256 45C36.2147 45 34.6052 47.5703 33.1064 49.9638C31.8006 52.0491 30.5789 54.0001 27.0006 54C23.4225 53.9999 22.2007 52.049 20.8949 49.9638C19.3961 47.5703 17.7865 45 12.3756 45C2.25055 45 0 36.7442 0 24C0 4.23601 4.7655 0 27 0ZM49.5 24C49.5 13.878 48.1565 9.89799 45.8623 7.87579C44.6944 6.84628 42.8722 5.95738 39.7488 5.35647C36.6026 4.75117 32.4778 4.5 27 4.5C21.5316 4.5 17.4124 4.76523 14.2732 5.38537C11.1583 6.00072 9.32897 6.90538 8.1517 7.95184C5.83648 10.0098 4.5 14.0158 4.5 24C4.5 30.3241 5.09867 34.632 6.41592 37.2668C7.01586 38.4668 7.70279 39.1862 8.47207 39.6441C9.25959 40.1128 10.4585 40.5 12.3756 40.5C15.6095 40.5 18.2372 41.232 20.3756 42.6808C22.433 44.0748 23.6339 45.8794 24.4009 47.0862L24.7092 47.5722C25.3722 48.6189 25.5918 48.9656 25.9024 49.2444L25.9146 49.256C25.9799 49.3197 26.1644 49.5 27.0007 49.5C27.8371 49.5 28.0216 49.3197 28.0867 49.2561L28.0989 49.2445C28.4096 48.9657 28.6291 48.6191 29.2923 47.572L29.6003 47.0864C30.3672 45.8797 31.5681 44.0749 33.6255 42.6809C35.7638 41.232 38.3916 40.5 41.6256 40.5C43.5662 40.5 44.7756 40.119 45.564 39.6594C46.3265 39.2148 47.0012 38.5197 47.5916 37.3489C48.8981 34.7581 49.5 30.4669 49.5 24Z"
                              fill="#E0E3EA"></path>
                        <path
                            d="M31.5 15.75C30.2573 15.75 29.25 16.7573 29.25 18C29.25 19.2427 30.2573 20.25 31.5 20.25H38.25C39.4927 20.25 40.5 19.2427 40.5 18C40.5 16.7573 39.4927 15.75 38.25 15.75H31.5Z"
                            fill="#A2ACBF"></path>
                        <path
                            d="M15.75 24.75C14.5073 24.75 13.5 25.7573 13.5 27C13.5 28.2427 14.5073 29.25 15.75 29.25H38.25C39.4927 29.25 40.5 28.2427 40.5 27C40.5 25.7573 39.4927 24.75 38.25 24.75H15.75Z"
                            fill="#A2ACBF"></path>
                    </svg>
                </div>
            @endif
        </div>
    </div>
</div>
