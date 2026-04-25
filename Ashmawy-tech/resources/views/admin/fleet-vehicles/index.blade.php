@extends('layouts.admin')
@section('title', 'Fleet vehicles')
@section('page-header')<h1 class="m-0">Fleet vehicles</h1>@endsection
@section('content')
    <div class="card">
        <div class="card-header text-right">
            <a href="{{ route('admin.fleet-vehicles.create') }}" class="btn btn-primary btn-sm">Add vehicle</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Name</th><th>Type</th><th>Plate</th><th>Odometer</th><th>Branch</th><th></th></tr></thead>
                <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td>{{ $vehicle->name }}</td>
                        <td>{{ $vehicle->type }}</td>
                        <td>{{ $vehicle->plate_number }}</td>
                        <td>{{ $vehicle->odometer }}</td>
                        <td>{{ $vehicle->branch?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.fleet-vehicles.edit', $vehicle) }}" class="btn btn-xs btn-default">Edit</a>
                            <form method="post" action="{{ route('admin.fleet-vehicles.destroy', $vehicle) }}" class="d-inline" onsubmit="return confirm('Delete vehicle?');">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No vehicles added.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $vehicles->links() }}</div>
    </div>
@endsection
