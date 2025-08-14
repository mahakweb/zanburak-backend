
@foreach ($questions as $question)
    @include('discuss.component.question-card', ['question' => $question])
@endforeach
<div class="mb-32pt d-flex flex-row">
    {{ $questions->links('vendor.pagination.custom') }}
</div>
