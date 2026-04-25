@extends('layouts.admin')

@section('title', 'Users')

@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Users</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add user</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Branch</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->role }}</td>
                        <td>{{ $user->branch?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-default">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="post" class="d-inline" onsubmit="return confirm('Delete user?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $users->links() }}</div>
    </div>
@endsection
