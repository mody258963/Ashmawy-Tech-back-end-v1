@extends('layouts.admin')
@section('title', 'Edit fleet vehicle')
@section('page-header')<h1 class="m-0">Edit fleet vehicle</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.fleet-vehicles.update', $vehicle) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                @include('admin.fleet-vehicles.partials.form', ['vehicle' => $vehicle])
            </div>
            <div class="card-footer"><button class="btn btn-primary">Update</button></div>
        </form>
    </div>
@endsection
