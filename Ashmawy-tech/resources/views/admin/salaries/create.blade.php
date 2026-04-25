@extends('layouts.admin')
@section('title', 'New salary')
@section('page-header')<h1 class="m-0">New salary</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.salaries.store') }}" method="post">@csrf
            <div class="card-body">
                @include('admin.salaries.partials.form', ['salary' => null])
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
@endsection
