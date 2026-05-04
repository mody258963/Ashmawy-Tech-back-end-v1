@extends('iot.layouts.app')

@section('title', $device->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">{{ $device->name }}</h1>
        <a href="{{ route('iot.dashboard') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back') }}</a>
    </div>
    <p class="text-muted">{{ __('UUID') }}: <code>{{ $device->device_uuid }}</code> · {{ __('MQTT user') }}: <code>{{ $device->mqtt_username }}</code></p>
    <p>
        <span class="badge badge-{{ $device->status === 'online' ? 'success' : 'secondary' }}">{{ $device->status }}</span>
        @if($device->last_seen)
            <small class="text-muted ml-2">{{ __('Last seen') }}: {{ $device->last_seen }}</small>
        @endif
    </p>

    <form action="{{ route('iot.devices.jwt.regenerate', $device) }}" method="post" class="mb-4">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm">{{ __('Regenerate device MQTT JWT') }}</button>
    </form>

    <h2 class="h5">{{ __('Components') }}</h2>
    <div class="row">
        @foreach ($components as $comp)
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h6">{{ $comp->name }} <span class="badge badge-light">ch {{ $comp->channel }}</span></h3>
                        <p class="small text-muted mb-2">{{ $comp->type }}</p>
                        <div class="btn-group btn-group-sm">
                            @foreach (['ON','OFF','TOGGLE'] as $act)
                                <form action="{{ route('iot.devices.components.action', [$device, $comp]) }}" method="post" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="{{ $act }}">
                                    <button type="submit" class="btn btn-outline-primary">{{ $act }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h2 class="h5 mt-4">{{ __('Latest sensor readings') }}</h2>
    <ul class="list-group">
        @forelse ($latestSensors as $row)
            <li class="list-group-item d-flex justify-content-between">
                <span>{{ $row->type }}</span>
                <code>{{ json_encode($row->value) }}</code>
                <small class="text-muted">{{ $row->recorded_at }}</small>
            </li>
        @empty
            <li class="list-group-item text-muted">{{ __('No sensor data yet.') }}</li>
        @endforelse
    </ul>
@endsection
