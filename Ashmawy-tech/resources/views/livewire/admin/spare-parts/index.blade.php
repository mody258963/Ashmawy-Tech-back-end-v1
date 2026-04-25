<div>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['products_total'] ?? 0 }}</h3>
                    <p>{{ __('messages.total_products') }}</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['products_sold'] ?? 0 }}</h3>
                    <p>{{ __('messages.products_sold') }}</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format((float) ($summary['capital_total'] ?? 0), 2) }}</h3>
                    <p>{{ __('messages.capital_in_stock_cost') }}</p>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format((float) ($summary['sold_price_total'] ?? 0), 2) }}</h3>
                    <p>{{ __('messages.total_sold_price') }}</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format((float) ($summary['sold_cost_total'] ?? 0), 2) }}</h3>
                    <p>{{ __('messages.total_sold_cost') }}</p>
                </div>
                <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format((float) ($summary['profit_total'] ?? 0), 2) }}</h3>
                    <p>{{ __('messages.total_profit_revenue_cost') }}</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ __('messages.inventory') }}</h3>
            <div class="d-flex">
                <input type="text" class="form-control form-control-sm mr-2" placeholder="{{ __('messages.search_item_by_name_code') }}" wire:model.live.debounce.300ms="search" style="width: 220px;">
                <a href="{{ route('admin.spare-parts.create') }}" class="btn btn-primary btn-sm">{{ __('messages.add_part') }}</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                <tr>
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.code') }}</th>
                    <th>{{ __('messages.branch') }}</th>
                    <th>{{ __('messages.qty') }}</th>
                    <th>{{ __('messages.unit') }}</th>
                    <th>{{ __('messages.cost') }}</th>
                    <th>{{ __('messages.price') }}</th>
                    <th>{{ __('messages.sold_qty') }}</th>
                    <th>{{ __('messages.purchased_qty') }}</th>
                    <th>{{ __('messages.adjustments') }}</th>
                    <th>{{ __('messages.last_sold') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.profit') }}</th>
                    <th>{{ __('messages.stock_value') }}</th>
                    <th>{{ __('messages.margin_percent') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($spareParts as $part)
                    @php($sales = $salesByPart[$part->id] ?? ['sold_qty' => 0, 'last_sold_at' => null])
                    @php($movement = $movementByPart[$part->id] ?? ['purchase_qty' => 0, 'adjust_in_qty' => 0, 'adjust_out_qty' => 0])
                    @php($profit = ((float) ($part->selling_price ?? 0) - (float) ($part->cost_price ?? 0)) * (int) $sales['sold_qty'])
                    @php($stockValue = ((float) $part->quantity) * ((float) ($part->cost_price ?? 0)))
                    @php($marginPct = (float) ($part->selling_price ?? 0) > 0 ? ((((float) ($part->selling_price ?? 0) - (float) ($part->cost_price ?? 0)) / (float) ($part->selling_price ?? 0)) * 100) : 0)
                    <tr>
                        <td>{{ $part->name }}</td>
                        <td>{{ $part->code ?? '—' }}</td>
                        <td>{{ $part->branch?->name }}</td>
                        <td>{{ $part->quantity }}</td>
                        <td>{{ $part->unit_type ?? 'piece' }}</td>
                        <td>{{ $part->cost_price ?? '—' }}</td>
                        <td>{{ $part->selling_price ?? '—' }}</td>
                        <td>{{ $sales['sold_qty'] }}</td>
                        <td>{{ $movement['purchase_qty'] }}</td>
                        <td>+{{ $movement['adjust_in_qty'] }} / -{{ $movement['adjust_out_qty'] }}</td>
                        <td>{{ $sales['last_sold_at'] ? \Illuminate\Support\Carbon::parse($sales['last_sold_at'])->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            @if ((float) $part->quantity <= 0)
                                <span class="badge badge-danger">{{ __('messages.out_of_stock') }}</span>
                            @elseif ((float) $part->quantity < 5)
                                <span class="badge badge-warning">{{ __('messages.low') }}</span>
                            @else
                                <span class="badge badge-success">{{ __('messages.good') }}</span>
                            @endif
                        </td>
                        <td>{{ number_format((float) $profit, 2) }}</td>
                        <td>{{ number_format((float) $stockValue, 2) }}</td>
                        <td>{{ number_format((float) $marginPct, 1) }}%</td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-secondary" wire:click="openInventory({{ $part->id }})">{{ __('messages.inventory') }}</button>
                            <a href="{{ route('admin.spare-parts.edit', $part) }}" class="btn btn-sm btn-default">{{ __('messages.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center text-muted">{{ __('messages.no_spare_parts_yet') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $spareParts->links() }}
        </div>
    </div>

    @if ($inventoryPartId)
        @php($active = $spareParts->firstWhere('id', $inventoryPartId) ?? \App\Models\SparePart::query()->find($inventoryPartId))
        <div class="card card-secondary mt-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.inventory') }} — {{ $active?->name }}</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" wire:click="closeInventory"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5>{{ __('messages.purchase_stock_in') }}</h5>
                        <form wire:submit.prevent="recordPurchase">
                            <div class="form-group">
                                <label>{{ __('messages.quantity') }}</label>
                                <input type="number" class="form-control" wire:model="purchase_quantity" min="1">
                            </div>
                            <div class="form-group">
                                <label>{{ __('messages.unit_cost_optional') }}</label>
                                <input type="text" class="form-control" wire:model="purchase_unit_cost">
                            </div>
                            <button type="submit" class="btn btn-success">{{ __('messages.record_purchase') }}</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <h5>{{ __('messages.sale_stock_out') }}</h5>
                        <form wire:submit.prevent="recordSale">
                            <div class="form-group">
                                <label>{{ __('messages.quantity') }}</label>
                                <input type="number" class="form-control" wire:model="sale_quantity" min="1">
                            </div>
                            <div class="form-group">
                                <label>{{ __('messages.unit_price_optional') }}</label>
                                <input type="text" class="form-control" wire:model="sale_unit_price">
                            </div>
                            <button type="submit" class="btn btn-warning">{{ __('messages.record_sale') }}</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <h5>{{ __('messages.adjustment') }}</h5>
                        <form wire:submit.prevent="recordAdjust">
                            <div class="form-group">
                                <label>{{ __('messages.direction') }}</label>
                                <select class="form-control" wire:model="adjust_direction">
                                    <option value="in">{{ __('messages.in') }}</option>
                                    <option value="out">{{ __('messages.out') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('messages.quantity') }}</label>
                                <input type="number" class="form-control" wire:model="adjust_quantity" min="1">
                            </div>
                            <button type="submit" class="btn btn-secondary">{{ __('messages.record_adjustment') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
