@extends('layouts.admin')

@section('title', 'Edit branch')

@section('page-header')
    <h1 class="m-0">Edit branch</h1>
@endsection

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.branches.update', $branch) }}" method="post">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $branch->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $branch->phone) }}">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $branch->address) }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.branches.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
