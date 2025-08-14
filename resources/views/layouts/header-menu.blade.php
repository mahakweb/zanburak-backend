<ul class="nav navbar-nav d-none d-sm-flex flex-row justify-content-center ml-8pt">
    <li class="nav-item mx-32pt {{ isActive('index', 'active') }}">
        <a href="/" class="nav-link">زنبورک</a>
    </li>
    <li class="nav-item mx-32pt {{ isActive('all-course', 'active') }}">
        <a href="{{ route('all-course') }}" class="nav-link">دوره‌های آموزشی</a>
    </li>
    <li class="nav-item mx-32pt {{ isActive('paths', 'active') }}">
        <a href="{{ route('paths') }}" class="nav-link">مسیر‌های یادگیری</a>
    </li>
    <li class="nav-item mx-32pt {{ isActive('discuss-all', 'active') }}">
        <a href="{{ route('discuss-all') }}" class="nav-link">پرسش و پاسخ</a>
    </li>
    <li class="nav-item dropdown mx-32pt">
        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-caret="false" aria-expanded="true">بیشتر</a>
        <div class="dropdown-menu rounded-lg p-1 shadow-lg">
            <div class="profile-menu d-flex flex-column font-size-16pt">
                <a href="{{ route('request-project') }}" class="w-100 p-2 rounded-lg">
                    <span>درخواست پروژه</span>
                </a>
                <a href="{{ route('vip') }}" class="w-100 p-2 rounded-lg">
                    <span>عضویت ویژه</span>
                </a>
                <a href="#" class="w-100 my-1 p-2 rounded-lg">
                    <span>سوالات متداول</span>
                </a>
                <a href="{{ route('about-us') }}" class="w-100 p-2 rounded-lg">
                    <span>درباره ما</span>
                </a>
                <a href="{{ route('contact-us') }}" class="w-100 p-2 rounded-lg">
                    <span>ارتباط با ما</span>
                </a>
                <a href="#" class="w-100 p-2 rounded-lg">
                    <span>همکاری با ما</span>
                </a>
            </div>
        </div>
    </li>
</ul>



