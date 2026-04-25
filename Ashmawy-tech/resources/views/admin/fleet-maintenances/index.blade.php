@extends('layouts.admin')
@section('title', 'Fleet maintenance')
@section('page-header')<h1 class="m-0">Fleet maintenance</h1>@endsection
@section('content')
    <div class="card">
        <div class="card-header text-right">
            <a href="{{ route('admin.fleet-maintenances.create') }}" class="btn btn-primary btn-sm">Add maintenance</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Date</th><th>Vehicle</th><th>Service</th><th>Cost</th><th>Next service</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->service_date?->format('Y-m-d') }}</td>
                        <td>{{ $item->vehicle?->name }}</td>
                        <td>{{ $item->service_type }}</td>
                        <td>{{ number_format((float) $item->cost, 2) }}</td>
                        <td>{{ $item->next_service_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.fleet-maintenances.edit', $item) }}" class="btn btn-xs btn-default">Edit</a>
                            <form method="post" action="{{ route('admin.fleet-maintenances.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete maintenance?');">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No maintenance records.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@endsection
