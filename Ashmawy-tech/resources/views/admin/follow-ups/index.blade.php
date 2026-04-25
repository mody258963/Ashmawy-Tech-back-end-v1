@extends('layouts.admin')
@section('title', __('messages.followups'))
@section('page-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">{{ __('messages.followups') }}</h1></div>
        <div class="col-sm-6 text-right"><a href="{{ route('admin.follow-ups.create') }}" class="btn btn-primary">{{ __('messages.add_followup') }}</a></div>
    </div>
@endsection
@section('content')
    @include('admin.partials.summary-cards', ['cards' => $cards ?? []])
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>{{ __('messages.customer') }}</th>
                    <th>{{ __('messages.phone') }}</th>
                    <th>{{ __('messages.note') }}</th>
                    <th>{{ __('messages.next') }}</th>
                    <th>{{ __('messages.followup_status') }}</th>
                    <th>{{ __('messages.days_left') }}</th>
                    <th>{{ __('messages.moderator') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($followUps as $f)
                    @php($nextAt = $f->next_follow_up_at ? \Illuminate\Support\Carbon::parse($f->next_follow_up_at) : null)
                    @php($daysLeft = $nextAt ? now()->startOfDay()->diffInDays($nextAt->copy()->startOfDay(), false) : null)
                    <tr>
                        <td>{{ $f->customer?->name }}</td>
                        <td>{{ $f->customer?->phone ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($f->note, 40) }}</td>
                        <td>{{ $nextAt?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @if (! $nextAt)
                                <span class="badge badge-secondary">{{ __('messages.no_date') }}</span>
                            @elseif ($nextAt->isPast())
                                <span class="badge badge-danger">{{ __('messages.overdue') }}</span>
                            @elseif ($nextAt->isToday())
                                <span class="badge badge-warning">{{ __('messages.due_today') }}</span>
                            @else
                                <span class="badge badge-success">{{ __('messages.upcoming') }}</span>
                            @endif
                        </td>
                        <td>{{ $daysLeft === null ? '—' : $daysLeft }}</td>
                        <td>{{ $f->moderator?->name }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.follow-ups.edit', $f) }}" class="btn btn-sm btn-default">{{ __('messages.edit') }}</a>
                            <form action="{{ route('admin.follow-ups.destroy', $f) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('messages.delete') }}</button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $followUps->links() }}</div>
    </div>
@endsection
