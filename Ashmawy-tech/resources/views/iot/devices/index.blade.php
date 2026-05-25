@extends('iot.layouts.app')

@section('title', __('Customer sites'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">{{ __('Customer sites') }}</h1>
        <a href="{{ route('iot.devices.create') }}" class="btn btn-primary btn-sm">{{ __('Add site') }}</a>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover bg-white">
            <thead>
            <tr>
                <th>ID</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Location') }}</th>
                <th>{{ __('MQTT user') }}</th>
                <th>{{ __('Switches') }}</th>
                <th>{{ __('Sensors') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($devices as $device)
                <tr>
                    <td>{{ $device->id }}</td>
                    <td><a href="{{ route('iot.devices.show', $device) }}">{{ $device->name }}</a></td>
                    <td>{{ $device->location ?: '—' }}</td>
                    <td><code>{{ $device->mqtt_username }}</code></td>
                    <td>{{ $device->components_count ?? 0 }}</td>
                    <td>{{ $device->sensor_slots_count ?? 0 }}</td>
                    <td><span class="badge badge-{{ $device->status === 'online' ? 'success' : 'secondary' }}">{{ $device->status }}</span></td>
                    <td class="text-nowrap">
                        <a href="{{ route('iot.devices.show', $device) }}" class="btn btn-outline-primary btn-sm">{{ __('Open') }}</a>
                        <a href="{{ route('iot.devices.edit', $device) }}" class="btn btn-outline-secondary btn-sm">{{ __('Edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">{{ __('No devices yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $devices->links() }}
@endsection
