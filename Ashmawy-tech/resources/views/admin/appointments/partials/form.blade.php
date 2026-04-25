<div class="form-group">
    <label>{{ __('messages.customer') }}</label>
    <select name="customer_id" class="form-control" required>
        <option value="">--</option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected((string) old('customer_id', $appointment->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }} ({{ $customer->phone }})</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>{{ __('messages.technician') }}</label>
    <select name="technician_id" class="form-control">
        <option value="">{{ __('messages.unassigned') }}</option>
        @foreach ($technicians as $technician)
            <option value="{{ $technician->id }}" @selected((string) old('technician_id', $appointment->technician_id ?? '') === (string) $technician->id)>{{ $technician->name }} ({{ $technician->role }})</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>{{ __('messages.scheduled_at') }}</label>
    <input type="datetime-local" name="scheduled_at" class="form-control" required
           value="{{ old('scheduled_at', isset($appointment->scheduled_at) ? $appointment->scheduled_at->format('Y-m-d\TH:i') : '') }}">
</div>

<div class="form-group">
    <label>{{ __('messages.status') }}</label>
    <select name="status" class="form-control">
        @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected(old('status', $appointment->status ?? 'scheduled') === $status)>{{ $status }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>{{ __('messages.address') }}</label>
    <textarea name="address" class="form-control" rows="2">{{ old('address', $appointment->address ?? '') }}</textarea>
</div>

<div class="form-group">
    <label>{{ __('messages.address_link') }}</label>
    <input type="url" name="address_link" class="form-control" value="{{ old('address_link', $appointment->address_link ?? '') }}" placeholder="https://maps.google.com/...">
</div>

<div class="form-group">
    <label>{{ __('messages.notes') }}</label>
    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $appointment->notes ?? '') }}</textarea>
</div>

