<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ __('messages.orders') }}</h3>
            <div class="d-flex">
                <input type="text" class="form-control form-control-sm mr-2" placeholder="{{ __('messages.search_orders_placeholder') }}" wire:model.live.debounce.300ms="search" style="width: 280px;">
                <a href="{{ route('admin.orders.calendar') }}" class="btn btn-outline-secondary btn-sm mr-2">{{ __('messages.calendar') }}</a>
                <a href="{{ route('admin.orders.create') }}" class="btn btn-primary btn-sm">{{ __('messages.new_order') }}</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('messages.order') }}</th>
                    <th>{{ __('messages.customer') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.branch') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer?->name }}</td>
                        <td><span class="badge badge-secondary">{{ $order->status }}</span></td>
                        <td>{{ $order->branch?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-xs btn-info">{{ __('messages.view') }}</a>
                            <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-xs btn-default">{{ __('messages.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ __('messages.no_orders_yet') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $orders->links() }}
        </div>
    </div>
</div>
