@extends('layouts.admin')
@section('title', 'New maintenance')
@section('page-header')<h1 class="m-0">New maintenance</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.fleet-maintenances.store') }}" method="post">@csrf
            <div class="card-body">
                @include('admin.fleet-maintenances.partials.form', ['item' => null])
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
@endsection
