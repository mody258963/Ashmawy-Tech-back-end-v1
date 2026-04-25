@extends('layouts.admin')
@section('title', __('messages.add_appointment'))
@section('page-header')<h1 class="m-0">{{ __('messages.add_appointment') }}</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.appointments.store') }}" method="post">
            @csrf
            <div class="card-body">
                @include('admin.appointments.partials.form', ['appointment' => null])
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-default">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection

