@extends('layouts.admin')
@section('title', __('messages.edit_payment'))
@section('page-header')<h1 class="m-0">{{ __('messages.edit_payment') }}</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.payments.update', $payment) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('messages.order') }}</label>
                    <select name="order_id" class="form-control" required>
                        @foreach ($orders as $o)
                            <option value="{{ $o->id }}" @selected(old('order_id', $payment->order_id)==$o->id)>{{ $o->order_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>{{ __('messages.amount') }}</label><input name="amount" type="text" class="form-control" value="{{ old('amount', $payment->amount) }}" required></div>
                <div class="form-group">
                    <label>{{ __('messages.method') }}</label>
                    <select name="method" class="form-control">
                        @foreach (['cash','transfer','card'] as $m)
                            <option value="{{ $m }}" @selected(old('method', $payment->method)===$m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>{{ __('messages.paid_at') }}</label><input type="datetime-local" name="paid_at" class="form-control" value="{{ old('paid_at', \Illuminate\Support\Carbon::parse($payment->paid_at)->format('Y-m-d\TH:i')) }}" required></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">{{ __('messages.update') }}</button>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-default">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
