<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'IoT') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <style>
        .mqtt-token { font-size: 0.75rem; word-break: break-all; max-height: 6rem; overflow: auto; }
        .card-compact .card-body { padding: 0.75rem 1rem; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <a class="navbar-brand" href="{{ route('iot.dashboard') }}">Ashmawy IoT</a>
    @auth('iot-web')
        <div class="navbar-nav mr-auto">
            <a class="nav-link text-light" href="{{ route('iot.dashboard') }}">{{ __('Dashboard') }}</a>
            <a class="nav-link text-light" href="{{ route('iot.devices.index') }}">{{ __('Customer sites') }}</a>
            <a class="nav-link text-light" href="{{ route('iot.devices.create') }}">{{ __('Add site') }}</a>
        </div>
        <form action="{{ route('iot.logout') }}" method="post" class="form-inline">
            @csrf
            <button class="btn btn-outline-light btn-sm" type="submit">{{ __('Log out') }}</button>
        </form>
    @endauth
</nav>
<main class="container pb-5">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
