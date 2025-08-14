<div class="mb-4">
    @foreach (\App\Models\User::leftJoin('scores','users.id','=','scores.user_id')
                        ->selectRaw('users.*, sum(scores.score) AS sum_score')
                        ->where('scores.created_at', '>', now()->subDays($numberOfDays)->endOfDay())
                        ->groupBy('users.id')
                        ->orderBy('sum_score','DESC')
                        ->take($numberOfUsers)
                        ->get() as $user)

        <div class="d-flex flex-row w-100 py-2 {{ !$loop->last ? 'border-bottom': '' }}">
            <div class="d-flex flex-row">
                <a href="{{ route('profile-index', $user->username) }}"
                    class="my-auto avatar rounded-circle mr-8pt border-3 {{ Cache::has('is_online' . $user->id) ? 'border-success' : 'border-light' }}">
                    <img src="{{ $user->profile_pic }}" alt="{{ $user->username }}"
                        class="avatar-img shadow rounded-circle">
                </a>
                <a href="{{ route('profile-index', $user->username) }}"
                    class="my-auto text-body">
                    <div class="d-flex flex-column">
                        <span class="font-bold">{{ $user->first_name . ' ' . $user->last_name }}</span>
                        <span class="text-70">{{ $user->username . '@' }}</span>
                    </div>
                </a>
            </div>
            <div class="ml-auto">
                <div class="d-flex flex-column card card-body bg-light m-0 p-1 border-0" style="width: 80px">
                    <div class="border-bottom">
                        <div class="text-70 text-center">تجربه</div>
                    </div>
                    <div class="d-flex flex-row mt-4pt justify-content-center">
                        <div class="text-70 font-size-12pt font-bold my-auto">{{ number_format($user->sum_score, 0, '.', ',') }}</div>
                        <svg class="ml-2" width="18" height="17" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle opacity="0.15" cx="14.2126" cy="15.8822" r="9.06581" transform="rotate(0.709692 14.2126 15.8822)" fill="#FFA826"></circle>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.78577 0.713257C7.48357 0.713257 7.258 0.877878 7.13 0.993884C6.9892 1.12149 6.86517 1.28299 6.75807 1.44444C6.54185 1.77041 6.32975 2.19628 6.13753 2.62986C5.75017 3.50364 5.40041 4.50831 5.2176 5.05677C5.21492 5.06482 5.20702 5.07114 5.19746 5.07147C4.6247 5.09083 3.57503 5.14219 2.66014 5.27637C2.20816 5.34265 1.75296 5.43405 1.39756 5.56639C1.22256 5.63155 1.03172 5.72094 0.874301 5.85062C0.714983 5.98186 0.525391 6.21188 0.525391 6.54358C0.525391 6.77299 0.612186 6.97607 0.690752 7.12099C0.774965 7.27632 0.88626 7.43027 1.0053 7.57563C1.24391 7.867 1.55941 8.1789 1.88561 8.47554C2.54112 9.07165 3.30023 9.6603 3.73213 9.98575C3.73894 9.99088 3.74211 9.99945 3.7393 10.0087C3.57343 10.5532 3.28536 11.5496 3.10211 12.4737C3.01109 12.9328 2.94081 13.3987 2.93025 13.7922C2.925 13.988 2.93362 14.1906 2.97276 14.3764C3.00922 14.5495 3.08933 14.7924 3.29435 14.9736C3.52445 15.177 3.80139 15.2039 3.99314 15.1921C4.18923 15.1799 4.38562 15.1232 4.56063 15.0571C4.91404 14.9237 5.31299 14.7005 5.69551 14.4596C6.46828 13.9727 7.28654 13.3408 7.75189 12.9693C7.76031 12.9625 7.77268 12.9624 7.78152 12.9694C8.24676 13.3413 9.0657 13.9736 9.84425 14.4607C10.2298 14.702 10.6329 14.9252 10.9924 15.0585C11.1709 15.1246 11.3693 15.1803 11.5672 15.1921C11.7614 15.2038 12.0307 15.1765 12.2605 14.9878C12.4758 14.8111 12.5652 14.5683 12.6067 14.3882C12.6505 14.1986 12.6606 13.9926 12.6558 13.7954C12.646 13.399 12.5721 12.9312 12.4763 12.4724C12.2833 11.5481 11.9781 10.5514 11.8014 10.0046C11.7983 9.9949 11.8016 9.98586 11.8087 9.98054C12.2428 9.65327 13.0002 9.06544 13.6535 8.4708C13.9786 8.17489 14.2929 7.86387 14.5305 7.57329C14.6491 7.4283 14.76 7.27473 14.8438 7.11973C14.9221 6.97505 15.0085 6.77245 15.0085 6.54358C15.0085 6.21223 14.8193 5.98233 14.6601 5.85104C14.5029 5.72136 14.3123 5.63197 14.1375 5.56681C13.7826 5.43449 13.328 5.34309 12.8766 5.27679C11.9629 5.14257 10.9141 5.09106 10.34 5.07159C10.3303 5.07126 10.3226 5.06496 10.32 5.05684C10.1413 4.50675 9.80029 3.50294 9.42076 2.63037C9.23245 2.19744 9.0241 1.77198 8.8106 1.44615C8.70486 1.28478 8.58193 1.12296 8.44163 0.994942C8.31372 0.878233 8.0884 0.713257 7.78577 0.713257Z" fill="#FFA826"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
