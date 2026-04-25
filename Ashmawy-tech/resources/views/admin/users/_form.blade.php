<div class="form-group">
    <label>Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
</div>
<div class="form-group">
    <label>Phone</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}" required>
</div>
<div class="form-group">
    <label>Password {{ $passwordRequired ? '' : '(leave blank to keep)' }}</label>
    <input type="password" name="password" class="form-control" {{ $passwordRequired ? 'required' : '' }}>
</div>
<div class="form-group">
    <label>Confirm password</label>
    <input type="password" name="password_confirmation" class="form-control" {{ $passwordRequired ? 'required' : '' }}>
</div>
<div class="form-group">
    <label>Role</label>
    <select name="role" class="form-control" required>
        @foreach (['owner','moderator','technician','collector','cashier'] as $role)
            <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $role }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Branch</label>
    <select name="branch_id" class="form-control">
        <option value="">—</option>
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
        @endforeach
    </select>
</div>
