<div class="mx-auto d-none d-lg-block">
    @if ($paginator->hasPages())
        <ul class="pagination justify-content-start pagination-xsm ">

            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="{{ $paginator->onFirstPage() ? 'disabled' : '' }} border-0 btn btn-link text-decoration-none py-1" href="{{ !$paginator->onFirstPage() ? $paginator->previousPageUrl() : '#' }}" aria-label="{{ !$paginator->onFirstPage() ? 'Previous' : '' }}"  rel="{{ !$paginator->onFirstPage() ? 'prev' : '' }}">
                    <svg class="mr-1" width="20" height="20" viewBox="0 0 24 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M6.64577 6.43275L2.05275 6.02655C1.02199 6.02655 0.186279 6.8704 0.186279 7.9112C0.186279 8.952 1.02199 9.79585 2.05275 9.79585L6.64577 9.38965C7.45439 9.38965 8.10996 8.7277 8.10996 7.9112C8.10996 7.09333 7.45439 6.43275 6.64577 6.43275" fill="currentColor"></path>
                        <path d="M22.0696 6.50741C21.9978 6.43492 21.7296 6.12856 21.4777 5.87418C20.0081 4.28084 16.1709 1.67543 14.1635 0.878077C13.8588 0.750884 13.0881 0.480085 12.675 0.460937C12.2808 0.460937 11.9043 0.552571 11.5453 0.733104C11.097 0.986123 10.7394 1.38548 10.5417 1.85596C10.4157 2.18147 10.218 3.15935 10.218 3.17713C10.0216 4.24528 9.91455 5.98222 9.91455 7.90243C9.91455 9.72964 10.0216 11.3955 10.1827 12.4814C10.2003 12.5005 10.3981 13.7137 10.6135 14.1294C11.0076 14.8899 11.7783 15.3603 12.6032 15.3603H12.675C13.2127 15.3426 14.3423 14.8707 14.3423 14.8543C16.2427 14.057 19.9891 11.5774 21.4953 9.92932C21.4953 9.92932 21.9206 9.50534 22.1048 9.24138C22.392 8.86117 22.5355 8.39069 22.5355 7.92021C22.5355 7.39503 22.3744 6.90677 22.0696 6.50741" fill="currentColor"></path>
                    </svg>
                    {{--  <span>قبلی</span>  --}}
                </a>
            </li>


            @foreach ($elements as $element)

                @if (is_string($element))
                        <li class="page-item mx-1 bg-transparent disabled btn btn-sm btn-link text-decoration-none border border-active-light rounded-lg">
                            <a class="border-0 px-1">
                                <span class="font-size-12pt font-bold">{{ $element }}</span>
                            </a>
                        </li>
                @endif



                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                        <a class="border-0 mx-1">
                            <li class="page-item px-12pt bg-yellow text-dark btn btn-sm btn-link text-decoration-none border border-active-light rounded-lg">
                                <span class="font-size-12pt font-bold">{{ $page }}</span>
                            </li>
                        </a>
                        @else
                        <a class="border-0 mx-1" href="{{ $url }}">
                            <li class="page-item px-12pt bg-transparent btn btn-sm btn-link text-decoration-none border border-active-light rounded-lg">
                                <span class="font-size-12pt font-bold">{{ $page }}</span>
                            </li>
                        </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                <a class="{{ !$paginator->hasMorePages() ? 'disabled' : '' }} border-0 btn btn-link text-decoration-none py-1" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" rel="{{ $paginator->hasMorePages() ? 'Next' : '' }}" aria-label="{{ $paginator->hasMorePages() ? 'Next' : '' }}">
                    {{--  <span>بعدی</span>  --}}
                    <svg class="ml-1" width="20" height="20" viewBox="0 0 21 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" opacity="0.4" d="M14.9442 6.2784L19.146 5.9068C20.089 5.9068 20.8535 6.67878 20.8535 7.63094C20.8535 8.5831 20.089 9.35508 19.146 9.35508L14.9442 8.98348C14.2044 8.98348 13.6047 8.3779 13.6047 7.63094C13.6047 6.88273 14.2044 6.2784 14.9442 6.2784"></path>
                        <path fill="currentColor" d="M0.834251 6.3467C0.899925 6.28039 1.14527 6.00012 1.37575 5.7674C2.72019 4.30976 6.23061 1.92624 8.06699 1.1968C8.34579 1.08044 9.05085 0.832702 9.42878 0.815186C9.78936 0.815186 10.1338 0.899015 10.4622 1.06417C10.8724 1.29564 11.1995 1.66099 11.3804 2.0914C11.4956 2.38918 11.6765 3.28378 11.6765 3.30005C11.8562 4.27723 11.9541 5.86624 11.9541 7.62291C11.9541 9.2945 11.8562 10.8185 11.7088 11.8119C11.6926 11.8294 11.5117 12.9392 11.3147 13.3196C10.9541 14.0152 10.2491 14.4457 9.49445 14.4457H9.42878C8.93685 14.4294 7.90342 13.9977 7.90342 13.9827C6.16494 13.2533 2.73754 10.9849 1.35964 9.47718C1.35964 9.47718 0.970554 9.08931 0.802034 8.84783C0.539341 8.5 0.407995 8.06959 0.407995 7.63918C0.407995 7.15872 0.55545 6.71205 0.834251 6.3467"></path>
                    </svg>
                </a>
            </li>
        </ul>
    @endif
</div>

<div class="pagination mx-auto d-lg-none">
    @if ($paginator->hasPages())
    <div class="d-flex">
        <a class="btn {{ $paginator->onFirstPage() ? 'disabled btn-light' : 'btn-yellow' }} rounded-lg {{ $paginator->onFirstPage() ? 'disabled' : '' }}" href="{{ !$paginator->onFirstPage() ? $paginator->previousPageUrl() : '#' }}" aria-label="{{ !$paginator->onFirstPage() ? 'Previous' : '' }}"  rel="{{ !$paginator->onFirstPage() ? 'prev' : '' }}">
            <svg class="mr-1" width="20" height="20" viewBox="0 0 24 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path opacity="0.4" d="M6.64577 6.43275L2.05275 6.02655C1.02199 6.02655 0.186279 6.8704 0.186279 7.9112C0.186279 8.952 1.02199 9.79585 2.05275 9.79585L6.64577 9.38965C7.45439 9.38965 8.10996 8.7277 8.10996 7.9112C8.10996 7.09333 7.45439 6.43275 6.64577 6.43275" fill="currentColor"></path>
                <path d="M22.0696 6.50741C21.9978 6.43492 21.7296 6.12856 21.4777 5.87418C20.0081 4.28084 16.1709 1.67543 14.1635 0.878077C13.8588 0.750884 13.0881 0.480085 12.675 0.460937C12.2808 0.460937 11.9043 0.552571 11.5453 0.733104C11.097 0.986123 10.7394 1.38548 10.5417 1.85596C10.4157 2.18147 10.218 3.15935 10.218 3.17713C10.0216 4.24528 9.91455 5.98222 9.91455 7.90243C9.91455 9.72964 10.0216 11.3955 10.1827 12.4814C10.2003 12.5005 10.3981 13.7137 10.6135 14.1294C11.0076 14.8899 11.7783 15.3603 12.6032 15.3603H12.675C13.2127 15.3426 14.3423 14.8707 14.3423 14.8543C16.2427 14.057 19.9891 11.5774 21.4953 9.92932C21.4953 9.92932 21.9206 9.50534 22.1048 9.24138C22.392 8.86117 22.5355 8.39069 22.5355 7.92021C22.5355 7.39503 22.3744 6.90677 22.0696 6.50741" fill="currentColor"></path>
            </svg>
            <span>صفحه قبل</span>
        </a>

        <a class="ml-3 btn {{ !$paginator->hasMorePages() ? 'disabled btn-light' : 'btn-yellow' }} rounded-lg {{ !$paginator->hasMorePages() ? 'disabled' : '' }}" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}" rel="{{ $paginator->hasMorePages() ? 'Next' : '' }}" aria-label="{{ $paginator->hasMorePages() ? 'Next' : '' }}">
            <span>صفحه بعد</span>
            <svg class="ml-1" width="20" height="20" viewBox="0 0 21 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="currentColor" opacity="0.4" d="M14.9442 6.2784L19.146 5.9068C20.089 5.9068 20.8535 6.67878 20.8535 7.63094C20.8535 8.5831 20.089 9.35508 19.146 9.35508L14.9442 8.98348C14.2044 8.98348 13.6047 8.3779 13.6047 7.63094C13.6047 6.88273 14.2044 6.2784 14.9442 6.2784"></path>
                <path fill="currentColor" d="M0.834251 6.3467C0.899925 6.28039 1.14527 6.00012 1.37575 5.7674C2.72019 4.30976 6.23061 1.92624 8.06699 1.1968C8.34579 1.08044 9.05085 0.832702 9.42878 0.815186C9.78936 0.815186 10.1338 0.899015 10.4622 1.06417C10.8724 1.29564 11.1995 1.66099 11.3804 2.0914C11.4956 2.38918 11.6765 3.28378 11.6765 3.30005C11.8562 4.27723 11.9541 5.86624 11.9541 7.62291C11.9541 9.2945 11.8562 10.8185 11.7088 11.8119C11.6926 11.8294 11.5117 12.9392 11.3147 13.3196C10.9541 14.0152 10.2491 14.4457 9.49445 14.4457H9.42878C8.93685 14.4294 7.90342 13.9977 7.90342 13.9827C6.16494 13.2533 2.73754 10.9849 1.35964 9.47718C1.35964 9.47718 0.970554 9.08931 0.802034 8.84783C0.539341 8.5 0.407995 8.06959 0.407995 7.63918C0.407995 7.15872 0.55545 6.71205 0.834251 6.3467"></path>
            </svg>
        </a>
    </div>
    @endif
</div>

