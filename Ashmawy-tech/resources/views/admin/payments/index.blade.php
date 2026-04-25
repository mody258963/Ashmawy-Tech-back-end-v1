@extends('layouts.admin')
@section('title', __('messages.payments'))
@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">{{ __('messages.payments') }}</h1></div>
        <div class="col-sm-6 text-right"><a href="{{ route('admin.payments.create') }}" class="btn btn-primary">{{ __('messages.record_payment') }}</a></div>
    </div>
@endsection
@section('content')
    @include('admin.partials.summary-cards', ['cards' => $cards ?? []])
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>{{ __('messages.id') }}</th><th>{{ __('messages.order') }}</th><th>{{ __('messages.amount') }}</th><th>{{ __('messages.method') }}</th><th>{{ __('messages.paid_at') }}</th><th>{{ __('messages.by_user') }}</th><th></th></tr></thead>
                <tbody>
                @foreach ($payments as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>#{{ $p->order_id }}</td>
                        <td>{{ $p->amount }}</td>
                        <td>{{ $p->method }}</td>
                        <td>{{ $p->paid_at }}</td>
                        <td>{{ $p->receiver?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.payments.edit', $p) }}" class="btn btn-sm btn-default">{{ __('messages.edit') }}</a>
                            <form action="{{ route('admin.payments.destroy', $p) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('messages.delete') }}</button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $payments->links() }}</div>
    </div>
@endsection
