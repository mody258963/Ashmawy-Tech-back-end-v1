@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
        </div>
    </div>
@endsection

@section('content')
    <livewire:admin.dashboard />
@endsection
