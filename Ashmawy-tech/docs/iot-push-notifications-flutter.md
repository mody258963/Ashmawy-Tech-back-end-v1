# Ashmawy IoT — Push notifications (Flutter)

Guide for the **mobile app**: Firebase Cloud Messaging (FCM), backend registration, and handling **critical alerts** from the MQTT broker.

Related: [iot-mobile-app-guide.md](./iot-mobile-app-guide.md), [iot-platform.md](./iot-platform.md).

---

## Overview

| Step | Who |
|------|-----|
| ESP publishes critical sensor on MQTT | Device firmware |
| EMQX → Laravel `iot:mqtt-subscribe` | Backend |
| Critical change + app in background | `IotCriticalAlertService` |
| FCM push to phone | `SendIotAlertPushJob` |

The app does **not** connect to MQTT for alerts. It only needs FCM + the REST API.

---

## 1. Firebase project (one-time)

1. [Firebase Console](https://console.firebase.google.com) → create/select project.
2. Add **Android** and/or **iOS** app (package name / bundle id must match Flutter).
3. Download:
   - **Android:** `google-services.json` → `android/app/`
   - **iOS:** `GoogleService-Info.plist` → `ios/Runner/`
4. Enable **Cloud Messaging** in project settings.

Backend FCM credentials (`FCM_PROJECT_ID`, service account JSON) are configured on the server only — see [iot-platform.md](./iot-platform.md#critical-alerts-mqtt-broker--fcm-push).

---

## 2. Flutter dependencies

```yaml
dependencies:
  firebase_core: ^3.0.0
  firebase_messaging: ^15.0.0
  flutter_local_notifications: ^18.0.0  # optional: show foreground alerts
  http: ^1.0.0
```

Initialize Firebase in `main()`:

```dart
WidgetsFlutterBinding.ensureInitialized();
await Firebase.initializeApp();
```

---

## 3. Register FCM token with the API

After login (`POST /api/v1/iot/auth/login`), send the device token to the backend.

**Endpoint:** `POST /api/v1/iot/push-tokens`  
**Auth:** `Authorization: Bearer <iot-api-access-token>`  
**Body:**

```json
{
  "token": "<fcm-registration-token>",
  "platform": "android"
}
```

`platform` must be `"android"` or `"ios"`.

### Example

```dart
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:io';

Future<void> registerPushToken(String apiBearer, String baseUrl) async {
  final messaging = FirebaseMessaging.instance;

  if (Platform.isIOS) {
    await messaging.requestPermission(alert: true, badge: true, sound: true);
  }

  final token = await messaging.getToken();
  if (token == null) return;

  final platform = Platform.isIOS ? 'ios' : 'android';

  await http.post(
    Uri.parse('$baseUrl/v1/iot/push-tokens'),
    headers: {
      'Authorization': 'Bearer $apiBearer',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({'token': token, 'platform': platform}),
  );

  // Token can rotate — re-register when it changes
  FirebaseMessaging.instance.onTokenRefresh.listen((newToken) async {
    await http.post(
      Uri.parse('$baseUrl/v1/iot/push-tokens'),
      headers: {
        'Authorization': 'Bearer $apiBearer',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'token': newToken, 'platform': platform}),
    );
  });
}
```

Call `registerPushToken` after successful login and on app start if already logged in.

---

## 4. Unregister on logout

**Endpoint:** `DELETE /api/v1/iot/push-tokens`  
**Body:** `{ "token": "<same-fcm-token>" }`

```dart
Future<void> unregisterPushToken(String apiBearer, String baseUrl) async {
  final token = await FirebaseMessaging.instance.getToken();
  if (token == null) return;

  await http.delete(
    Uri.parse('$baseUrl/v1/iot/push-tokens'),
    headers: {
      'Authorization': 'Bearer $apiBearer',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({'token': token}),
  );
}
```

---

## 5. When the server sends a push

A push is sent when **all** of these are true:

1. Sensor type is **critical** (admin “Critical alert” on the site, or type in server `IOT_CRITICAL_SENSOR_TYPES`, e.g. `door_status`, `motion`).
2. Value is an **alert state** (e.g. door open, motion `true`).
3. App is **not** streaming that device (`POST .../app/heartbeat` with `streaming: false` or TTL expired).

While the user has the device screen open with `streaming: true`, the server **suppresses** push (live data via `GET /devices/{id}/latest` instead).

---

## 6. FCM payload (data)

| Field | Example | Meaning |
|-------|---------|---------|
| `type` | `critical_alert` | Open device / alert UI |
| `device_id` | `2` | `iot_devices.id` (use in API URLs) |
| `sensor_type` | `door_status` | MQTT sensor segment |

Notification tray (visible to user):

- **title:** device name  
- **body:** e.g. `Door: door open`, `Motion detected`

### Android notification channel (recommended)

Create a high-importance channel for critical alerts:

```dart
const AndroidNotificationChannel criticalChannel = AndroidNotificationChannel(
  'critical_alerts',
  'Critical alerts',
  description: 'Door, motion, and other security alerts',
  importance: Importance.high,
);
```

---

## 7. Handle messages in Flutter

### Background / terminated (required top-level handler)

```dart
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  // Persist or route via local notification plugin if needed
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  runApp(const MyApp());
}
```

### Foreground + tap

```dart
void setupPushHandlers(void Function(String deviceId, String sensorType) onCriticalAlert) {
  FirebaseMessaging.onMessage.listen((RemoteMessage message) {
    final data = message.data;
    if (data['type'] == 'critical_alert') {
      onCriticalAlert(data['device_id'] ?? '', data['sensor_type'] ?? '');
    }
  });

  FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
    final data = message.data;
    if (data['type'] == 'critical_alert') {
      onCriticalAlert(data['device_id'] ?? '', data['sensor_type'] ?? '');
    }
  });
}
```

Navigate to the device screen using `device_id` from `GET /devices`.

### Cold start (app opened from notification)

```dart
final initial = await FirebaseMessaging.instance.getInitialMessage();
if (initial?.data['type'] == 'critical_alert') {
  // navigate to device initial.data['device_id']
}
```

---

## 8. App heartbeat vs push (important)

| User action | Call |
|-------------|------|
| Open device screen | `POST /devices/{id}/app/heartbeat` `{ "streaming": true, "ttl_seconds": 300 }` |
| Leave screen / background app | `{ "streaming": false }` or stop heartbeat |
| Poll live sensors (foreground) | `GET /devices/{id}/latest` every 10–15s |

If you forget `streaming: false` on background, critical pushes may be **blocked** while the session TTL is active.

---

## 9. Testing checklist

1. Log in on a physical device (simulator FCM is limited).
2. Confirm `POST /push-tokens` returns `{ "message": "ok" }`.
3. Background the app or call `streaming: false`.
4. Trigger critical MQTT (e.g. open door → `door_status` on broker).
5. Verify notification; tap opens correct device.
6. Open device screen with `streaming: true` → repeat trigger → **no** push (expected).

---

## 10. Postman

Collection: [`postman/Ashmawy-Iot-Flutter-API.postman_collection.json`](../postman/Ashmawy-Iot-Flutter-API.postman_collection.json) → folder **IoT — Push tokens**.

---

## API summary

| Method | Path | Purpose |
|--------|------|---------|
| `POST` | `/api/v1/iot/push-tokens` | Register FCM token |
| `DELETE` | `/api/v1/iot/push-tokens` | Remove token on logout |
| `POST` | `/api/v1/iot/devices/{id}/app/heartbeat` | Foreground streaming (suppresses push) |
| `GET` | `/api/v1/iot/devices/{id}/latest` | Live sensor snapshot (Redis) |
