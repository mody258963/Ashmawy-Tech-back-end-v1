@extends('layouts.admin')
@section('title', 'Edit spare part')
@section('page-header')<h1 class="m-0">Edit spare part</h1>@endsection
@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.spare-parts.update', $sparePart) }}" method="post">@csrf @method('PUT')
            <div class="card-body">
                <div class="form-group"><label>Name</label><input name="name" class="form-control" value="{{ old('name', $sparePart->name) }}" required></div>
                <div class="form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code', $sparePart->code) }}"></div>
                <div class="form-group">
                    <label>Unit type</label>
                    <select name="unit_type" class="form-control">
                        @foreach (['piece','meter','kilo'] as $u)
                            <option value="{{ $u }}" @selected(old('unit_type', $sparePart->unit_type ?? 'piece') === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Quantity</label><input name="quantity" type="number" step="0.001" class="form-control" value="{{ old('quantity', $sparePart->quantity) }}" required min="0"></div>
                <div class="form-group"><label>Cost price</label><input name="cost_price" class="form-control" value="{{ old('cost_price', $sparePart->cost_price) }}"></div>
                <div class="form-group"><label>Selling price</label><input name="selling_price" class="form-control" value="{{ old('selling_price', $sparePart->selling_price) }}"></div>
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id', $sparePart->branch_id)==$b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.spare-parts.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
