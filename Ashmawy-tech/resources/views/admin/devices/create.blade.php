@extends('layouts.admin')
@section('title', 'New device')
@section('page-header')<h1 class="m-0">New device</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.devices.store') }}" method="post">@csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Customer</label>
                    <input type="text" id="customer_search_device_create" class="form-control mb-2" placeholder="Search customer by name or phone">
                    <select id="customer_select_device_create" name="customer_id" class="form-control" required>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Type</label><input name="type" class="form-control" required value="{{ old('type') }}"></div>
                <div class="form-group"><label>Brand</label><input name="brand" class="form-control" value="{{ old('brand') }}"></div>
                <div class="form-group"><label>Model</label><input name="model" class="form-control" value="{{ old('model') }}"></div>
                <div class="form-group"><label>Serial</label><input name="serial_number" class="form-control" value="{{ old('serial_number') }}"></div>
                <div class="form-group"><label>Issue</label><textarea name="issue_description" class="form-control" rows="3">{{ old('issue_description') }}</textarea></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.devices.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const searchInput = document.getElementById('customer_search_device_create');
            const select = document.getElementById('customer_select_device_create');
            if (!searchInput || !select) {
                return;
            }
            searchInput.addEventListener('input', function () {
                const needle = this.value.toLowerCase().trim();
                Array.from(select.options).forEach(function (option) {
                    const match = option.text.toLowerCase().includes(needle);
                    option.hidden = !match;
                });
            });
        })();
    </script>
@endpush
