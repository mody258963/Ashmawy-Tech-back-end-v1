@extends('layouts.admin')
@section('title', 'Salaries')
@section('page-header')<h1 class="m-0">Salaries</h1>@endsection
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <form method="get" class="form-inline">
                <input type="month" name="month" class="form-control form-control-sm mr-2" value="{{ $month->format('Y-m') }}">
                <button class="btn btn-sm btn-primary">Apply</button>
            </form>
            <a href="{{ route('admin.salaries.create') }}" class="btn btn-sm btn-primary">Add salary</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Worker</th><th>Month</th><th>Base</th><th>Penalties</th><th>Net</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($salaries as $salary)
                    @php($penalty = (float) ($penaltiesByUser[$salary->user_id] ?? 0))
                    <tr>
                        <td>{{ $salary->user?->name }}</td>
                        <td>{{ $salary->for_month?->format('Y-m') }}</td>
                        <td>{{ number_format((float) $salary->base_amount, 2) }}</td>
                        <td>{{ number_format($penalty, 2) }}</td>
                        <td><strong>{{ number_format((float) $salary->base_amount - $penalty, 2) }}</strong></td>
                        <td>{{ $salary->status }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.salaries.edit', $salary) }}" class="btn btn-xs btn-default">Edit</a>
                            <form method="post" action="{{ route('admin.salaries.destroy', $salary) }}" class="d-inline" onsubmit="return confirm('Delete salary?');">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No salary records for this month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $salaries->links() }}</div>
    </div>
@endsection
