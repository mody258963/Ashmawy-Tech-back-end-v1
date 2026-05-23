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

`door_status` (and configured types) publish **immediately** on change even when `streaming` is false — used for FCM push when app is closed.

## Commands (always)

ESP stays subscribed to `.../component/{channel}/set` while MQTT is connected.

## Firmware

Use [`AshmawyEsp32Hybrid/AshmawyEsp32Hybrid.ino`](./AshmawyEsp32Hybrid/AshmawyEsp32Hybrid.ino).

**Full guide for embedded engineers:** [ESP32_HYBRID_EMBEDDED_GUIDE.md](./ESP32_HYBRID_EMBEDDED_GUIDE.md)
