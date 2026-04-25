@extends('layouts.admin')
@section('title', __('messages.expenses'))
@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">{{ __('messages.expenses') }}</h1></div>
        <div class="col-sm-6 text-right"><a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">{{ __('messages.add_expense') }}</a></div>
    </div>
@endsection
@section('content')
    @include('admin.partials.summary-cards', ['cards' => $cards ?? []])
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>{{ __('messages.title') }}</th><th>{{ __('messages.branch') }}</th><th>{{ __('messages.amount') }}</th><th>{{ __('messages.by') }}</th><th></th></tr></thead>
                <tbody>
                @foreach ($expenses as $e)
                    <tr>
                        <td>{{ $e->title }}</td>
                        <td>{{ $e->branch?->name }}</td>
                        <td>{{ $e->amount }}</td>
                        <td>{{ $e->creator?->name }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.expenses.edit', $e) }}" class="btn btn-sm btn-default">{{ __('messages.edit') }}</a>
                            <form action="{{ route('admin.expenses.destroy', $e) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('messages.delete') }}</button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $expenses->links() }}</div>
    </div>
@endsection
