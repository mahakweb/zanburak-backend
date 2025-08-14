@extends('auth.layouts.master')

@section('title', __('Confirm password'))

@section('content')

    <div class="d-flex flex-column justify-content-center align-items-center mt-2 mb-5 navbar-light">
        <a href="" class="navbar-brand flex-column mb-2 align-items-center mr-0" style="min-width: 0">
        <span class=" navbar-brand-icon mr-0">
        <span class=" rounded"><img src="/assets/images/logo/logo-dark.png" alt="logo" class="img-fluid" /></span>
        </span>
        </a>
        <h6 class="m-0">{{ __('Confirm password') }}</h6>
    </div>
    <div class="alert alert-soft-warning d-flex" role="alert">
        <i class="material-icons mr-12pt">error_outline</i>
        <div class="text-body">{{ __('Please confirm your password before continuing') }}.</div>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <div class="form-group">
        <label class="text-label" for="password_2">{{ __('Password') }}:</label>
        <div class="input-group input-group-merge">
            <input id="password_2" type="password" class="form-control rounded-lg-left form-control-prepended @error('password') is-invalid @enderror" name="password" required>
            @error('password')
            <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
            @enderror

            <div class="input-group-prepend">
                <div class="input-group-text rounded-lg-right">
                    <i class="fa fa-lock"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <button class="btn btn-block btn-yellow rounded-lg" type="submit">{{ __('Confirm password') }}</button>
        @if (Route::has('password.request'))
            <a class="btn btn-link" href="{{ route('password.request') }}">
                {{ __('Forgot your password') }}?
            </a>
        @endif
    </div>
    </form>

@endsection
