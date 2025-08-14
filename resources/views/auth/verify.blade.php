@extends('auth.layouts.master')

@section('title', __('Verify email'))

@section('content')

    <div class="d-flex flex-column justify-content-center align-items-center mt-2 mb-5 navbar-light">
        <a href="" class="navbar-brand flex-column mb-2 align-items-center mr-0" style="min-width: 0">
        <span class=" navbar-brand-icon mr-0">
        <span class=" rounded"><img src="/assets/images/logo/logo-dark.png" alt="logo" class="img-fluid" /></span>
        </span>
        </a>
        <h6 class="m-0">{{ __('Verify your email address') }}</h6>
    </div>
    @if (session('resent'))
        <div class="alert alert-soft-success d-flex" role="alert">
            <i class="material-icons mr-12pt">check_circle</i>
            <div class="text-body">{{ __('Email confirmation link has been sent to you') }}</div>
        </div>
    @endif
    {{ __('Please check your email for a confirmation link before proceeding') }}، &nbsp;
    {{ __('If you did not receive the email') }}:
    </br>
    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <div class="form-group">
            <button class="btn btn-block btn-yellow rounded-lg" type="submit">{{ __('Resend') }}</button>
        </div>
    </form>

@endsection
