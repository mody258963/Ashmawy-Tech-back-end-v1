# Ashmawy IoT — Flutter mobile guide

Base URL: `{APP_URL}/api/v1/iot`  
Auth: `Authorization: Bearer <token>` from `POST /auth/login` (Passport `iot-api`).

Related: [iot-platform.md](./iot-platform.md), [firmware/ESP32_APP_HEARTBEAT.md](./firmware/ESP32_APP_HEARTBEAT.md), [firmware/AshmawyEsp32Hybrid/AshmawyEsp32Hybrid.ino](./firmware/AshmawyEsp32Hybrid/AshmawyEsp32Hybrid.ino).

---

## App heartbeat (sensor streaming)

When the user **opens** a device screen, wake the ESP32 to publish telemetry (temperature, etc.). When the app **backgrounds**, stop periodic sensors to save power and bandwidth.

| When | API |
|------|-----|
| Screen open / resumed | `POST /devices/{id}/app/heartbeat` `{ "streaming": true, "ttl_seconds": 300 }` |
| Every 2–3 min while visible | Repeat heartbeat |
| App paused / closed | `POST .../app/heartbeat` `{ "streaming": false }` or let TTL expire |
| Read sensors | `GET /devices/{id}/latest` every 10–15s while foreground |

**Do not** use device MQTT JWT as the API Bearer token.

### Example

```dart
Future<void> startDeviceStreaming(int deviceId, String bearer) async {
  await http.post(
    Uri.parse('$baseUrl/v1/iot/devices/$deviceId/app/heartbeat'),
    headers: {
      'Authorization': 'Bearer $bearer',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'streaming': true, 'ttl_seconds': 300}),
  );
}
```

---

## Door lock / commands (always work)

`POST /devices/{id}/components/{componentId}/action` works **without** app heartbeat. ESP stays on MQTT for `.../component/{ch}/set`.

Handle `200` / `504` / `422` per ACK contract in [iot-platform.md](./iot-platform.md).

---

## Push notifications (FCM)

1. Flutter: `firebase_messaging` → get FCM token.
2. `POST /api/v1/iot/push-tokens` `{ "token": "...", "platform": "android" | "ios" }`
3. When app is closed and door opens, server sends push (if `FCM_*` configured).

Critical sensor types: `door_status`, `motion` (configurable via `IOT_CRITICAL_SENSOR_TYPES`).

---

## Device id

Use **`iot_devices.id`** from `GET /devices` (e.g. `2`), not `iot_user_id`.

---

## Postman

Import [`postman/Ashmawy-Iot-Flutter-API.postman_collection.json`](../postman/Ashmawy-Iot-Flutter-API.postman_collection.json).
