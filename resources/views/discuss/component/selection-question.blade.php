<form id="filter-type" action="{{ route('discuss-all') }}" method="GET">

    <div class="accordion js-accordion accordion--boxed mb-32pt" id="filter-question-parent">
        <div class="accordion__item bg-transparent shadow-none">
            <div class="accordion__toggle collapsed cursor-pointer" data-toggle="collapse" data-target="#filter-question" data-parent="#filter-question-parent">
                <span class="flex">
                    <h5 class="mb-1">گزینش سوالات</h5>
                </span>
                <span class="accordion__toggle-icon ">
                    <svg class="" width="18" height="18" viewBox="0 0 21 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" opacity="0.4" d="M12.4789 4.53947L15.8693 4.23962C16.6302 4.23962 17.2471 4.86253 17.2471 5.63081C17.2471 6.3991 16.6302 7.022 15.8693 7.022L12.4789 6.72216C11.882 6.72216 11.3981 6.23353 11.3981 5.63081C11.3981 5.02709 11.882 4.53947 12.4789 4.53947"></path>
                        <path fill="currentColor" d="M1.09392 4.5946C1.14691 4.5411 1.34488 4.31495 1.53085 4.12717C2.61567 2.95102 5.44819 1.02779 6.92994 0.439206C7.1549 0.345316 7.7238 0.145421 8.02875 0.131287C8.3197 0.131287 8.59765 0.198928 8.86261 0.332191C9.19355 0.518962 9.45751 0.813757 9.60348 1.16105C9.69647 1.40133 9.84244 2.12317 9.84244 2.1363C9.98742 2.92477 10.0664 4.20693 10.0664 5.62437C10.0664 6.97315 9.98742 8.20281 9.86844 9.00441C9.85544 9.01855 9.70947 9.91404 9.55049 10.2209C9.25954 10.7823 8.69064 11.1296 8.08174 11.1296H8.02875C7.63182 11.1164 6.79796 10.7681 6.79796 10.756C5.3952 10.1674 2.62966 8.33708 1.51785 7.12055C1.51785 7.12055 1.2039 6.80758 1.06793 6.61274C0.855964 6.33208 0.749982 5.98478 0.749982 5.63749C0.749982 5.24981 0.868961 4.8894 1.09392 4.5946"></path>
                    </svg>
                </span>
            </div>
            <div class="accordion__menu collapse show" id="filter-question">
                <div class="px-4">
                    <hr>
                    <div class="mb-4">
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="radioStacked1" type="radio" name="type" value="all" onChange="$(this).closest('form').submit()" {{ request()->get('type') == 'all' || !request()->get('type') ? 'checked' :''}} class="custom-control-input">
                            <label for="radioStacked1" class="custom-control-label">همه سوالات</label>
                        </div>
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="radioStacked2" type="radio" name="type" value="best-answer" onChange="$(this).closest('form').submit()" {{ request()->get('type') == 'best-answer' ? 'checked' :''}} class="custom-control-input">
                            <label for="radioStacked2" class="custom-control-label">حل شده</label>
                        </div>
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="radioStacked3" type="radio" name="type" value="no-best-answer" onChange="$(this).closest('form').submit()" {{ request()->get('type') == 'no-best-answer' ? 'checked' :''}} class="custom-control-input">
                            <label for="radioStacked3" class="custom-control-label">حل نشده</label>
                        </div>
                        @auth
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="radioStacked4" type="radio" name="type" value="my-question" onChange="$(this).closest('form').submit()" {{ request()->get('type') == 'my-question' ? 'checked' :''}} class="custom-control-input">
                            <label for="radioStacked4" class="custom-control-label">پرسش‌های من</label>
                        </div>
                        @endauth
                        <div class="custom-control custom-radio mb-12pt font-size-16pt">
                            <input id="radioStacked5" type="radio" name="type" value="no-answer" onChange="$(this).closest('form').submit()" {{ request()->get('type') == 'no-answer' ? 'checked' :''}} class="custom-control-input">
                            <label for="radioStacked5" class="custom-control-label">بدون پاسخ</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
