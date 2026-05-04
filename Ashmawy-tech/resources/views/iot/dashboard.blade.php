@extends('iot.layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <h1 class="h3 mb-3">{{ __('Your devices') }}</h1>
    <div class="list-group">
        @forelse ($devices as $device)
            <a href="{{ route('iot.devices.show', $device) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span>{{ $device->name }}</span>
                <span class="badge badge-{{ $device->status === 'online' ? 'success' : 'secondary' }}">{{ $device->status }}</span>
            </a>
        @empty
            <div class="list-group-item text-muted">{{ __('No devices yet.') }}</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $devices->links() }}</div>
@endsection
