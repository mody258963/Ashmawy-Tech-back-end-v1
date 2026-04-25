@extends('layouts.admin')
@section('title', 'Edit salary')
@section('page-header')<h1 class="m-0">Edit salary</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.salaries.update', $salary) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                @include('admin.salaries.partials.form', ['salary' => $salary])
            </div>
            <div class="card-footer"><button class="btn btn-primary">Update</button></div>
        </form>
    </div>
@endsection
