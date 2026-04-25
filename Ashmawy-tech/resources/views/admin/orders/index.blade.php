@extends('layouts.admin')
@section('title', 'Orders')
@section('page-header')<h1 class="m-0">Orders</h1>@endsection
@section('content')
    @include('admin.partials.summary-cards', ['cards' => $cards ?? []])
    <livewire:admin.orders.index />
@endsection
