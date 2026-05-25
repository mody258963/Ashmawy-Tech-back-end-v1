@extends('iot.layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">{{ __('Installer dashboard') }}</h1>
        <a href="{{ route('iot.devices.create') }}" class="btn btn-primary btn-sm">{{ __('Add customer site') }}</a>
    </div>
    <p class="text-muted">{{ __('Each device is one store or warehouse where you installed a board.') }}</p>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-compact">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Total sites') }}</div>
                    <div class="h4 mb-0">{{ $devices->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-compact">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Online now') }}</div>
                    <div class="h4 mb-0 text-success">{{ $onlineCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5">{{ __('Customer sites') }}</h2>
    <div class="table-responsive">
        <table class="table table-sm table-hover bg-white">
            <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Location') }}</th>
                <th>{{ __('Switches') }}</th>
                <th>{{ __('Sensors') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($devices as $device)
                <tr>
                    <td>
                        <a href="{{ route('iot.devices.show', $device) }}">{{ $device->name }}</a>
                        <div class="small text-muted">#{{ $device->id }}</div>
                    </td>
                    <td>{{ $device->location ?: '—' }}</td>
                    <td>{{ $device->components_count ?? 0 }}</td>
                    <td>{{ $device->sensor_slots_count ?? 0 }}</td>
                    <td>
                        <span class="badge badge-{{ $device->status === 'online' ? 'success' : 'secondary' }}">{{ $device->status }}</span>
                    </td>
                    <td class="text-right">
                        <a href="{{ route('iot.devices.show', $device) }}" class="btn btn-outline-primary btn-sm">{{ __('Manage') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted">{{ __('No customer sites yet.') }}
                        <a href="{{ route('iot.devices.create') }}">{{ __('Add the first site') }}</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $devices->links() }}
@endsection
