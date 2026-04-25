<div class="form-group">
    <label>Worker</label>
    <select name="user_id" class="form-control" required>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected(old('user_id', $salary?->user_id) == $user->id)>{{ $user->name }} ({{ $user->role }})</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Branch</label>
    <select name="branch_id" class="form-control">
        <option value="">—</option>
        @foreach ($branches as $b)
            <option value="{{ $b->id }}" @selected(old('branch_id', $salary?->branch_id) == $b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Month</label><input type="month" name="for_month" class="form-control" required value="{{ old('for_month', $salary?->for_month?->format('Y-m') ?? now()->format('Y-m')) }}"></div>
<div class="form-group"><label>Base salary</label><input type="text" name="base_amount" class="form-control" required value="{{ old('base_amount', $salary?->base_amount) }}"></div>
<div class="form-group">
    <label>Status</label>
    <select name="status" class="form-control">
        @foreach (['draft','approved','paid'] as $s)
            <option value="{{ $s }}" @selected(old('status', $salary?->status ?? 'draft') === $s)>{{ $s }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $salary?->notes) }}</textarea></div>
