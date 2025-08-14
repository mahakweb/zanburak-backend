@extends('student.layouts.master')

@section('title', 'دنبال شده‌ها')

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

        <div class="mx-auto">
            <div class="col-auto px-0">
                <div class="p-2">
                    <nav class="tabbable">
                        <div class="card-header-tabs-basic nav" role="tablist">

                            <a class="active align-items-center mb-0 h5" data-toggle="tab" href="#user-follow">کاربران</a>

                            <a class="align-items-center mb-0 h5" data-toggle="tab" href="#question-follow">پرسش‌ها</a>

                            <a class="align-items-center mb-0 h5" data-toggle="tab" href="#article-follow">مقالات</a>

                            <a class="align-items-center mb-0 h5" data-toggle="tab" href="#comment-follow">نظرات</a>

                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <div class="tab-content mt-3 text-70">
            <div class="tab-pane fade show active" id="user-follow">
                @if(!count($userFollowings))
                    <div class="text-center my-3 p-3">
                        <h5 class="text-muted">کاربری را دنبال نکرده‌اید!</h5>
                        <object data="/assets/images/other/svg/yellow/empty/void.svg" width="200" height="200"> </object>
                    </div>
                @else
                    <div class="row card-group-row my-3">

                        @foreach($userFollowings as $userFollowing)
                            @php
                                $user = \App\Models\User::whereId($userFollowing->followable_id)->first();
                            @endphp

                            <div class="col-sm-4 card-group-row__col">

                                <div class="card border-0 shadow-none card-group-row__card">

                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center">
                                            <div class="flex">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle mr-12pt z-0 o-hidden">
                                                        <div class="">
                                                            <a href="{{ route('profile-index', $user->username) }}" class="avatar avatar-lg {{ Cache::has('is_online' . $user->id) ? 'border-success' : 'border-light' }} border-3 rounded-circle">
                                                                <img src="{{ $user->profile_pic }}" alt="{{ $user->username }}" class="avatar-img rounded-circle shadow-lg">
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('profile-index', $user->username) }}" class="flex">
                                                        <h5 class="card-title">{{ $user->first_name.' '.$user->last_name }}</h5>
                                                        <p class="flex text-black-50 lh-1 mb-0"><small>{{ $user->username.' @ ' }}</small></p>
                                                    </a>
                                                </div>
                                            </div>

                                            <a data-toggle="tooltip" data-title="کاربر {{ Cache::has('is_online' . $user->id) ? 'آنلاین' : 'آفلاین' }} است" data-placement="top" data-boundary="window" class="ml-4pt {{ Cache::has('is_online' . $user->id) ? 'text-success' : 'text-accent' }}" data-original-title="" title=""><i class="material-icons">{{ Cache::has('is_online' . $user->id) ? 'wifi' : 'wifi_off' }}</i></a>

                                        </div>



                                        <p class="mt-16pt text-black-70 flex text-justify">{!! $user->info->about !!}</p>



                                        <div class="row align-items-center">
                                            <div class="col text-right">
                                                <form id="send-follow" action="{{ route('send-follow') }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="followable_id" value="{{ $user->id }}">
                                                    <button type="submit" class="btn btn-sm btn-light rounded shadow">
                                                        <span id="follow-check">
                                                            {{ auth()->user()->isFollowing($user) ? 'آنفالو کردن' : 'فالو کردن' }}
                                                        </span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>


                                    </div>
                                </div>


                            </div>

                        @endforeach

                    </div>
                @endif
            </div>
            <div class="tab-pane fade" id="question-follow">
                <div class="text-center my-3 p-3">
                    <h5 class="text-muted">چیزی برای نمایش وجود ندارد!</h5>
                    <object data="/assets/images/other/svg/yellow/empty/no_data.svg" width="200" height="200"> </object>
                </div>
            </div>
            <div class="tab-pane fade" id="article-follow">
                <div class="text-center my-3 p-3">
                    <h5 class="text-muted">چیزی برای نمایش وجود ندارد!</h5>
                    <object data="/assets/images/other/svg/yellow/empty/no_data.svg" width="200" height="200"> </object>
                </div>
            </div>
            <div class="tab-pane fade" id="comment-follow">
                <div class="text-center my-3 p-3">
                    <h5 class="text-muted">چیزی برای نمایش وجود ندارد!</h5>
                    <object data="/assets/images/other/svg/yellow/empty/no_data.svg" width="200" height="200"> </object>
                </div>
            </div>
        </div>
@endsection
@section('script')
    <script src="/assets/js/manage-follow.js"></script>
@endsection
