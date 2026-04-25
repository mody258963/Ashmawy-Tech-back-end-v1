@extends('layouts.admin')

@section('title', 'New branch')

@section('page-header')
    <h1 class="m-0">New branch</h1>
@endsection

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.branches.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.branches.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
