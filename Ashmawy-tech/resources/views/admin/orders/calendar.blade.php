@extends('layouts.admin')
@section('title', 'Pickup calendar')
@section('page-header')<h1 class="m-0">Pending pickup calendar</h1>@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <form method="get" class="form-inline">
                <label class="mr-2 mb-0">Month</label>
                <input type="month" name="month" class="form-control mr-2" value="{{ $start->format('Y-m') }}">
                <button class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-default btn-sm ml-2">Back to orders</a>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Received at</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Device</th>
                    <th>Branch</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->received_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer?->name }}</td>
                        <td>{{ $order->device?->type }}</td>
                        <td>{{ $order->branch?->name ?? '—' }}</td>
                        <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-xs btn-info">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No pending pickup orders in selected month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
