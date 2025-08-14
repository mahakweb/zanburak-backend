@extends('student.layouts.master')

@section('title', 'دوره‌های من')

@section('student-content')

    @include('student.layouts.user-data-header')
    <hr>
    <h5><span class="mr-2 text-secondary">&#x2022;</span>دوره‌های خریداری شده‌</h5>
    @if(!count($courses))
        <div class="text-center p-3">
            <h5 class="text-muted">چیزی برای نمایش وجود ندارد!</h5>
            <object data="/assets/images/other/svg/yellow/empty/empty.svg" width="300" height="300"> </object>
        </div>
    @else
        <div class="row card-group-row">
            @foreach($courses as $course)
                <div class="col-sm-6 col-md-4 col-lg-3 card-group-row__col">
                    @include('course.course-card', ['course' => $course])
                </div>
            @endforeach
        </div>
    @endif

@endsection
@section('script')
    <script src="/assets/js/manage-like.js"></script>
@endsection
