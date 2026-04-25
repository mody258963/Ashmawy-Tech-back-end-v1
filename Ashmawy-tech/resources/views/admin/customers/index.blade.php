@extends('layouts.admin')

@section('title', 'Customers')

@section('page-header')
    <h1 class="m-0">Customers</h1>
@endsection

@section('content')
    @include('admin.partials.summary-cards', ['cards' => $cards ?? []])
    <livewire:admin.customers.index />
@endsection
