# IoT SaaS module

This app ships a **separate IoT product surface** alongside the repair-shop admin:

| Surface | URL / prefix | Auth |
|--------|----------------|------|
| Web dashboard | `/iot/login`, `/iot/dashboard`, `/iot/devices/*` | Session guard `iot-web` (`iot_users` table) |
| Mobile / Flutter API | `/api/v1/iot/*` | Passport guard `iot-api` (`Bearer` token) |
| ESP32 / devices | MQTT to EMQX | JWT password (see below) |

Staff `users` and repair `customers` tables are **unchanged**. IoT accounts live in `iot_users` with optional `customer_id` link.

## One-time setup

1. Run migrations: `php artisan migrate`
2. Create Passport client for IoT provider: `php artisan iot:ensure-passport-client`  
   (Docker `entrypoint.sh` runs this after migrations when `RUN_MIGRATIONS=true`.)
3. Set `IOT_JWT_SECRET` (≥32 chars) and `MQTT_HOST` in `.env`.
4. For ingestion, use **Redis** queue: `QUEUE_CONNECTION=redis`, then:
   - `php artisan queue:work redis --queue=iot`
   - `php artisan iot:mqtt-subscribe`

### Create a test `iot_user` + device (Tinker)

```php
$u = \App\Models\Iot\IotUser::create([
    'name' => 'Demo',
    'email' => 'iot-demo@example.com',
    'password' => 'password',
    'is_active' => true,
]);
$d = \App\Models\Iot\IotDevice::create([
    'iot_user_id' => $u->id,
    'device_uuid' => (string) \Illuminate\Support\Str::uuid(),
    'name' => 'Living room',
    'mqtt_username' => 'dev-'.\Illuminate\Support\Str::random(8),
]);
\App\Models\Iot\IotComponent::create([
    'iot_device_id' => $d->id,
    'name' => 'Light',
    'type' => 'switch',
    'channel' => 1,
]);
app(\App\Services\Iot\DeviceJwtService::class)->generate($d);
```

## MQTT topic layout

```
iot/{iot_user_id}/{device_uuid}/component/{channel}/set
iot/{iot_user_id}/{device_uuid}/component/{channel}/status
iot/{iot_user_id}/{device_uuid}/sensor/{type}
iot/{iot_user_id}/{device_uuid}/device/status
```

- `iot_user_id` is the **numeric** `iot_users.id` (not the repair-shop `users.id`).
- `device_uuid` is a UUID string stored on `iot_devices.device_uuid`.

## Laravel → device command (QoS 1)

Laravel publishes JSON to the `.../component/{channel}/set` topic:

```json
{"action":"ON","value":null,"message_id":"<uuid>","ts":"2026-05-04T12:00:00+00:00"}
```

Implementation: `App\Services\Iot\MqttPublisherService` (uses `PhpMqtt\Client\ConnectionManager` with **QoS 1**).

## Device JWT (EMQX)

`App\Services\Iot\DeviceJwtService` issues **HS256** JWTs signed with `IOT_JWT_SECRET`. Claims include:

| Claim | Purpose |
|-------|---------|
| `sub` | Same as `mqtt_username` (device-specific username) |
| `iot_user_id` | Tenant id |
| `device_uuid` | Device id string |
| `acl` | Array of allowed topic prefixes, e.g. `iot/{id}/{uuid}/#` |
| `exp` / `iat` | Expiry |

ESP32 should connect with:

- **Username:** `mqtt_username` from API / DB  
- **Password:** the JWT string returned by `POST /api/v1/iot/devices/{id}/jwt/regenerate` or shown in the web dashboard after regenerate.

Tune **EMQX 5** JWT authentication to verify HS256 with the same secret as `IOT_JWT_SECRET`. The sample `docker-compose.yml` sets basic `EMQX_AUTHENTICATION__*` env vars; adjust for your EMQX version and security policy (JWKS, TLS, etc.).

## REST API (Flutter)

Base URL: `{APP_URL}/api/v1/iot`

| Method | Path | Auth |
|--------|------|------|
| POST | `/auth/login` | Body: `email`, `password`, optional `device_name` |
| POST | `/auth/logout` | Bearer |
| GET | `/me` | Bearer |
| GET | `/devices` | Bearer |
| GET | `/devices/{id}` | Bearer |
| POST | `/devices/{id}/jwt/regenerate` | Bearer |
| GET | `/devices/{id}/components` | Bearer |
| POST | `/devices/{id}/components/{component}/action` | Bearer, body: `action` (`ON`,`OFF`,`TOGGLE`,`SET`), optional `value` object |
| GET | `/devices/{id}/sensors` | Bearer (paginated) |
| GET | `/devices/{id}/latest` | Bearer (latest row per sensor `type`) |

### Flutter login example

```dart
final res = await http.post(
  Uri.parse('$baseUrl/api/v1/iot/auth/login'),
  headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
  body: jsonEncode({'email': email, 'password': password, 'device_name': 'flutter-app'}),
);
final token = jsonDecode(res.body)['token'];
```

Use `Authorization: Bearer $token` on subsequent calls.

## Ingestion & idempotency

`php artisan iot:mqtt-subscribe` subscribes with **QoS 1** and dispatches:

- `ProcessComponentSetJob`
- `ProcessSensorDataJob`
- `ProcessDeviceStatusJob`

`App\Services\Iot\IotMessageIdempotency` uses Redis `SET key NX EX` so duplicate deliveries are ignored when Redis is available.

## ESP32 (Arduino / C++) sketch outline

```cpp
// Pseudocode: use PubSubClient or async-mqtt-client + WiFi
const char* mqtt_host = "192.168.1.10"; // or emqx hostname
const uint16_t mqtt_port = 1883;
const char* mqtt_user = "<mqtt_username>";   // from backend
const char* mqtt_pass = "<jwt_from_backend>"; // JWT as password

void onMqttMessage(char* topic, byte* payload, unsigned int len) {
  // Parse JSON command: action ON/OFF/TOGGLE/SET
}

void publishSensor(const char* type, const char* jsonPayload) {
  // Publish to: iot/<userId>/<deviceUuid>/sensor/<type>
}
```

Use your stack’s TLS client for production (`8883` / MQTTS).

## Automation placeholder

`App\Services\Iot\AutomationEngineStub` is intentionally empty — hook schedules / alerts there later.
