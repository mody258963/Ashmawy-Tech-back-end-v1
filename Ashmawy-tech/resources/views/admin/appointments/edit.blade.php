@extends('layouts.admin')
@section('title', __('messages.edit_appointment'))
@section('page-header')<h1 class="m-0">{{ __('messages.edit_appointment') }}</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.appointments.update', $appointment) }}" method="post">
            @csrf
            @method('PATCH')
            <div class="card-body">
                @include('admin.appointments.partials.form', ['appointment' => $appointment])
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">{{ __('messages.update') }}</button>
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-default">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection

