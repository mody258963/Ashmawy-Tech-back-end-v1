@extends('layouts.admin')
@section('title', __('messages.edit_expense'))
@section('page-header')<h1 class="m-0">{{ __('messages.edit_expense') }}</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.expenses.update', $expense) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('messages.branch') }}</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id', $expense->branch_id)==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>{{ __('messages.title') }}</label><input name="title" class="form-control" value="{{ old('title', $expense->title) }}" required></div>
                <div class="form-group"><label>{{ __('messages.amount') }}</label><input name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" required></div>
                <div class="form-group"><label>{{ __('messages.description') }}</label><textarea name="description" class="form-control" rows="3">{{ old('description', $expense->description) }}</textarea></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">{{ __('messages.update') }}</button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-default">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
