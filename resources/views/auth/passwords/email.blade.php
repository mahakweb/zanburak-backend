@extends('auth.layouts.master')

@section('title', __('Reset password'))

@section('content')

    <div class="d-flex flex-column justify-content-center align-items-center mt-2 mb-5 navbar-light">
        <a href="" class="navbar-brand flex-column mb-2 align-items-center mr-0" style="min-width: 0">
            <span class=" navbar-brand-icon mr-0">
                <span class=" rounded"><img src="/assets/images/logo/logo-wide.svg" alt="logo" class="img-fluid" /></span>
            </span>
        </a>
        <h5 class="m-0">{{ __('Reset password') }}</h5>
    </div>
    @if (session('status'))
        <div class="alert alert-soft-success d-flex" role="alert">
            <i class="material-icons mr-12pt">check_circle</i>
            <div class="text-body">{{ __('Password reset link has been sent to you') }}!</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label class="text-label" for="email_2">{{ __('Email') }}:</label>
            <div class="input-group input-group-merge">
                <input id="email_2" type="email" class="form-control rounded-lg-left form-control-prepended @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="info@zanburak.ir" required autocomplete="email">
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
            <button class="btn btn-block btn-yellow rounded-lg" type="submit">{{ __('Send link') }}</button>
        </div>
    </form>

@endsection












