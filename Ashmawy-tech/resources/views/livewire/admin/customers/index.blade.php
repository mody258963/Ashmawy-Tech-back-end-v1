<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ __('messages.customers') }}</h3>
            <div class="d-flex">
                <input type="text" class="form-control form-control-sm mr-2" placeholder="{{ __('messages.search_customers_placeholder') }}" wire:model.live.debounce.300ms="search" style="width: 260px;">
                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">{{ __('messages.add_customer') }}</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.phone') }}</th>
                    <th>{{ __('messages.branch') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.address_link') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>{{ $customer->id }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->branch?->name }}</td>
                        <td>{{ $customer->status }}</td>
                        <td>
                            @if ($customer->address_link)
                                <a href="{{ $customer->address_link }}" target="_blank" rel="noopener">{{ __('messages.map') }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-xs btn-default">{{ __('messages.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">{{ __('messages.no_customers_yet') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $customers->links() }}
        </div>
    </div>
</div>
