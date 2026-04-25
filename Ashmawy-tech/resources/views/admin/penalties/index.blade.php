@extends('layouts.admin')

@section('title', 'Penalties')

@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Penalties</h1></div>
        <div class="col-sm-6 text-right"><a href="{{ route('admin.penalties.create') }}" class="btn btn-primary">Add penalty</a></div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Branch</th>
                    <th>Month</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($penalties as $p)
                    <tr>
                        <td>{{ $p->user?->name }}</td>
                        <td>{{ $p->branch?->name ?? '—' }}</td>
                        <td>{{ $p->applied_for_month }}</td>
                        <td>{{ $p->amount }}</td>
                        <td>{{ $p->reason }}</td>
                        <td class="text-right">
                            <form action="{{ route('admin.penalties.destroy', $p) }}" method="post" class="d-inline" onsubmit="return confirm('Delete?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $penalties->links() }}</div>
    </div>
@endsection

