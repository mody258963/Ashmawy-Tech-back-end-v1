@extends('iot.layouts.app')

@section('title', __('Add customer site'))

@section('content')
    <h1 class="h3 mb-3">{{ __('Add customer site') }}</h1>
    <p class="text-muted">{{ __('Creates a new device row, MQTT username, and JWT for the ESP32.') }}</p>

    <form method="post" action="{{ route('iot.devices.store') }}" class="card card-body col-lg-6">
        @csrf
        <div class="form-group">
            <label for="name">{{ __('Site / customer name') }} *</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Warehouse Al-Mansour">
        </div>
        <div class="form-group">
            <label for="location">{{ __('Location') }}</label>
            <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" placeholder="City, address">
        </div>
        <div class="form-group">
            <label for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Create site') }}</button>
        <a href="{{ route('iot.devices.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
    </form>
@endsection
