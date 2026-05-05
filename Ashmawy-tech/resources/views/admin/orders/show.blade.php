@extends('layouts.admin')
@section('title', 'Order '.$order->order_number)
@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Order {{ $order->order_number }}</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-default">All orders</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Details</h3></div>
                <div class="card-body">
                    <p><strong>Status:</strong> {{ $order->status }}</p>
                    <p><strong>Customer:</strong> {{ $order->customer?->name }}</p>
                    <p><strong>Device:</strong> {{ $order->device?->type }} ({{ $order->device?->brand }} {{ $order->device?->model }})</p>
                    <p><strong>Branch:</strong> {{ $order->branch?->name ?? '—' }}</p>
                    <p><strong>Estimated:</strong> {{ $order->estimated_cost }}</p>
                    <p><strong>Final:</strong> {{ $order->final_cost ?? '—' }}</p>
                    <p><strong>Order expenses:</strong> {{ number_format((float) $order->expenses->sum('amount'), 2) }}</p>
                    <p><strong>Approved:</strong> {{ $order->approved ? 'Yes' : 'No' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Add note</h3></div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.notes.store', $order) }}" method="post">
                        @csrf
                        <div class="form-group">
                            <textarea name="note" class="form-control" rows="3" required placeholder="Note..."></textarea>
                        </div>
                        <button class="btn btn-primary btn-sm">Add note</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">{{ __('messages.parts_used') }}</h3></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.quantity') }}</th>
                    <th>{{ __('messages.unit_price_optional') }}</th>
                    <th>{{ __('messages.total') }}</th>
                </tr>
                </thead>
                <tbody>
                @php($partsTotal = 0)
                @forelse ($order->spareParts as $part)
                    @php($lineTotal = (float) $part->pivot->quantity * (float) $part->pivot->unit_price)
                    @php($partsTotal += $lineTotal)
                    <tr>
                        <td>{{ $part->name }}</td>
                        <td>{{ $part->pivot->quantity }}</td>
                        <td>{{ number_format((float) $part->pivot->unit_price, 2) }}</td>
                        <td>{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">{{ __('messages.no_parts_used') }}</td></tr>
                @endforelse
                </tbody>
                @if ($order->spareParts->count() > 0)
                    <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">{{ __('messages.total') }}</th>
                        <th>{{ number_format($partsTotal, 2) }}</th>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Notes</h3></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                @forelse ($notes as $note)
                    <tr>
                        <td>{{ $note->created_at }}</td>
                        <td>{{ $note->user?->name }}</td>
                        <td>{{ $note->note }}</td>
                    </tr>
                @empty
                    <tr><td class="text-muted">No notes.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Status history</h3></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                @foreach ($histories as $h)
                    <tr>
                        <td>{{ $h->changed_at }}</td>
                        <td>{{ $h->from_status }} → {{ $h->to_status }}</td>
                        <td>{{ $h->changedBy?->name }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
