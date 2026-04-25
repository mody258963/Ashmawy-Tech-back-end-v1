@extends('layouts.admin')
@section('title', 'Spare parts')
@section('page-header')<h1 class="m-0">Spare parts &amp; inventory</h1>@endsection
@section('content')
    @include('admin.partials.summary-cards', ['cards' => $cards ?? []])
    <livewire:admin.spare-parts.index />
@endsection
