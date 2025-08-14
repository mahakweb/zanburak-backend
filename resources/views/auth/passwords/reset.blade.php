@extends('auth.layouts.master')

@section('title', __('Reset password'))

@section('content')

    <div class="d-flex flex-column justify-content-center align-items-center mt-2 mb-5 navbar-light">
        <a href="" class="navbar-brand flex-column mb-2 align-items-center mr-0" style="min-width: 0">
        <span class=" navbar-brand-icon mr-0">
        <span class=" rounded"><img src="/assets/images/logo/logo-dark.png" alt="logo" class="img-fluid" /></span>
        </span>
        </a>
        <h5 class="m-0">{{ __('Reset password') }}</h5>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-group">
            <label class="text-label" for="email_2"> {{ __('Email') }}:</label>
            <div class="input-group input-group-merge">
                <input readonly id="email_2" type="email" class="form-control rounded-lg-left form-control-prepended @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email">
                @error('email')
                <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <div class="input-group-prepend">
                    <div class="input-group-text rounded-lg-right">
                        <i class="fa fa-envelope"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="text-label" for="password_2">{{ __('New password') }}:</label>
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
            <label class="text-label" for="password_2">{{ __('Confirm password') }}:</label>
            <div class="input-group input-group-merge">
                <input id="password-confirm" type="password" class="form-control rounded-lg-left form-control-prepended" name="password_confirmation" required>
                <div class="input-group-prepend">
                    <div class="input-group-text rounded-lg-right">
                        <i class="fa fa-lock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <button class="btn btn-block btn-yellow rounded-lg" type="submit">{{ __('Change') }}</button>
        </div>
    </form>

@endsection


