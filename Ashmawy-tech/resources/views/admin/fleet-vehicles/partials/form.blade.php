<div class="form-group"><label>Name</label><input name="name" class="form-control" required value="{{ old('name', $vehicle?->name) }}"></div>
<div class="form-group">
    <label>Type</label>
    <select name="type" class="form-control">
        @foreach (['van','car'] as $t)
            <option value="{{ $t }}" @selected(old('type', $vehicle?->type ?? 'van') === $t)>{{ $t }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Plate number</label><input name="plate_number" class="form-control" required value="{{ old('plate_number', $vehicle?->plate_number) }}"></div>
<div class="form-group"><label>Odometer</label><input type="number" min="0" name="odometer" class="form-control" value="{{ old('odometer', $vehicle?->odometer ?? 0) }}"></div>
<div class="form-group"><label>Service interval KM</label><input type="number" min="100" name="service_interval_km" class="form-control" value="{{ old('service_interval_km', $vehicle?->service_interval_km ?? 5000) }}"></div>
<div class="form-group"><label>Last service at</label><input type="date" name="last_service_at" class="form-control" value="{{ old('last_service_at', $vehicle?->last_service_at?->format('Y-m-d')) }}"></div>
<div class="form-group">
    <label>Branch</label>
    <select name="branch_id" class="form-control">
        <option value="">—</option>
        @foreach ($branches as $b)
            <option value="{{ $b->id }}" @selected(old('branch_id', $vehicle?->branch_id) == $b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group form-check">
    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" @checked(old('active', $vehicle?->active ?? true))>
    <label for="active" class="form-check-label">Active</label>
</div>
