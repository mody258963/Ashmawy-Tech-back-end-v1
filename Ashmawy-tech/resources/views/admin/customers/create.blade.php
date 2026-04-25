@extends('layouts.admin')

@section('title', __('messages.new_customer'))

@section('page-header')
    <h1 class="m-0">{{ __('messages.new_customer') }}</h1>
@endsection

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.customers.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('messages.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.phone') }}</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.branch') }}</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.status') }}</label>
                    <select name="status" class="form-control">
                        @foreach (['new','contacted','follow_up','converted','rejected'] as $s)
                            <option value="{{ $s }}" @selected(old('status', 'new') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.address') }}</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.address_link') }}</label>
                    <input type="url" name="address_link" class="form-control" value="{{ old('address_link') }}" placeholder="https://maps.google.com/...">
                </div>
                <div class="form-group">
                    <label>{{ __('messages.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.rejection_reason_if_rejected') }}</label>
                    <input type="text" name="rejection_reason" class="form-control" value="{{ old('rejection_reason') }}">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-default">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
