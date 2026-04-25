@extends('layouts.admin')
@section('title', __('messages.devices'))
@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">{{ __('messages.devices') }}</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.devices.create') }}" class="btn btn-primary">{{ __('messages.add_device') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <form method="get" class="form-inline">
                <input type="text" class="form-control form-control-sm mr-2" name="q" value="{{ $search ?? '' }}" placeholder="{{ __('messages.search_devices_placeholder') }}">
                <button class="btn btn-sm btn-primary">{{ __('messages.search') }}</button>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>{{ __('messages.id') }}</th><th>{{ __('messages.customer') }}</th><th>{{ __('messages.type') }}</th><th>{{ __('messages.brand') }}</th><th>{{ __('messages.model') }}</th><th></th></tr></thead>
                <tbody>
                @foreach ($devices as $device)
                    <tr>
                        <td>{{ $device->id }}</td>
                        <td>{{ $device->customer?->name }}</td>
                        <td>{{ $device->type }}</td>
                        <td>{{ $device->brand }}</td>
                        <td>{{ $device->model }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.devices.edit', $device) }}" class="btn btn-sm btn-default">{{ __('messages.edit') }}</a>
                            <form action="{{ route('admin.devices.destroy', $device) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('messages.delete') }}</button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $devices->links() }}</div>
    </div>
@endsection
