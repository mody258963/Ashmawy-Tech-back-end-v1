<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Ashmawy Tech') }} | Login</title>

    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <style>
        :root {
            --brand-primary: #d90429;
            --brand-dark: #2b2d42;
            --brand-soft: #edf2f4;
        }

        body.login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #2b2d42 0%, #3a3d5a 40%, #d90429 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        .login-logo a {
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        }

        .login-card {
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.25);
        }

        .login-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eceff3;
            padding: 1rem 1.25rem;
        }

        .login-card .card-body {
            padding: 1.35rem;
            background: #fff;
        }

        .login-subtitle {
            margin-top: 0.35rem;
            margin-bottom: 0;
            color: #6c757d;
            font-size: 0.92rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #495057;
        }

        .input-group {
            margin-bottom: 1rem;
        }

        .input-group .form-control {
            height: 46px;
            border-right: 0;
            font-size: 0.95rem;
            background: #fff;
            border-color: #d9dee4;
        }

        .input-group .input-group-text {
            width: 44px;
            justify-content: center;
            border-left: 0;
        }

        html[dir="rtl"] .input-group .form-control {
            border-right: 1px solid #dee2e6;
            border-left: 0;
        }

        html[dir="rtl"] .input-group .input-group-text {
            border-left: 1px solid #dee2e6;
            border-right: 0;
        }

        .input-group-text {
            color: var(--brand-dark);
            background: var(--brand-soft);
            border-color: #d9dee4;
        }

        .form-control:focus {
            border-color: rgba(217, 4, 41, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(217, 4, 41, 0.15);
        }

        .btn-primary {
            background: var(--brand-primary);
            border-color: var(--brand-primary);
            font-weight: 600;
            height: 42px;
            border-radius: 8px;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: #b90323;
            border-color: #b90323;
        }

        .login-page a {
            color: var(--brand-primary);
            font-weight: 500;
        }

        .remember-row {
            align-items: center;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="{{ url('/') }}"><b>Ashmawy</b>Tech</a>
    </div>

    <div class="card card-outline card-primary login-card">
        <div class="card-header text-center">
            <h1 class="h4 mb-0">{{ __('Sign in') }}</h1>
            <p class="login-subtitle">Welcome back, please login to continue.</p>
        </div>

        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label class="form-label" for="email">{{ __('Email') }}</label>
                <div class="input-group">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control"
                        placeholder="name@example.com"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                    </div>
                </div>

                <label class="form-label" for="password">{{ __('Password') }}</label>
                <div class="input-group">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="********"
                        required
                        autocomplete="current-password"
                    >
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>

                <div class="row remember-row">
                    <div class="col-7">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">{{ __('Remember me') }}</label>
                        </div>
                    </div>
                    <div class="col-5">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('Log in') }}</button>
                    </div>
                </div>
            </form>

            @if (Route::has('password.request'))
                <p class="mb-0 mt-3">
                    <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                </p>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
