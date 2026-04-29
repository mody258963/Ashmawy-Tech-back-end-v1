@extends('layouts.admin')
@section('title', 'Orders calendar')
@section('page-header')<h1 class="m-0">Orders calendar (pickup + home service)</h1>@endsection
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
                    <th>Scheduled at</th>
                    <th>Order</th>
                    <th>Service mode</th>
                    <th>Flow stage</th>
                    <th>Customer</th>
                    <th>Device</th>
                    <th>Branch</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ ($order->received_at ?? $order->created_at)?->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>
                            @if ($order->service_mode === \App\Models\Order::SERVICE_MODE_HOME)
                                <span class="badge badge-info">Home service</span>
                            @else
                                <span class="badge badge-secondary">Workshop pickup</span>
                            @endif
                        </td>
                        <td>{{ $order->home_service_stage ?? $order->status }}</td>
                        <td>{{ $order->customer?->name }}</td>
                        <td>{{ $order->device?->type }}</td>
                        <td>{{ $order->branch?->name ?? '—' }}</td>
                        <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-xs btn-info">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No pickup or home-service orders in selected month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
