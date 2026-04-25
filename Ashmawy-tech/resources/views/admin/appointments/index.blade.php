@extends('layouts.admin')
@section('title', __('messages.appointments'))
@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">{{ __('messages.appointments') }}</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">{{ __('messages.add_appointment') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <form method="get" class="form-inline">
                <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control form-control-sm mr-2">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">{{ __('messages.status') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <select name="customer_id" class="form-control form-control-sm mr-2">
                    <option value="">{{ __('messages.customer') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                <select name="technician_id" class="form-control form-control-sm mr-2">
                    <option value="">{{ __('messages.technician') }}</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected($filters['technician_id'] === (string) $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">{{ __('messages.search') }}</button>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>{{ __('messages.id') }}</th>
                    <th>{{ __('messages.customer') }}</th>
                    <th>{{ __('messages.technician') }}</th>
                    <th>{{ __('messages.scheduled_at') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.address') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($appointments as $appointment)
                    <tr>
                        <td>{{ $appointment->id }}</td>
                        <td>{{ $appointment->customer?->name }}</td>
                        <td>{{ $appointment->technician?->name ?? __('messages.unassigned') }}</td>
                        <td>{{ $appointment->scheduled_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $appointment->status }}</td>
                        <td>
                            {{ \Illuminate\Support\Str::limit((string) $appointment->address, 40) }}
                            @if ($appointment->address_link)
                                <a href="{{ $appointment->address_link }}" target="_blank" rel="noopener">{{ __('messages.map') }}</a>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.appointments.edit', $appointment) }}" class="btn btn-sm btn-default">{{ __('messages.edit') }}</a>
                            <form action="{{ route('admin.appointments.destroy', $appointment) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('messages.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">{{ __('messages.no_appointments_yet') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $appointments->links() }}</div>
    </div>
@endsection

