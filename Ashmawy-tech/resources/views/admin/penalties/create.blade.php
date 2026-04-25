@extends('layouts.admin')

@section('title', 'New penalty')

@section('page-header')
    <h1 class="m-0">New penalty</h1>
@endsection

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.penalties.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label>User</label>
                    <select name="user_id" class="form-control" required>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Branch (optional)</label>
                    <select name="branch_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Applied for month</label>
                    <input type="date" name="applied_for_month" class="form-control" value="{{ old('applied_for_month', $monthStart) }}" required>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" name="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" required>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.penalties.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection

