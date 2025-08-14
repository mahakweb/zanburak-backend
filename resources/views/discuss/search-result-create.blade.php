<div class="card card-body bg-transparent mt-2 w-100 {{ $questions->count() == 0 ? 'd-none' : '' }}" style="max-height: 300px;overflow-y: auto">
    <div class="row">

        <h6 class="col-sm-12 text-muted">گفتگوهای مشابه:</h6>

        @foreach($questions as $question)
            <div class="col-sm-12">
                <div class="card card-sm mb-1 border-2 border-light" >
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center">
                            <div class="flex">
                                <div class="d-flex align-items-center">
                                    <div class="rounded mr-12pt z-0 o-hidden">
                                        <div class="overlay">
                                            {{--  <img src="/assets/images/other/question-mark-50.png" width="40" height="40" alt="question" class="rounded">  --}}
                                            <svg fill="#000000" width="40" height="30" viewBox="0 0 24 24" id="note-question" data-name="Flat Color" xmlns="http://www.w3.org/2000/svg" class="icon flat-color">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <rect id="primary" x="4" y="3" width="16" height="19" rx="2" style="fill: #fed700;"></rect>
                                                    <path id="secondary" d="M16,3V5a1,1,0,0,1-1,1H9A1,1,0,0,1,8,5V3A1,1,0,0,1,9,2h6A1,1,0,0,1,16,3ZM12,15.5A1.5,1.5,0,1,0,13.5,17,1.5,1.5,0,0,0,12,15.5ZM13,14V14A2.5,2.5,0,0,0,12.5,9H11a1,1,0,0,0,0,2h1.5a.5.5,0,0,1,0,1A1.5,1.5,0,0,0,11,13.5V14a1,1,0,0,0,2,0Z" style="fill: #000000;"></path>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex">
                                        <a href="{{ route('discuss-question', $question->slug) }}" class="flex text-black-50 lh-1 mb-0">{{ $question->subject }}</a>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('discuss-question', $question->slug) }}" class="ml-4pt btn btn-sm btn-outline-primary">
                                مشاهده
                                <svg class="ml-1" width="17" height="17" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="currentColor" opacity="0.4" d="M21.25 9.14993C18.94 5.51993 15.56 3.42993 12 3.42993C10.22 3.42993 8.49 3.94993 6.91 4.91993C5.33 5.89993 3.91 7.32993 2.75 9.14993C1.75 10.7199 1.75 13.2699 2.75 14.8399C5.06 18.4799 8.44 20.5599 12 20.5599C13.78 20.5599 15.51 20.0399 17.09 19.0699C18.67 18.0899 20.09 16.6599 21.25 14.8399C22.25 13.2799 22.25 10.7199 21.25 9.14993ZM12 16.0399C9.76 16.0399 7.96 14.2299 7.96 11.9999C7.96 9.76993 9.76 7.95993 12 7.95993C14.24 7.95993 16.04 9.76993 16.04 11.9999C16.04 14.2299 14.24 16.0399 12 16.0399Z"></path>
                                    <path fill="currentColor" d="M12.0004 9.13989C10.4304 9.13989 9.15039 10.4199 9.15039 11.9999C9.15039 13.5699 10.4304 14.8499 12.0004 14.8499C13.5704 14.8499 14.8604 13.5699 14.8604 11.9999C14.8604 10.4299 13.5704 9.13989 12.0004 9.13989Z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>
