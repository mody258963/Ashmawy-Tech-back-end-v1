@extends('layouts.admin')
@section('title', 'New spare part')
@section('page-header')<h1 class="m-0">New spare part</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.spare-parts.store') }}" method="post">@csrf
            <div class="card-body">
                <div class="form-group"><label>Name</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                <div class="form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code') }}"></div>
                <div class="form-group">
                    <label>Unit type</label>
                    <select name="unit_type" class="form-control">
                        @foreach (['piece','meter','kilo'] as $u)
                            <option value="{{ $u }}" @selected(old('unit_type', 'piece') === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Quantity</label><input name="quantity" type="number" step="0.001" class="form-control" value="{{ old('quantity', 0) }}" required min="0"></div>
                <div class="form-group"><label>Cost price</label><input name="cost_price" class="form-control" value="{{ old('cost_price') }}"></div>
                <div class="form-group"><label>Selling price</label><input name="selling_price" class="form-control" value="{{ old('selling_price') }}"></div>
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.spare-parts.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
