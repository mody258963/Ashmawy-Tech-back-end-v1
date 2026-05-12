# IoT components: how the backend receives and sends MQTT messages

This document explains **how Ashmawy Tech’s Laravel backend handles component topics** (`…/component/{channel}/set` and `…/component/{channel}/status`), how that relates to the **Flutter API**, **Redis**, and **MySQL**, and what must be running in production.

---

## 1. Mental model (two directions)

| Direction | Who publishes | Topic pattern | What Laravel does |
|-----------|----------------|---------------|-------------------|
| **App → device (command)** | Backend (`MqttPublisherService`) | `iot/{iot_user_id}/{device_uuid}/component/{channel}/set` | Publishes JSON after an authenticated API call (QoS 1). Device (ESP32) **subscribes** here. |
| **Device → backend (`set` echo / bridge)** | Anyone on the broker | Same `…/set` | Long-running **`iot:mqtt-subscribe`** receives it → **`ProcessComponentSetJob`** → MySQL **actions** + component **`last_state`**. |
| **Device → backend (status / ack)** | Device | `iot/{iot_user_id}/{device_uuid}/component/{channel}/status` | Subscriber → **`ProcessComponentStatusJob`** → **Redis** module snapshot → Flutter reads latest via device API. |

Important:

- **`iot_user_id`** is the primary key of **`iot_users`** (not your main app user unless they are the same conceptually).
- **`device_uuid`** must match **`iot_devices.device_uuid`** for that user.
- **`channel`** must match **`iot_components.channel`** for that device (`iot_components.iot_device_id` + `channel`).

---

## 2. End-to-end flow (Flutter locks door → ESP receives)

```mermaid
sequenceDiagram
    participant App as Flutter app
    participant API as Laravel API
    participant DB as MySQL
    participant MQTT as EMQX broker
    participant Sub as iot:mqtt-subscribe
    participant Q as Queue iot
    participant ESP as ESP32

    App->>API: POST /api/v1/iot/devices/{id}/components/{id}/action
    Note over API: auth:iot-api (Passport)
    API->>DB: Transaction: insert iot_device_actions (triggered_by=user)
    API->>MQTT: publish …/component/{ch}/set QoS 1
    MQTT->>ESP: deliver command
    Note over MQTT,Sub: Broker may also deliver same publish to Laravel subscriber (depends on broker / client id).
    MQTT->>Sub: optional duplicate copy of …/set
    Sub->>Q: dispatch ProcessComponentSetJob
    Q->>DB: insert action (triggered_by=system), update iot_components.last_state
```

Code paths:

- Route: `routes/api.php` → `POST devices/{device}/components/{component}/action`
- Controller: `App\Http\Controllers\Api\V1\Iot\ComponentController::action`
- Service: `App\Services\Iot\ComponentControlService::execute`
- MQTT publish: `App\Services\Iot\MqttPublisherService::publishComponentCommand`

Allowed actions from the API today: **`ON`**, **`OFF`**, **`TOGGLE`**, **`SET`** (see `ComponentControlService`). Custom strings such as `CLEAR_ALARM` require extending validation if you want them from the app.

---

## 3. Flow: ESP publishes component status (ack / relay state)

```mermaid
flowchart LR
    ESP[ESP32 publishes …/component/N/status]
    EMQX[EMQX broker]
    SUB[Laravel iot:mqtt-subscribe]
    Q[Queue: iot]
    JOB[ProcessComponentStatusJob]
    REDIS[(Redis module_status hash)]
    AUTO[AutomationEngineStub hook]

    ESP --> EMQX --> SUB
    SUB --> Q --> JOB
    JOB --> REDIS
    JOB --> AUTO
```

After the job runs, Flutter can load latest module snapshots from the API (device detail / realtime layer backed by Redis).

Implementation highlights:

- Subscription QoS for telemetry topics (including `…/status`) is **at most once** in `IotMqttSubscribe`; device may still publish QoS 1 — broker accepts both.
- Idempotency: **`IotMessageIdempotency`** dedupes by `sha256(topic + '|' + payload)` **after** the device row is resolved (see jobs).

---

## 4. Flow: MQTT `…/set` message picked up by Laravel subscriber

```mermaid
flowchart TD
    A[Message on iot/+/+/component/+/set]
    B[IotMqttSubscribe::routeMessage]
    C{Topic segments valid?}
    D[dispatch ProcessComponentSetJob]
    E[iot queue worker]
    F{Device exists for uuid + iot_user_id?}
    G{Component row for channel?}
    H{idempotency claim}
    I[Insert iot_device_actions triggered_by system]
    J[Update iot_components last_state]
    K[AutomationEngineStub]
    L[Skip silently]

    A --> B --> C
    C -->|no| L
    C -->|yes| D --> E
    E --> F
    F -->|no| L
    F -->|yes| G
    G -->|no| L
    G -->|yes| H
    H -->|duplicate| L
    H -->|ok| I --> J --> K
```

Source: `App\Console\Commands\IotMqttSubscribe::routeMessage` → `App\Jobs\Iot\ProcessComponentSetJob::handle`.

---

## 5. Topic parsing (how Laravel decides which job runs)

Topics must start with:

```text
iot/{iot_user_id}/{device_uuid}/...
```

Then:

| Segment pattern | Job |
|-----------------|-----|
| `component/{channel}/set` | `ProcessComponentSetJob` |
| `component/{channel}/status` | `ProcessComponentStatusJob` |
| `sensor/{type}` or `sensor/a/b` | `ProcessSensorDataJob` |
| `device/status` | `ProcessDeviceStatusJob` |

Helpers: `App\Support\Iot\IotTopic`.

---

## 6. Redis keys for component **status** (not `set`)

Latest per-channel payloads are stored under prefix **`config('iot.redis_key_prefix')`** (default `iot:v1`):

```text
{prefix}:device:{iot_device_pk}:module_status
```

Each hash field is the **channel number** (string); value is JSON with `payload`, `recorded_at`, `message_id`.

Writer: `App\Services\Iot\IotRealtimeStore::putModuleStatus`.

---

## 7. MySQL tables touched by components

| Table | When |
|-------|------|
| `iot_device_actions` | API action (`triggered_by=user`) and/or MQTT `set` job (`triggered_by=system`) |
| `iot_components` | `last_state` / `last_state_at` updated from **`ProcessComponentSetJob`** |

---

## 8. What must run on the server

```mermaid
flowchart LR
    subgraph required [Required for MQTT ingestion]
        A[php artisan iot:mqtt-subscribe]
        B[php artisan queue:work --queue=iot]
    end
    subgraph docker [Official Docker image]
        C[supervisord]
        C --- D[iot-mqtt-subscribe program]
        C --- E[laravel-queue-iot program]
    end
```

Without **both** the subscriber and a worker consuming the **`iot`** queue, messages will not become Redis/MySQL updates.

---

## 9. Reference: core PHP classes

| Role | Class |
|------|--------|
| MQTT subscribe loop | `App\Console\Commands\IotMqttSubscribe` |
| Publish commands to devices | `App\Services\Iot\MqttPublisherService` |
| API orchestration | `App\Services\Iot\ComponentControlService` |
| Jobs | `App\Jobs\Iot\ProcessComponentSetJob`, `ProcessComponentStatusJob` |
| Redis snapshots | `App\Services\Iot\IotRealtimeStore` |
| Topic strings | `App\Support\Iot\IotTopic` |

---

## 10. Payload shapes (contract)

**Backend → device (`…/set`)** — built in `MqttPublisherService`:

```json
{
  "action": "ON",
  "value": null,
  "message_id": "uuid-v4",
  "ts": "2026-05-09T12:00:00+00:00"
}
```

**Device → backend (`…/status`)** — free-form JSON stored under Redis `payload`; commonly includes `state`, `message_id`, `ts` (see project ESP integration guide).

---

This file is descriptive only; behavior follows the code in the paths above. If you change topic layouts or actions, update **`IotMqttSubscribe::routeMessage`**, **`ComponentControlService`**, and device firmware together.
