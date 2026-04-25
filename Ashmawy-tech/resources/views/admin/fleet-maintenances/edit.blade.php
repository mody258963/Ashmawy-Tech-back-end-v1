@extends('layouts.admin')
@section('title', 'Edit maintenance')
@section('page-header')<h1 class="m-0">Edit maintenance</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.fleet-maintenances.update', $item) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                @include('admin.fleet-maintenances.partials.form', ['item' => $item])
            </div>
            <div class="card-footer"><button class="btn btn-primary">Update</button></div>
        </form>
    </div>
@endsection
