@extends('layouts.admin')
@section('title', __('messages.new_expense'))
@section('page-header')<h1 class="m-0">{{ __('messages.new_expense') }}</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.expenses.store') }}" method="post">@csrf
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('messages.branch') }}</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>{{ __('messages.title') }}</label><input name="title" class="form-control" required value="{{ old('title') }}"></div>
                <div class="form-group"><label>{{ __('messages.amount') }}</label><input name="amount" class="form-control" required value="{{ old('amount') }}"></div>
                <div class="form-group"><label>{{ __('messages.description') }}</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-default">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
