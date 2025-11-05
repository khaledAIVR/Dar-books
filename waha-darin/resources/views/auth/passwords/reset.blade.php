@extends('layouts.auth')
@php $authBg = asset('images/authBg.png'); @endphp

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <h1 class="card-header bg-white text-center pt-3 pb-4">@lang('auth.Reset Password')</h1>

                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="form-group row">
                            <label for="email"
                                   class="col-md-4 col-form-label text-md-right sr-only">@lang('auth.E-mail address')</label>
                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input id="email" type="email"
                                           class="form-control @error('email') is-invalid @enderror" name="email" value="{{request('email')}}"
                                           placeholder="@lang('auth.E-mail address')"
                                           value="{{ old('email') }}" required autocomplete="email" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text b-start-none" id="basic-addon2">
                                            <Icon name="email"
                                                  title="Menu"
                                                  size="normal"></Icon></span>
                                    </div>
                                </div>
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password"
                                   class="col-md-4 col-form-label text-md-right sr-only">@lang('auth.Password')</label>
                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input id="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror" name="password"
                                           placeholder="@lang('auth.Password')"
                                           required autocomplete="new-password" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text b-start-none" id="basic-addon2">
                                            <Icon name="password"
                                                  title="Menu"
                                                  size="normal"></Icon></span>
                                    </div>
                                </div>
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password-confirm"
                                   class="col-md-4 col-form-label text-md-right sr-only">@lang('auth.Password confirmation')</label>
                            <div class="col-12">
                                <div class="input-group mb-3">
                                    <input id="password-confirm" type="password"
                                           class="form-control" name="password_confirmation"
                                           placeholder="@lang('auth.Password confirmation')"
                                           required autocomplete="new-password" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text b-start-none" id="basic-addon2">
                                            <Icon name="password"
                                                  title="Menu"
                                                  size="normal"></Icon></span>
                                    </div>
                                </div>
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-12">
                                <button type="submit"
                                        class="btn btn-primary d-flex w-100 justify-content-center align-items-center btn-50">
                                    @lang('auth.Reset Password')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
