@extends('iot.layouts.app')

@section('title', $device->name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap">
        <div>
            <h1 class="h3 mb-1">{{ $device->name }}</h1>
            @if($device->location)
                <p class="text-muted mb-0">{{ $device->location }}</p>
            @endif
            @if($device->notes)
                <p class="small mb-0">{{ $device->notes }}</p>
            @endif
        </div>
        <div class="btn-group btn-group-sm mt-2">
            <a href="{{ route('iot.devices.edit', $device) }}" class="btn btn-outline-secondary">{{ __('Edit site') }}</a>
            <a href="{{ route('iot.dashboard') }}" class="btn btn-outline-secondary">{{ __('Dashboard') }}</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card card-compact mb-3">
                <div class="card-header py-2"><strong>{{ __('Device & MQTT') }}</strong></div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ID</dt>
                        <dd class="col-sm-8"><code>{{ $device->id }}</code> (use in API URLs)</dd>
                        <dt class="col-sm-4">{{ __('Status') }}</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $device->status === 'online' ? 'success' : 'secondary' }}">{{ $device->status }}</span>
                            @if($device->last_seen)
                                · {{ $device->last_seen }}
                            @endif
                        </dd>
                        <dt class="col-sm-4">iot_user_id</dt>
                        <dd class="col-sm-8"><code>{{ $device->iot_user_id }}</code> → ESP <code>IOT_USER_ID</code></dd>
                        <dt class="col-sm-4">{{ __('UUID') }}</dt>
                        <dd class="col-sm-8"><code>{{ $device->device_uuid }}</code></dd>
                        <dt class="col-sm-4">{{ __('MQTT user') }}</dt>
                        <dd class="col-sm-8"><code>{{ $device->mqtt_username }}</code></dd>
                        <dt class="col-sm-4">{{ __('JWT password') }}</dt>
                        <dd class="col-sm-8">
                            @if($device->mqtt_jwt_token)
                                <pre class="mqtt-token bg-light p-2 mb-0">{{ $device->mqtt_jwt_token }}</pre>
                                @if($device->jwt_expires_at)
                                    <div class="text-muted mt-1">{{ __('Expires') }}: {{ $device->jwt_expires_at }}</div>
                                @endif
                            @else
                                <span class="text-warning">{{ __('No JWT — regenerate below') }}</span>
                            @endif
                        </dd>
                    </dl>
                    <form action="{{ route('iot.devices.jwt.regenerate', $device) }}" method="post" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">{{ __('Regenerate MQTT JWT') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            @if($presence)
                <div class="card card-compact mb-3 border-info">
                    <div class="card-header py-2">{{ __('Live presence (Redis)') }}</div>
                    <div class="card-body small"><pre class="mb-0">{{ json_encode($presence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div>
                </div>
            @endif
        </div>
    </div>

    {{-- Switches / components --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <strong>{{ __('Switches & actuators') }}</strong>
            <span class="badge badge-light">{{ $components->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Live status') }}</th>
                        <th>{{ __('Control') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($components as $comp)
                        @php
                            $live = $moduleStatuses[(string) $comp->channel] ?? null;
                            $livePayload = is_array($live) ? ($live['payload'] ?? null) : null;
                        @endphp
                        <tr>
                            <td>{{ $comp->name }}</td>
                            <td><code>{{ $comp->type }}</code></td>
                            <td><code>{{ $comp->channel }}</code></td>
                            <td class="small">
                                @if($livePayload)
                                    <code>{{ json_encode($livePayload) }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @foreach (['ON','OFF','TOGGLE'] as $act)
                                    <form action="{{ route('iot.devices.components.action', [$device, $comp]) }}" method="post" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="{{ $act }}">
                                        <button type="submit" class="btn btn-outline-primary btn-sm py-0">{{ $act }}</button>
                                    </form>
                                @endforeach
                            </td>
                            <td class="text-nowrap text-right">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0" data-toggle="collapse" data-target="#edit-comp-{{ $comp->id }}">{{ __('Edit') }}</button>
                                <form action="{{ route('iot.devices.components.destroy', [$device, $comp]) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this switch?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="edit-comp-{{ $comp->id }}">
                            <td colspan="6" class="bg-light">
                                <form method="post" action="{{ route('iot.devices.components.update', [$device, $comp]) }}" class="form-row align-items-end p-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-3">
                                        <label class="small mb-0">{{ __('Name') }}</label>
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $comp->name }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small mb-0">{{ __('Type') }}</label>
                                        <select name="type" class="form-control form-control-sm">
                                            @foreach(['switch','lock','dimmer','motor','valve','hvac','generic','sensor'] as $t)
                                                <option value="{{ $t }}" @selected($comp->type === $t)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small mb-0">{{ __('Channel') }}</label>
                                        <input type="number" name="channel" class="form-control form-control-sm" min="1" max="255" value="{{ $comp->channel }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Save') }}</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted p-3">{{ __('No switches yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <form method="post" action="{{ route('iot.devices.components.store', $device) }}" class="form-row align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="small mb-0">{{ __('New name') }}</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Door lock" required>
                </div>
                <div class="col-md-2">
                    <label class="small mb-0">{{ __('Type') }}</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="switch">switch</option>
                        <option value="lock">lock</option>
                        <option value="dimmer">dimmer</option>
                        <option value="motor">motor</option>
                        <option value="valve">valve</option>
                        <option value="generic">generic</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small mb-0">{{ __('Channel') }}</label>
                    <input type="number" name="channel" class="form-control form-control-sm" min="1" max="255" value="{{ ($components->max('channel') ?? 0) + 1 }}" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm">{{ __('Add switch') }}</button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">{{ __('MQTT topic') }}: <code>iot/{{ $device->iot_user_id }}/{{ $device->device_uuid }}/component/{channel}/set</code></p>
        </div>
    </div>

    {{-- Sensors --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <strong>{{ __('Sensors') }}</strong>
            <span class="badge badge-light">{{ $sensorSlots->count() }}</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>{{ __('Type') }} (MQTT)</th>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Critical') }}</th>
                    <th>{{ __('Live value') }}</th>
                    <th>{{ __('DB latest') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($sensorSlots as $slot)
                    @php
                        $live = $liveSensors[$slot->type] ?? null;
                        $db = $dbLatest->get($slot->type);
                    @endphp
                    <tr>
                        <td><code>{{ $slot->type }}</code></td>
                        <td>{{ $slot->label ?: '—' }}</td>
                        <td>@if($slot->is_critical)<span class="badge badge-warning">FCM</span>@else — @endif</td>
                        <td>
                            @if($live)
                                <code>{{ json_encode($live['value'] ?? null) }}</code>
                                <div class="small text-muted">{{ $live['recorded_at'] ?? '' }}</div>
                            @else
                                <span class="text-muted">{{ __('No live data') }}</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($db)
                                <code>{{ json_encode($db->value) }}</code>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right">
                            <form action="{{ route('iot.devices.sensor-slots.destroy', [$device, $slot]) }}" method="post" onsubmit="return confirm('{{ __('Remove sensor and clear readings?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted p-3">{{ __('No sensors configured. Add types that match ESP publishes (e.g. temperature, door_status).') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            @php
                $slotTypes = $sensorSlots->pluck('type')->all();
                $orphanLive = collect($liveSensors)->filter(fn ($v, $k) => ! in_array($k, $slotTypes, true));
            @endphp
            @if($orphanLive->isNotEmpty())
                <div class="border-top p-3 bg-light small">
                    <strong>{{ __('Live MQTT types not in list') }}</strong> (add them to track):
                    <ul class="mb-0">
                        @foreach($orphanLive as $type => $row)
                            <li><code>{{ $type }}</code> = <code>{{ json_encode($row['value'] ?? null) }}</code></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="card-footer">
            <form method="post" action="{{ route('iot.devices.sensor-slots.store', $device) }}" class="form-row align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="small mb-0">{{ __('MQTT type') }}</label>
                    <input type="text" name="type" class="form-control form-control-sm" placeholder="temperature" pattern="[a-z][a-z0-9_]*" required>
                </div>
                <div class="col-md-3">
                    <label class="small mb-0">{{ __('Label') }}</label>
                    <input type="text" name="label" class="form-control form-control-sm" placeholder="Room temp">
                </div>
                <div class="col-md-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="is_critical" value="1" id="is_critical">
                        <label class="custom-control-label small" for="is_critical">{{ __('Critical alert') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm">{{ __('Add sensor') }}</button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">{{ __('MQTT topic') }}: <code>iot/{{ $device->iot_user_id }}/{{ $device->device_uuid }}/sensor/{type}</code></p>
        </div>
    </div>

    <div class="card border-danger">
        <div class="card-header py-2 text-danger"><strong>{{ __('Danger zone') }}</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-2">{{ __('Deletes this customer site, all switches, sensors, and history.') }}</p>
            <form action="{{ route('iot.devices.destroy', $device) }}" method="post" onsubmit="return confirm('{{ __('Permanently delete this site and all data?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete customer site') }}</button>
            </form>
        </div>
    </div>
@endsection
