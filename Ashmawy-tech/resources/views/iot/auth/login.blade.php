@extends('iot.layouts.app')

@section('title', __('IoT login'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header">{{ __('IoT dashboard login') }}</div>
                <div class="card-body">
                    <form method="post" action="{{ route('iot.login') }}">
                        @csrf
                        <div class="form-group">
                            <label for="email">{{ __('Email') }}</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="password">{{ __('Password') }}</label>
                            <input id="password" type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1">
                            <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">{{ __('Sign in') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
