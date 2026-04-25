<div class="form-group">
    <label>Vehicle</label>
    <select name="fleet_vehicle_id" class="form-control" required>
        @foreach ($vehicles as $vehicle)
            <option value="{{ $vehicle->id }}" @selected(old('fleet_vehicle_id', $item?->fleet_vehicle_id) == $vehicle->id)>{{ $vehicle->name }} ({{ $vehicle->plate_number }})</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Service type</label><input name="service_type" class="form-control" required value="{{ old('service_type', $item?->service_type) }}"></div>
<div class="form-group"><label>Cost</label><input type="text" name="cost" class="form-control" required value="{{ old('cost', $item?->cost) }}"></div>
<div class="form-group"><label>Odometer</label><input type="number" min="0" name="odometer" class="form-control" value="{{ old('odometer', $item?->odometer) }}"></div>
<div class="form-group"><label>Service date</label><input type="date" name="service_date" class="form-control" required value="{{ old('service_date', $item?->service_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"></div>
<div class="form-group"><label>Next service date</label><input type="date" name="next_service_date" class="form-control" value="{{ old('next_service_date', $item?->next_service_date?->format('Y-m-d')) }}"></div>
<div class="form-group"><label>Notes</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $item?->notes) }}</textarea></div>
