/**
 * Ashmawy IoT — ESP32 (Arduino IDE) — MQTT only, no GPIO pins defined
 *
 * Libraries (Library Manager):
 *   - "MQTT" by Joel Gaehwiler (256dpi)
 *   - "ArduinoJson" by Benoit Blanchon (v6)
 *
 * Topics: iot/{IOT_USER_ID}/{DEVICE_UUID}/...
 *
 * Door / temperature here are software demos (no wiring). Add your own pins + digitalRead later.
 *
 * Data to broker (QoS 1):
 *   - On MQTT connect: device/status + sensor/temperature + sensor/telemetry
 *   - Every TELEMETRY_INTERVAL_MS: temperature + telemetry
 *   - When simulated door state changes: sensor/door
 */

#include <WiFi.h>
#include <MQTT.h>
#include <ArduinoJson.h>

// ---------------------------------------------------------------------------
// Wi-Fi
// ---------------------------------------------------------------------------
static const char *WIFI_SSID = "YOUR_WIFI_SSID";
static const char *WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// ---------------------------------------------------------------------------
// MQTT / EMQX (device auth: username + password = device JWT from backend)
// ---------------------------------------------------------------------------
static const char *MQTT_HOST = "72.61.106.84";
static const uint16_t MQTT_PORT = 1883;

static const char *MQTT_USERNAME = "iot";
static const char *MQTT_PASSWORD = "password1234";

static const char *IOT_USER_ID = "2";
static const char *DEVICE_UUID = "20e1196d-a31e-43ef-b092-2a21851ffa2a0";
static const char *MQTT_CLIENT_ID = "dev-dgkZnvru0";

static const int CH_LOCK = 1;
static const int CH_ALARM_CLEAR = 2;

// ---------------------------------------------------------------------------
// Timing
// ---------------------------------------------------------------------------
static const unsigned long TELEMETRY_INTERVAL_MS = 5000;
static const unsigned long MQTT_RECONNECT_DELAY_MS = 3000;

WiFiClient net;
/** Larger buffer helps QoS 1 bursts (device/status + temperature + telemetry). */
MQTTClient mqtt(32768);

bool wifiConnected = false;
bool mqttConnected = false;

bool lockedFromApp = false;
bool alarmLatched = false;

unsigned long lastTelemetryMs = 0;
unsigned long lastMqttAttemptMs = 0;
unsigned long bootMillis = 0;
uint32_t publishSequence = 0;

// ---------------------------------------------------------------------------
// Software-only sensors (no pins)
// ---------------------------------------------------------------------------
bool doorSimulatedOpen() {
  return ((millis() / 25000UL) & 1U) != 0;
}

float temperatureSimulatedC() {
  uint32_t t = millis();
  return 20.0f + (float)(t % 7000) / 700.0f;
}

// ---------------------------------------------------------------------------
// Topic builders
// ---------------------------------------------------------------------------
String topicBase() {
  String s = "iot/";
  s += IOT_USER_ID;
  s += "/";
  s += DEVICE_UUID;
  return s;
}

String topicComponentSet(int channel) {
  return topicBase() + "/component/" + String(channel) + "/set";
}

String topicComponentStatus(int channel) {
  return topicBase() + "/component/" + String(channel) + "/status";
}

String topicSensor(const char *sensorType) {
  return topicBase() + "/sensor/" + String(sensorType);
}

String topicDeviceStatus() {
  return topicBase() + "/device/status";
}

/** Let the client process outgoing QoS 1 ACKs / TCP — call after every publish. */
void mqttFlush() {
  for (int i = 0; i < 30; i++) {
    mqtt.loop();
    delay(3);
  }
}

bool mqttPublishQos1(const char *label, const String &topic, const JsonDocument &doc) {
  char buf[768];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n == 0 || n >= sizeof(buf)) {
    Serial.printf("[MQTT] skip %s: JSON too large\n", label);
    return false;
  }
  const char *tpc = topic.c_str();
  int len = (int)n;

  bool ok = mqtt.publish(tpc, buf, len, false, 1);
  mqttFlush();
  if (!ok) {
    ok = mqtt.publish(tpc, buf, len, false, 1);
    mqttFlush();
  }

  if (ok) {
    Serial.printf("[MQTT] QoS1 ok %s -> %s (%u bytes)\n", label, tpc, (unsigned)n);
  } else {
    Serial.printf("[MQTT] QoS1 FAIL %s -> %s (check WiFi / broker / keep mqtt.loop)\n", label, tpc);
  }
  return ok;
}

void publishDeviceOnline() {
  StaticJsonDocument<256> doc;
  doc["status"] = "online";
  doc["fw"] = "1.0.0";
  doc["uptime_ms"] = (uint32_t)(millis() - bootMillis);
  doc["rssi_dbm"] = (int)WiFi.RSSI();
  doc["simulation"] = true;
  mqttPublishQos1("device/status", topicDeviceStatus(), doc);
}

void publishTemperatureSensor(float tempC) {
  StaticJsonDocument<192> doc;
  doc["value"] = static_cast<double>(tempC);
  doc["unit"] = "C";
  doc["seq"] = ++publishSequence;
  doc["simulation"] = true;
  doc["ts"] = "1970-01-01T00:00:00Z";
  mqttPublishQos1("sensor/temperature", topicSensor("temperature"), doc);
}

void publishTelemetryBundle(float tempC) {
  StaticJsonDocument<384> doc;
  doc["seq"] = (int)++publishSequence;
  doc["uptime_ms"] = (uint32_t)(millis() - bootMillis);
  doc["rssi_dbm"] = (int)WiFi.RSSI();
  doc["door_open"] = doorSimulatedOpen();
  doc["locked"] = lockedFromApp;
  doc["alarm"] = alarmLatched;
  doc["temp_c"] = static_cast<double>(tempC);
  doc["simulation"] = true;
  doc["ts"] = "1970-01-01T00:00:00Z";
  mqttPublishQos1("sensor/telemetry", topicSensor("telemetry"), doc);
}

void publishFullDataSnapshot() {
  float t = temperatureSimulatedC();
  publishTemperatureSensor(t);
  mqttFlush();
  publishTelemetryBundle(t);
}

void publishDoorSensorEdge(bool openNow) {
  StaticJsonDocument<192> doc;
  doc["open"] = openNow;
  doc["seq"] = (int)++publishSequence;
  doc["simulation"] = true;
  doc["ts"] = "1970-01-01T00:00:00Z";
  mqttPublishQos1("sensor/door", topicSensor("door"), doc);
}

void publishLockStatus(const char *state, const char *messageId) {
  StaticJsonDocument<256> doc;
  doc["state"] = state;
  doc["locked_from_app"] = lockedFromApp;
  if (messageId && messageId[0]) {
    doc["message_id"] = messageId;
  }
  doc["simulation"] = true;
  doc["ts"] = "1970-01-01T00:00:00Z";
  mqttPublishQos1("component/lock_status", topicComponentStatus(CH_LOCK), doc);
}

void publishAlarmStatus(bool on, const char *reason, const char *messageId) {
  StaticJsonDocument<256> doc;
  doc["alarm"] = on;
  doc["reason"] = reason;
  if (messageId && messageId[0]) {
    doc["message_id"] = messageId;
  }
  doc["simulation"] = true;
  doc["ts"] = "1970-01-01T00:00:00Z";
  mqttPublishQos1("component/alarm_status", topicComponentStatus(CH_ALARM_CLEAR), doc);
}

void handleSetMessage(int channel, const String &payload) {
  StaticJsonDocument<512> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    return;
  }

  String action = doc["action"].as<String>();
  action.toUpperCase();

  String midStr = doc["message_id"].isNull() ? String("") : doc["message_id"].as<String>();
  const char *mid = midStr.length() ? midStr.c_str() : "";

  if (channel == CH_LOCK) {
    if (action == "ON" || action == "LOCK") {
      lockedFromApp = true;
      publishLockStatus("LOCKED", mid);
      return;
    }
    if (action == "OFF" || action == "UNLOCK") {
      lockedFromApp = false;
      alarmLatched = false;
      publishLockStatus("UNLOCKED", mid);
      publishAlarmStatus(false, "cleared_by_unlock", mid);
      return;
    }
  }

  if (channel == CH_ALARM_CLEAR) {
    if (action == "OFF" || action == "CLEAR_ALARM" || action == "RESET") {
      alarmLatched = false;
      publishAlarmStatus(false, "cleared_from_app", mid);
      publishLockStatus(lockedFromApp ? "LOCKED" : "UNLOCKED", mid);
    }
  }
}

void onMqttMessage(String &topic, String &payload) {
  if (!topic.endsWith("/set")) {
    return;
  }
  int i = topic.indexOf("/component/");
  if (i < 0) {
    return;
  }
  int start = i + 11;
  int slash = topic.indexOf("/", start);
  if (slash < 0) {
    return;
  }
  int channel = topic.substring(start, slash).toInt();
  handleSetMessage(channel, payload);
}

void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    return;
  }
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 25000) {
    delay(200);
  }
  wifiConnected = (WiFi.status() == WL_CONNECTED);
}

void connectMqtt() {
  if (!wifiConnected) {
    return;
  }

  mqtt.begin(MQTT_HOST, MQTT_PORT, net);
  mqtt.onMessage(onMqttMessage);

  unsigned long t0 = millis();
  while (!mqtt.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD) && millis() - t0 < 15000) {
    delay(300);
  }

  mqttConnected = mqtt.connected();
  if (!mqttConnected) {
    return;
  }

  mqtt.subscribe(topicComponentSet(CH_LOCK).c_str(), 1);
  mqtt.subscribe(topicComponentSet(CH_ALARM_CLEAR).c_str(), 1);

  publishDeviceOnline();
  publishFullDataSnapshot();
}

void setup() {
  Serial.begin(115200);
  delay(500);
  bootMillis = millis();

  connectWifi();
  if (wifiConnected) {
    connectMqtt();
  }

  lastTelemetryMs = millis();
}

static bool prevDoorOpen = false;

void loop() {
  if (!wifiConnected || WiFi.status() != WL_CONNECTED) {
    wifiConnected = false;
    mqttConnected = false;
    connectWifi();
    delay(500);
    return;
  }

  if (!mqttConnected || !mqtt.connected()) {
    mqttConnected = false;
    if (millis() - lastMqttAttemptMs >= MQTT_RECONNECT_DELAY_MS) {
      lastMqttAttemptMs = millis();
      connectMqtt();
    }
    delay(200);
    return;
  }

  mqtt.loop();

  bool openNow = doorSimulatedOpen();
  if (openNow != prevDoorOpen) {
    prevDoorOpen = openNow;
    publishDoorSensorEdge(openNow);
  }

  if (lockedFromApp && openNow && !alarmLatched) {
    alarmLatched = true;
    publishAlarmStatus(true, "door_open_while_locked", "");
  }

  if (millis() - lastTelemetryMs >= TELEMETRY_INTERVAL_MS) {
    lastTelemetryMs = millis();
    publishFullDataSnapshot();
  }
}
