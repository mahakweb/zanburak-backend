@extends('auth.layouts.master')

@section('title', __('Verify mobile'))

@section('content')

    <div class="d-flex flex-column justify-content-center align-items-center mt-2 mb-5 navbar-light">
        <a href="" class="navbar-brand flex-column mb-2 align-items-center mr-0" style="min-width: 0">
        <span class=" navbar-brand-icon mr-0">
        <span class=" rounded"><img src="/assets/images/logo/logo-dark.png" alt="logo" class="img-fluid" /></span>
        </span>
        </a>
        <h6 class="m-0">{{ __('Verify your mobile number') }}</h6>
    </div>
    {{ __('You have not verified your mobile number') }}، &nbsp;
    {{ __('Click on the link below to confirm your mobile number') }}:
    </br>

    <div class="form-group">
        <a href="{{ route('student-profile').'#change-mobile' }}" class="btn btn-block btn-yellow rounded-lg">{{ __('Verify') }}</a>
    </div>


@endsection
