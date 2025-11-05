@extends('layouts.auth')
@php $authBg = asset('images/authBg.png'); @endphp

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if (session('resent'))
                    <div class="alert alert-success" role="alert">
                        @lang('auth.A fresh verification link has been sent to your email address.')
                    </div>
                @endif

                <div class="card rounded-more border-primary p-5">
                    <h1 class="card-header bg-white text-center pt-3 border-0">@lang('auth.Verify Your Email Address')</h1>
                    <div class="card-body p-5 d-flex flex-column text-center justify-content-between align-items-center">
                        <h4 class="font-weight-light mb-4">@lang('auth.Before proceeding, please check your email for a verification link.')</h4>
                        <h4 class="font-weight-lighter text-dark-light mb-3"> @lang('auth.If you did not receive the email')</h4>

                        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-primary-light btn-lg p-0 m-0 align-baseline">@lang('auth.click here to request another')</button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection




