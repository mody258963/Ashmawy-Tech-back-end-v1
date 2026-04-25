@extends('layouts.admin')

@section('title', 'Edit user')

@section('page-header')
    <h1 class="m-0">Edit user</h1>
@endsection

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.users.update', $user) }}" method="post">
            @csrf @method('PUT')
            <div class="card-body">
                @include('admin.users._form', ['user' => $user, 'branches' => $branches, 'passwordRequired' => false])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
