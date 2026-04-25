@extends('layouts.admin')
@section('title', 'New fleet vehicle')
@section('page-header')<h1 class="m-0">New fleet vehicle</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.fleet-vehicles.store') }}" method="post">@csrf
            <div class="card-body">
                @include('admin.fleet-vehicles.partials.form', ['vehicle' => null])
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
@endsection
