@extends('layouts.admin')
@section('title', 'Edit device')
@section('page-header')<h1 class="m-0">Edit device</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.devices.update', $device) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Customer</label>
                    <select name="customer_id" class="form-control" required>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $device->customer_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Type</label><input name="type" class="form-control" required value="{{ old('type', $device->type) }}"></div>
                <div class="form-group"><label>Brand</label><input name="brand" class="form-control" value="{{ old('brand', $device->brand) }}"></div>
                <div class="form-group"><label>Model</label><input name="model" class="form-control" value="{{ old('model', $device->model) }}"></div>
                <div class="form-group"><label>Serial</label><input name="serial_number" class="form-control" value="{{ old('serial_number', $device->serial_number) }}"></div>
                <div class="form-group"><label>Issue</label><textarea name="issue_description" class="form-control" rows="3">{{ old('issue_description', $device->issue_description) }}</textarea></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.devices.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
