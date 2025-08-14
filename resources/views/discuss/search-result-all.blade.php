


@foreach($questions as $question)
    @include('discuss.component.question-card', ['question' => $question])
@endforeach

