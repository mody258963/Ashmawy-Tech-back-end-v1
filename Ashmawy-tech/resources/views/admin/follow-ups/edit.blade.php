@extends('layouts.admin')
@section('title', 'Edit follow-up')
@section('page-header')<h1 class="m-0">Edit follow-up</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.follow-ups.update', $followUp) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Customer</label>
                    <select name="customer_id" class="form-control" required>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $followUp->customer_id)==$c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Note</label><textarea name="note" class="form-control" rows="4" required>{{ old('note', $followUp->note) }}</textarea></div>
                <div class="form-group"><label>Next follow-up</label><input type="datetime-local" name="next_follow_up_at" class="form-control" value="{{ old('next_follow_up_at', optional($followUp->next_follow_up_at)->format('Y-m-d\TH:i')) }}"></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.follow-ups.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
