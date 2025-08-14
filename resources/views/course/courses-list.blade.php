
<div class="row card-group-row">
    @foreach($courses as $course)
        <div class="col-sm-6 col-md-6 col-lg-4 card-group-row__col">
            @include('course.course-card', ['course' => $course])
        </div>
    @endforeach
</div>
<div class="mb-32pt d-flex flex-row">
    {{ $courses->links('vendor.pagination.custom') }}
</div>


