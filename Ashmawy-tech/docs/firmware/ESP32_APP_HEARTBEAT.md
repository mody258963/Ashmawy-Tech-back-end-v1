# App heartbeat (ESP32 + Flutter)

## Flow

1. Flutter: `POST /api/v1/iot/devices/{id}/app/heartbeat` with `{ "streaming": true, "ttl_seconds": 300 }`
2. Laravel publishes QoS 1 to `iot/{user}/{uuid}/app/heartbeat`
3. ESP32 (`AshmawyEsp32Hybrid.ino`) extends sensor streaming for `ttl_seconds`
4. Flutter polls `GET .../devices/{id}/latest`

When app backgrounds: `{ "streaming": false }` or let TTL expire — ESP stops periodic temperature/counter/motion.

## MQTT payload (Laravel → ESP)

```json
{
  "streaming": true,
  "ttl_seconds": 300,
  "message_id": "<uuid>",
  "ts": "2026-05-20T12:00:00+00:00"
}
```

## Critical sensors (always)

Critical events are **not** sent on `app/heartbeat`. That topic is only for **starting/stopping** periodic telemetry (server → ESP).

The ESP publishes critical data on the **same sensor topics** as normal telemetry (ESP → server):

```text
iot/{iot_user_id}/{device_uuid}/sensor/{type}
```

| Type (examples) | Topic example | When |
|-----------------|-----------------|------|
| `door_status` | `iot/1/20e1196d-a31e-43ef-b092-2a21851ffa2a/sensor/door_status` | Door open/closed changes — even if app is closed and streaming is off |
| `motion` | `.../sensor/motion` | If configured as critical (`IOT_CRITICAL_SENSOR_TYPES`) |

**Payload (QoS 0):**

```json
{"v": "door open", "seq": 42}
```

Laravel subscribes to `iot/+/+/sensor/#`, stores values in Redis, and may send **FCM push** when the app is not in foreground and the type is listed in `IOT_CRITICAL_SENSOR_TYPES` (default: `door_status`, `motion`).

## Commands (always)

ESP stays subscribed to `.../component/{channel}/set` while MQTT is connected.

## Firmware

Use [`AshmawyEsp32Hybrid/AshmawyEsp32Hybrid.ino`](./AshmawyEsp32Hybrid/AshmawyEsp32Hybrid.ino).

**Full guide for embedded engineers:** [ESP32_HYBRID_EMBEDDED_GUIDE.md](./ESP32_HYBRID_EMBEDDED_GUIDE.md)
