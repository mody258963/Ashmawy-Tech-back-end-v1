@extends('layouts.admin')

@section('title', 'New user')

@section('page-header')
    <h1 class="m-0">New user</h1>
@endsection

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.users.store') }}" method="post">
            @csrf
            <div class="card-body">
                @include('admin.users._form', ['user' => null, 'branches' => $branches, 'passwordRequired' => true])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
