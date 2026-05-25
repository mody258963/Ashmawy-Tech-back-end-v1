@extends('iot.layouts.app')

@section('title', __('Edit') . ' — ' . $device->name)

@section('content')
    <h1 class="h3 mb-3">{{ __('Edit customer site') }}</h1>

    <form method="post" action="{{ route('iot.devices.update', $device) }}" class="card card-body col-lg-6">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">{{ __('Site / customer name') }} *</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $device->name) }}" required>
        </div>
        <div class="form-group">
            <label for="location">{{ __('Location') }}</label>
            <input type="text" name="location" id="location" class="form-control" value="{{ old('location', $device->location) }}">
        </div>
        <div class="form-group">
            <label for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $device->notes) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <a href="{{ route('iot.devices.show', $device) }}" class="btn btn-link">{{ __('Cancel') }}</a>
    </form>
@endsection
