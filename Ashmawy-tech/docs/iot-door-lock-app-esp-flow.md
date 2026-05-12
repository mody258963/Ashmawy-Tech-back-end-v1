# Door lock: app → backend (QoS 1) → ESP32 → status back → API

This describes a **minimal demo** (no GPIO): the Flutter app calls your REST API; Laravel publishes an MQTT command at **QoS 1**; the ESP32 receives it and publishes **status** so you can read the door state from another endpoint.

---

## 1. What you configure once

| Item | Where |
|------|--------|
| Device row | `iot_devices`: `iot_user_id`, `device_uuid` (must match MQTT topic). |
| Door “actuator” | **Option A:** `POST /api/v1/iot/devices/{device_pk}/components` (creates `iot_components`). **Option B:** insert manually in DB / tinker. |
| API path id | URL uses **`iot_devices.id`** (integer), **not** `iot_user_id`. |
| Component id in `/action` | URL uses **`iot_components.id`** (primary key from list/create). **MQTT** still uses **`iot_components.channel`** in the topic. |

Example: device **`id = 2`**, door component **`id = 5`**, **`channel = 1`**.

### Create component (API)

```http
POST /api/v1/iot/devices/2/components
Authorization: Bearer {iot_access_token}
Content-Type: application/json

{
  "name": "Front door lock",
  "type": "lock",
  "channel": 1,
  "metadata": null
}
```

Response **`201`**: body includes **`data.id`** — use that as **`iot_component_id`** in:

`POST /api/v1/iot/devices/2/components/{data.id}/action`

`type` must be one of: `switch`, `dimmer`, `motor`, `sensor`, `lock`, `valve`, `hvac`, `generic`. **`channel`** must be unique per device (1–255).

---

## 2. Open the door from the app (QoS 1)

**Request**

```http
POST /api/v1/iot/devices/2/components/5/action
Authorization: Bearer {iot_access_token}
Content-Type: application/json

{"action":"OFF"}
```

**Allowed actions** (validated): `ON`, `OFF`, `TOGGLE`, `SET`  
Demo mapping on the ESP:

| `action` | Meaning in demo |
|----------|------------------|
| `OFF` | Unlock / door open (`locked = false`) |
| `ON` | Lock (`locked = true`) |
| `TOGGLE` | Flip lock state |

**What Laravel does** (`ComponentControlService` → `MqttPublisherService`):

1. Inserts **`iot_device_actions`** (`triggered_by = user`).
2. Publishes to MQTT (QoS 1):

   `iot/{iot_user_id}/{device_uuid}/component/{channel}/set`

   Payload shape:

   ```json
   {
     "action": "OFF",
     "value": null,
     "message_id": "uuid-v4",
     "ts": "2026-05-11T12:00:00+00:00"
   }
   ```

The **`channel`** in the topic is **`iot_components.channel`** (e.g. `1`), not the component primary key.

---

## 3. What the ESP32 demo does

Firmware path: `docs/firmware/AshmawyEsp32DoorLockDemo/AshmawyEsp32DoorLockDemo.ino`

1. **Subscribe QoS 1** to `.../component/1/set` (change `DOOR_COMPONENT_CHANNEL` if needed).
2. On each message: parse `action`, update simulated `doorLocked`, then **publish QoS 1** to:

   `iot/{iot_user_id}/{device_uuid}/component/1/status`

   Example payload:

   ```json
   {
     "state": "UNLOCKED",
     "locked": false,
     "door_open": true,
     "message_id": "<same as command if present>",
     "ts": "1970-01-01T00:00:00Z"
   }
   ```

3. **`mqtt.loop()`** + short flush after publish so QoS 1 ACKs complete (same idea as the larger IoT sketch).

---

## 4. How the backend ingests status (for the app to read)

```mermaid
sequenceDiagram
    participant App
    participant API as Laravel API
    participant DB as MySQL
    participant EMQX
    participant Sub as iot:mqtt-subscribe
    participant Q as queue iot
    participant Job as ProcessComponentStatusJob
    participant Redis

    App->>API: POST .../components/{id}/action OFF
    API->>DB: insert iot_device_actions
    API->>EMQX: PUBLISH .../component/1/set QoS 1
    EMQX->>Sub: deliver
    Sub->>Q: dispatch ProcessComponentSetJob (same topic pattern)
    Note over Sub,Q: Optional: records echo in DB; device still acts on same message from broker

    EMQX->>ESP: deliver .../set
    ESP->>EMQX: PUBLISH .../component/1/status QoS 1
    EMQX->>Sub: deliver status
    Sub->>Q: dispatch ProcessComponentStatusJob
    Job->>Redis: module hash channel 1 snapshot

    App->>API: GET .../devices/2
    API->>Redis: realtime snapshot
    API-->>App: JSON includes realtime.modules["1"]
```

Notes:

- **`ProcessComponentSetJob`** also runs when the **subscriber** sees `.../set` (duplicate path vs API insert — idempotency + your product rules apply).
- **Live door UI** should read **`GET /api/v1/iot/devices/{device_id}`** → **`realtime.modules`**: each key is the **channel** string (`"1"`), value includes `payload`, `recorded_at`, `message_id`.

There is **no dedicated “door status only”** route in the current API beyond that snapshot (and optional web dashboard). If you want `GET .../components/{id}/status`, that would be a new small endpoint reading Redis by component channel.

---

## 5. Quick checklist if the app shows nothing

1. **`iot:mqtt-subscribe`** and **`queue:work --queue=iot`** running on the server that shares Redis with the API.
2. Topic **`iot_user_id` + `device_uuid`** match **`iot_devices`**.
3. **`iot_components.channel`** equals the segment in MQTT (e.g. `1`).
4. EMQX **ACL** allows the **bridge** user to subscribe to `iot/+/+/component/+/status`.

---

## 6. Files in this repo

| File | Role |
|------|------|
| `docs/firmware/AshmawyEsp32DoorLockDemo/AshmawyEsp32DoorLockDemo.ino` | ESP32 demo (no pins) |
| `app/Services/Iot/ComponentControlService.php` | API → DB + MQTT publish |
| `app/Services/Iot/MqttPublisherService.php` | QoS 1 publish to `.../set` |
| `app/Jobs/Iot/ProcessComponentStatusJob.php` | `.../status` → Redis |
| `app/Http/Controllers/Api/V1/Iot/DeviceController.php` | `show()` adds `realtime` |
