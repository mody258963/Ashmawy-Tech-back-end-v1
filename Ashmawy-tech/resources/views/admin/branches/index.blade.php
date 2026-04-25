@extends('layouts.admin')

@section('title', 'Branches')

@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Branches</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Add branch</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr><th>Name</th><th>Phone</th><th>Address</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($branches as $branch)
                    <tr>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->phone ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($branch->address ?? '', 40) }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-default">Edit</a>
                            <form action="{{ route('admin.branches.destroy', $branch) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this branch?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $branches->links() }}</div>
    </div>
@endsection
