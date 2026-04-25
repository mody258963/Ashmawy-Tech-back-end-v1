@extends('layouts.admin')

@section('title', 'Performance')

@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Performance</h1></div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <form class="form-inline" method="get" action="{{ route('admin.performance.index') }}">
                <label class="mr-2">Month</label>
                <input type="date" name="month" class="form-control form-control-sm mr-2" value="{{ $monthStart->toDateString() }}">
                <label class="mr-2">Branch ID</label>
                <input type="text" name="branch_id" class="form-control form-control-sm mr-2" value="{{ $branchId ?? '' }}" placeholder="optional">
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Worker</th>
                    <th>Total delivered (month)</th>
                    <th>Penalties (month)</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    @php($u = $users[$row['technician_id']] ?? null)
                    <tr>
                        <td>{{ $u?->name ?? ('#'.$row['technician_id']) }}</td>
                        <td>{{ $row['total_orders'] }}</td>
                        <td>{{ number_format((float) $row['penalties'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">No data for this month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

