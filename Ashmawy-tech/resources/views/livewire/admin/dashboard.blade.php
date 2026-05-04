@php($moderator = auth()->user()?->isModerator())
<div>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['orders']['total'] ?? 0 }}</h3>
                    <p>{{ __('messages.total_orders') }}</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                <a href="{{ route('admin.orders.index') }}" class="small-box-footer">{{ __('messages.more') }} <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['orders']['open'] ?? 0 }}</h3>
                    <p>{{ __('messages.open_orders') }}</p>
                </div>
                <div class="icon"><i class="fas fa-tools"></i></div>
                <a href="{{ route('admin.orders.index') }}" class="small-box-footer">{{ __('messages.more') }} <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        @unless ($moderator)
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format((float) ($stats['money']['payments']['month'] ?? 0), 2) }}</h3>
                    <p>{{ __('messages.payments_month') }}</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <a href="{{ route('admin.payments.index') }}" class="small-box-footer">{{ __('messages.more') }} <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['inventory']['low_stock_count'] ?? 0 }}</h3>
                    <p>{{ __('messages.low_stock_parts_lt_5') }}</p>
                </div>
                <div class="icon"><i class="fas fa-cogs"></i></div>
                <a href="{{ route('admin.spare-parts.index') }}" class="small-box-footer">{{ __('messages.more') }} <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        @endunless
    </div>

    @unless ($moderator)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.quick_summary') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-muted">{{ __('messages.profit_month') }}</div>
                            <div class="h4 mb-0">{{ number_format((float) ($stats['money']['profit']['month'] ?? 0), 2) }}</div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-muted">{{ __('messages.follow_ups_overdue') }}</div>
                            <div class="h4 mb-0">{{ $stats['followups']['overdue'] ?? 0 }}</div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-muted">{{ __('messages.aging_orders_gt_7_days') }}</div>
                            <div class="h4 mb-0">{{ $stats['orders']['aging']['gt_7d'] ?? 0 }}</div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-muted">{{ __('messages.low_stock_items') }}</div>
                            <div class="h4 mb-0">{{ $stats['inventory']['low_stock_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endunless

    <div class="row">
        <div class="{{ $moderator ? 'col-12' : 'col-md-6' }}">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.orders_by_status') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                        @foreach (($stats['orders']['by_status'] ?? []) as $status => $count)
                            <tr>
                                <td>{{ $status }}</td>
                                <td class="text-right">{{ $count }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted">
                    {{ __('messages.avg_days_to_deliver') }}: {{ $stats['orders']['aging']['avg_days_to_deliver'] ?? 0 }}
                    | {{ __('messages.aging_gt_3d') }}: {{ $stats['orders']['aging']['gt_3d'] ?? 0 }}
                    | {{ __('messages.aging_gt_7d') }}: {{ $stats['orders']['aging']['gt_7d'] ?? 0 }}
                    | {{ __('messages.aging_gt_14d') }}: {{ $stats['orders']['aging']['gt_14d'] ?? 0 }}
                </div>
            </div>
        </div>

        @unless ($moderator)
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.money') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('messages.metric') }}</th>
                            <th class="text-right">{{ __('messages.today') }}</th>
                            <th class="text-right">{{ __('messages.week') }}</th>
                            <th class="text-right">{{ __('messages.month') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{ __('messages.payments') }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['payments']['today'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['payments']['week'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['payments']['month'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.expenses') }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['expenses']['today'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['expenses']['week'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['expenses']['month'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.sales_price') }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_revenue']['today'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_revenue']['week'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_revenue']['month'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.sales_cost') }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_cost']['today'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_cost']['week'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_cost']['month'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.sales_profit') }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_profit']['today'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_profit']['week'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['inventory_profit']['month'] ?? 0), 2) }}</td>
                        </tr>
                        <tr class="font-weight-bold">
                            <td>{{ __('messages.net_profit') }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['profit']['today'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['profit']['week'] ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float) ($stats['money']['profit']['month'] ?? 0), 2) }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endunless
    </div>

    @unless ($moderator)
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.inventory_today') }}</h3>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        @foreach (($stats['inventory']['movements_today'] ?? []) as $type => $row)
                            <span class="badge badge-secondary">{{ $type }}: {{ $row['qty'] }} ({{ $row['count'] }})</span>
                        @endforeach
                    </div>
                    <div class="text-muted mb-2">{{ __('messages.low_stock_list') }}</div>
                    <ul class="pl-3 mb-0">
                        @foreach (($stats['inventory']['low_stock_list'] ?? []) as $part)
                            <li>{{ $part->name }} ({{ $part->quantity }}) - {{ $part->branch?->name }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.follow_ups') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-muted">{{ __('messages.due_today') }}</div>
                            <div class="h4 mb-0">{{ $stats['followups']['due_today'] ?? 0 }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">{{ __('messages.overdue') }}</div>
                            <div class="h4 mb-0">{{ $stats['followups']['overdue'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.follow-ups.index') }}" class="btn btn-sm btn-default">{{ __('messages.view_follow_ups') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endunless
</div>
