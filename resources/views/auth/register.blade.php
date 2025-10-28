@extends('auth.layouts.master')


@section('title', __('Register'))

@section('content')

    <div class="d-flex flex-column justify-content-center align-items-center mt-2 mb-4 navbar-light">
        <a href="" class="navbar-brand flex-column mb-2 align-items-center mr-0" style="min-width: 0">
        <span class=" navbar-brand-icon mr-0">
        <span class=" rounded"><img src="/assets/images/logo/logo-wide.svg" alt="logo" class="img-fluid" /></span>
        </span>
        </a>
        {{--  <h6 class="m-0">{{ __('Register') }}</h6>  --}}
        <div class="form-group d-flex my-2">
            <a href="{{ route('register') }}" class="btn btn-yellow rounded-lg">
                <svg width="24" height="24" class="mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="0.4" d="M21.0901 21.5C21.0901 21.78 20.8701 22 20.5901 22H3.41016C3.13016 22 2.91016 21.78 2.91016 21.5C2.91016 17.36 6.99015 14 12.0002 14C13.0302 14 14.0302 14.14 14.9502 14.41C14.3602 15.11 14.0002 16.02 14.0002 17C14.0002 17.75 14.2101 18.46 14.5801 19.06C14.7801 19.4 15.0401 19.71 15.3401 19.97C16.0401 20.61 16.9702 21 18.0002 21C19.1202 21 20.1302 20.54 20.8502 19.8C21.0102 20.34 21.0901 20.91 21.0901 21.5Z" fill="currentColor"></path> <path d="M20.97 14.33C20.25 13.51 19.18 13 18 13C16.88 13 15.86 13.46 15.13 14.21C14.43 14.93 14 15.92 14 17C14 17.75 14.21 18.46 14.58 19.06C14.78 19.4 15.04 19.71 15.34 19.97C16.04 20.61 16.97 21 18 21C19.46 21 20.73 20.22 21.42 19.06C21.63 18.72 21.79 18.33 21.88 17.93C21.96 17.63 22 17.32 22 17C22 15.98 21.61 15.04 20.97 14.33ZM19.5 17.73H18.75V18.51C18.75 18.92 18.41 19.26 18 19.26C17.59 19.26 17.25 18.92 17.25 18.51V17.73H16.5C16.09 17.73 15.75 17.39 15.75 16.98C15.75 16.57 16.09 16.23 16.5 16.23H17.25V15.52C17.25 15.11 17.59 14.77 18 14.77C18.41 14.77 18.75 15.11 18.75 15.52V16.23H19.5C19.91 16.23 20.25 16.57 20.25 16.98C20.25 17.39 19.91 17.73 19.5 17.73Z" fill="currentColor"></path> <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="currentColor"></path> </g></svg>
                {{ __('Register') }}
            </a>
            <a href="{{ route('login') }}" class="ml-2 btn btn-light rounded-lg">
                <svg width="24" height="24" class="mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="0.4" d="M9 7.2V16.79C9 20 11 22 14.2 22H16.79C19.99 22 21.99 20 21.99 16.8V7.2C22 4 20 2 16.8 2H14.2C11 2 9 4 9 7.2Z" fill="currentColor"></path> <path d="M12.43 8.12002L15.78 11.47C16.07 11.76 16.07 12.24 15.78 12.53L12.43 15.88C12.14 16.17 11.66 16.17 11.37 15.88C11.08 15.59 11.08 15.11 11.37 14.82L13.44 12.75H2.75C2.34 12.75 2 12.41 2 12C2 11.59 2.34 11.25 2.75 11.25H13.44L11.37 9.18002C11.22 9.03002 11.15 8.84002 11.15 8.65002C11.15 8.46002 11.22 8.27002 11.37 8.12002C11.66 7.82002 12.13 7.82002 12.43 8.12002Z" fill="currentColor"></path> </g></svg>
                {{ __('Login') }}
            </a>
        </div>
    </div>
    <div class="text-center mb-24pt ">
        <div class="font-bold font-size-16pt">
           {{ __('Register in zanburak') }}
        </div>
        <div class="font-size-12pt font-bold text-50 mt-3">
          {{ __('Hello dear friend, you can register in the following ways') }}:
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 col-lg-6">
            <a href="{{ route('social-oauth', ['driver' => 'google']) }}" class="btn btn-white shadow btn-block rounded-lg mb-24pt px-1">
                <svg class="icon--left" width="24" height="24" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill="#4285F4" d="M14.9 8.161c0-.476-.039-.954-.121-1.422h-6.64v2.695h3.802a3.24 3.24 0 01-1.407 2.127v1.75h2.269c1.332-1.22 2.097-3.02 2.097-5.15z"></path><path fill="#34A853" d="M8.14 15c1.898 0 3.499-.62 4.665-1.69l-2.268-1.749c-.631.427-1.446.669-2.395.669-1.836 0-3.393-1.232-3.952-2.888H1.85v1.803A7.044 7.044 0 008.14 15z"></path><path fill="#FBBC04" d="M4.187 9.342a4.17 4.17 0 010-2.68V4.859H1.849a6.97 6.97 0 000 6.286l2.338-1.803z"></path><path fill="#EA4335" d="M8.14 3.77a3.837 3.837 0 012.7 1.05l2.01-1.999a6.786 6.786 0 00-4.71-1.82 7.042 7.042 0 00-6.29 3.858L4.186 6.66c.556-1.658 2.116-2.89 3.952-2.89z"></path></g></svg>
                {{ __('Register with Google') }}
            </a>
        </div>
        <div class="col-md-6 col-lg-6">
            <a href="{{ route('social-oauth', ['driver' => 'github']) }}" class="btn btn-white shadow btn-block rounded-lg mb-24pt px-1">
                <svg class="icon--left" width="24" height="24" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill="#161514" fill-rule="evenodd" d="M8 1C4.133 1 1 4.13 1 7.993c0 3.09 2.006 5.71 4.787 6.635.35.064.478-.152.478-.337 0-.166-.006-.606-.01-1.19-1.947.423-2.357-.937-2.357-.937-.319-.808-.778-1.023-.778-1.023-.635-.434.048-.425.048-.425.703.05 1.073.72 1.073.72.624 1.07 1.638.76 2.037.582.063-.452.244-.76.444-.935-1.554-.176-3.188-.776-3.188-3.456 0-.763.273-1.388.72-1.876-.072-.177-.312-.888.07-1.85 0 0 .586-.189 1.924.716A6.711 6.711 0 018 4.381c.595.003 1.194.08 1.753.236 1.336-.905 1.923-.717 1.923-.717.382.963.142 1.674.07 1.85.448.49.72 1.114.72 1.877 0 2.686-1.638 3.278-3.197 3.45.251.216.475.643.475 1.296 0 .934-.009 1.688-.009 1.918 0 .187.127.404.482.336A6.996 6.996 0 0015 7.993 6.997 6.997 0 008 1z" clip-rule="evenodd"></path></g></svg>
                {{ __('Register with Github') }}
            </a>
        </div>
    </div>
    <div class="page-separator justify-content-center">
        <div class="page-separator__text rounded-lg font-size-12pt text-70">
            {{ __('Or register from below') }}
        </div>
    </div>
    <form action="{{ route('register') }}" method="post" novalidate>
        @csrf

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="text-label" for="first_name">{{ __('First name') }}:</label>
                    <div class=input-group"">
                        <input id="first_name" type="text" class="form-control rounded-lg @error('first_name') is-invalid @enderror" name="first_name" placeholder="{{ __('e.g') }} : {{ __('Milad') }}" value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="text-label" for="last_name">{{ __('Last name') }}:</label>
                    <div class="input-group">
                        <input id="last_name" type="text" class="form-control rounded-lg @error('last_name') is-invalid @enderror" name="last_name" placeholder="{{ __('e.g') }} : {{ __('Mahaki') }}" value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="text-label" for="email_2">{{ __('Email') }}:</label>
                    <div class="input-group input-group-merge">
                        <input id="email_2" type="email" class="form-control rounded-lg-left form-control-prepended @error('email') is-invalid @enderror" name="email" placeholder="info@zanburak.ir" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <div class="input-group-prepend">
                            <div class="input-group-text rounded-lg-right">
                                <span><i class="fa fa-envelope"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="text-label" for="email_2">{{ __('Mobile') }}:</label>
                    <div class="input-group input-group-merge">
                        <input id="email_2" type="phone" maxlength="11" class="form-control rounded-lg-left form-control-prepended @error('mobile') is-invalid @enderror" name="mobile" placeholder="09123456789" value="{{ old('mobile') }}" required autocomplete="mobile">
                        @error('mobile')
                        <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                        @enderror
                        <div class="input-group-prepend">
                            <div class="input-group-text rounded-lg-right">
                                <span><i class="fa fa-mobile"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="text-label" for="password_2">{{ __('Password') }}:</label>
                    <div class="input-group input-group-merge">
                        <input id="password_2" type="password" class="form-control rounded-lg-left form-control-prepended @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                        @enderror
                        <div class="input-group-prepend">
                            <div class="input-group-text rounded-lg-right">
                                <span><i class="fa fa-lock"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="text-label" for="password_2">{{ __('Confirm password') }}:</label>
                    <div class="input-group input-group-merge">
                        <input id="password-confirm" type="password" class="form-control rounded-lg-left form-control-prepended" name="password_confirmation" required autocomplete="new-password">
                        <div class="input-group-prepend">
                            <div class="input-group-text rounded-lg-right">
                                <span><i class="fa fa-lock"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group mb-5">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" name="terms"  class="custom-control-input @error('terms') is-invalid @enderror" id="terms" required/>
                <label class="custom-control-label" for="terms">{{ __('Accept the terms and conditions') }}.</label>
                @error('terms.blade.php')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="form-group text-center">
            <button class="btn btn-block btn-yellow rounded-lg mb-2" type="submit">{{ __('Register') }}</button>
        </div>
    </form>

@endsection
