<div class="modal fade" id="modal-send-report" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="d-flex flex-column py-4" style="max-width: 100%">
                <div class="mx-auto">
                    <svg width="70" height="70" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier"> <rect width="24" height="24" fill="white"></rect>
                            <path fill="red" fill-rule="evenodd" clip-rule="evenodd" d="M7.10711 2.87868C7.66972 2.31607 8.43278 2 9.22843 2H14.7716C15.5672 2 16.3303 2.31607 16.8929 2.87868L21.1213 7.10711C21.6839 7.66972 22 8.43278 22 9.22843V14.7716C22 15.5672 21.6839 16.3303 21.1213 16.8929L16.8929 21.1213C16.3303 21.6839 15.5672 22 14.7716 22H9.22843C8.43278 22 7.66972 21.6839 7.10711 21.1213L2.87868 16.8929C2.31607 16.3303 2 15.5672 2 14.7716V9.22843C2 8.43278 2.31607 7.66972 2.87868 7.10711L7.10711 2.87868ZM13 8C13 7.44772 12.5523 7 12 7C11.4477 7 11 7.44772 11 8V13C11 13.5523 11.4477 14 12 14C12.5523 14 13 13.5523 13 13V8ZM13 15.9888C13 15.4365 12.5523 14.9888 12 14.9888C11.4477 14.9888 11 15.4365 11 15.9888V16C11 16.5523 11.4477 17 12 17C12.5523 17 13 16.5523 13 16V15.9888Z"></path>
                        </g>
                    </svg>
                </div>
                @auth
                <form action="{{ route('send-report') }}" method="POST" class="send-report my-3 mx-auto">
                    @csrf
                    <input type="hidden" name="reportable_id" value="">
                    <input type="hidden" name="reportable_type" value="">
                    <p class="text-center font-size-16pt font-bold">
                        گزارش این مطلب به عنوان یک:
                    </p>
                    <div class="d-flex flex-column">
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="report-type1" type="radio" name="report" value="spam"  class="custom-control-input">
                            <label for="report-type1" class="custom-control-label">اسپم</label>
                        </div>
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="report-type2" type="radio" name="report" value="offensive-writing"  class="custom-control-input">
                            <label for="report-type2" class="custom-control-label">نوشته توهین آمیز</label>
                        </div>
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="report-type3" type="radio" name="report" value="violation-of-rules"  class="custom-control-input">
                            <label for="report-type3" class="custom-control-label">نقض قوانین</label>
                        </div>
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="report-type4" type="radio" name="report" value="other"  class="custom-control-input">
                            <label for="report-type4" class="custom-control-label">موارد دیگر</label>
                        </div>
                    </div>
                    <div class="form-group mt-4 d-flex">
                        <button class="btn btn-light rounded-lg mr-2 " data-dismiss="modal">انصراف</button>
                        <button class="btn btn-accent rounded-lg" type="submit">ارسال گزارش</button>
                    </div>
                </form>
                @endauth
                @guest
                    <div class="mt-16pt d-flex flex-column alert alert-soft-accent border-0 rounded-lg mx-auto">
                        <p class="font-size-14pt font-bold">
                            برای ثبت گزارش تخلف وارد شده یا ثبت نام کنید
                        </p>
                        <a class="font-size-14pt font-bold mx-auto" href="{{ route('login') }}">ورود | ثبت نام</a>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</div>
