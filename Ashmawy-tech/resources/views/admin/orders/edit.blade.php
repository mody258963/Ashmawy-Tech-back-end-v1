@extends('layouts.admin')
@section('title', 'Edit order')
@section('page-header')<h1 class="m-0">Edit order {{ $order->order_number }}</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.orders.update', $order) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                <div class="mb-3 text-right">
                    <a href="{{ route('admin.orders.calendar') }}" class="btn btn-sm btn-outline-secondary">Pickup calendar</a>
                </div>
                <div class="form-group">
                    <label>Customer</label>
                    <input type="text" id="customer_search_order_edit" class="form-control mb-2" placeholder="Search customer by name or phone">
                    <select id="customer_select_order_edit" name="customer_id" class="form-control" required>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $order->customer_id)==$c->id)>{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Device</label>
                    <select id="device_select_order_edit" name="device_id" class="form-control" required>
                        @foreach ($devices as $d)
                            <option value="{{ $d->id }}" data-customer-id="{{ $d->customer_id }}" @selected(old('device_id', $order->device_id)==$d->id)>#{{ $d->id }} {{ $d->type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id', $order->branch_id)==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Technician</label>
                    <select name="technician_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('technician_id', $order->technician_id)==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Collector</label>
                    <select name="collector_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(old('collector_id', $order->collector_id)==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Estimated cost</label><input type="text" name="estimated_cost" class="form-control" value="{{ old('estimated_cost', $order->estimated_cost) }}" required></div>
                <div class="form-group"><label>Final cost</label><input type="text" name="final_cost" class="form-control" value="{{ old('final_cost', $order->final_cost) }}"></div>
                <div class="form-group">
                    <label>Service mode</label>
                    <select name="service_mode" class="form-control" id="service_mode_edit">
                        <option value="workshop" @selected(old('service_mode', $order->service_mode ?? 'workshop') === 'workshop')>Workshop (normal flow)</option>
                        <option value="home_service" @selected(old('service_mode', $order->service_mode) === 'home_service')>Home service (no collection)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Home service stage</label>
                    <select name="home_service_stage" class="form-control" id="home_stage_edit">
                        <option value="">-- none --</option>
                        @foreach (['home_visit_scheduled','on_the_way','home_service_in_progress','home_service_done'] as $stage)
                            <option value="{{ $stage }}" @selected(old('home_service_stage', $order->home_service_stage) === $stage)>{{ $stage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach (['pending_pickup','received','diagnosing','waiting_approval','repairing','ready','delivered','cancelled'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $order->status)===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form-check">
                    <input type="hidden" name="approved" value="0">
                    <input type="checkbox" name="approved" value="1" class="form-check-input" id="ap" @checked(old('approved', $order->approved))>
                    <label class="form-check-label" for="ap">Approved</label>
                </div>
                <div class="form-group">
                    <label>Received at <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="received_at" class="form-control" value="{{ old('received_at', $order->received_at?->format('Y-m-d\TH:i') ?? '') }}">
                    <small class="form-text text-muted">Required when status is pending_pickup.</small>
                </div>
                <div class="form-group">
                    <label>Delivered at <span class="text-muted font-weight-normal">(optional)</span></label>
                    <input type="datetime-local" name="delivered_at" class="form-control" value="{{ old('delivered_at', $order->delivered_at?->format('Y-m-d\TH:i') ?? '') }}">
                    <small class="form-text text-muted">Leave empty until the order is completed and returned to the customer.</small>
                </div>
                <hr>
                <h5>Use inventory in this order</h5>
                <div class="row">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="col-md-4">
                            <div class="border rounded p-2 mb-2">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Part</label>
                                    <select name="parts[{{ $i }}][spare_part_id]" class="form-control form-control-sm">
                                        <option value="">-- none --</option>
                                        @foreach ($spareParts as $part)
                                            <option value="{{ $part->id }}">{{ $part->name }} ({{ $part->quantity }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="mb-1">Qty</label>
                                    <input type="number" min="1" class="form-control form-control-sm" name="parts[{{ $i }}][quantity]">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="mb-1">Unit sell price</label>
                                    <input type="text" class="form-control form-control-sm" name="parts[{{ $i }}][unit_price]">
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-default">Back</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const searchInput = document.getElementById('customer_search_order_edit');
            const customerSelect = document.getElementById('customer_select_order_edit');
            const deviceSelect = document.getElementById('device_select_order_edit');
            if (!searchInput || !customerSelect || !deviceSelect) {
                return;
            }

            const syncDevices = function () {
                const selectedCustomerId = customerSelect.value;
                let firstVisibleDevice = null;

                Array.from(deviceSelect.options).forEach(function (option) {
                    const match = option.dataset.customerId === selectedCustomerId;
                    option.hidden = !match;

                    if (match && !firstVisibleDevice) {
                        firstVisibleDevice = option;
                    }
                });

                if (!deviceSelect.selectedOptions.length || deviceSelect.selectedOptions[0].hidden) {
                    if (firstVisibleDevice) {
                        deviceSelect.value = firstVisibleDevice.value;
                    }
                }
            };

            searchInput.addEventListener('input', function () {
                const needle = this.value.toLowerCase().trim();
                Array.from(customerSelect.options).forEach(function (option) {
                    const match = option.text.toLowerCase().includes(needle);
                    option.hidden = !match;
                });
            });

            customerSelect.addEventListener('change', syncDevices);
            syncDevices();
        })();

        (function () {
            const mode = document.getElementById('service_mode_edit');
            const stage = document.getElementById('home_stage_edit');
            if (!mode || !stage) {
                return;
            }
            const sync = function () {
                const isHome = mode.value === 'home_service';
                stage.disabled = !isHome;
                if (!isHome) {
                    stage.value = '';
                }
            };
            mode.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
