@extends('layouts.admin')
@section('title', 'New follow-up')
@section('page-header')<h1 class="m-0">New follow-up</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.follow-ups.store') }}" method="post">@csrf
            <div class="card-body">
                <div class="form-group">
                    <label>Customer</label>
                    <select name="customer_id" class="form-control" required>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Note</label><textarea name="note" class="form-control" rows="4" required>{{ old('note') }}</textarea></div>
                <div class="form-group"><label>Next follow-up</label><input type="datetime-local" name="next_follow_up_at" class="form-control" value="{{ old('next_follow_up_at') }}"></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.follow-ups.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
