/**
 * Door lock demo — ESP32 (Arduino IDE), no GPIO
 *
 * Libraries: "MQTT" (256dpi), "ArduinoJson" v6
 *
 * Contract (matches Ashmawy backend):
 *   - App: POST /api/v1/iot/devices/{id}/components/{component_id}/action
 *         body: { "action": "OFF" }  → unlock / open door (QoS 1 publish from Laravel)
 *         body: { "action": "ON" }   → lock door
 *   - ESP: subscribes QoS 1 to  iot/{iot_user_id}/{device_uuid}/component/{channel}/set
 *   - ESP: publishes QoS 1 to     .../component/{channel}/status  (ack + current state → Redis)
 *
 * Create in DB: one component on the device with channel = DOOR_COMPONENT_CHANNEL (default 1).
 *
 * Uses onMessageAdvanced + deserializeJson(buf, len) so inbound JSON is not dependent on
 * null-terminated payload quirks. Keeps the subscribe topic in a stable buffer (not a
 * temporary String::c_str()). After subscribe, spins mqtt.loop() briefly for SUBACK/PUBLISH.
 */

#include <WiFi.h>
#include <MQTT.h>
#include <ArduinoJson.h>
#include <cstring>

static const char *WIFI_SSID = "YOUR_WIFI_SSID";
static const char *WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

static const char *MQTT_HOST = "72.61.106.84";
static const uint16_t MQTT_PORT = 1883;

static const char *MQTT_USERNAME = "back-end";
static const char *MQTT_PASSWORD = "password1234";

/** Copy from GET /api/v1/iot/devices/{id} → numeric `iot_users.id` (not repair-shop user id). */
static const char *IOT_USER_ID = "2";
/** Copy exact `device_uuid` from the same API response (36 chars; must match DB or Laravel publishes elsewhere). */
static const char *DEVICE_UUID = "20e1196d-a31e-43ef-b092-2a21851ffa2a";
static const char *MQTT_CLIENT_ID = "dev-dgkZnvru0";


/** Must match iot_components.channel for your door lock row. */
static const int DOOR_COMPONENT_CHANNEL = 1;

static const unsigned long MQTT_FLUSH_MS = 120;

/** Stable buffer for exact subscribe topic (avoid temporary String + c_str()). */
static char gSetTopicBuf[160];

WiFiClient net;
MQTTClient mqtt(16384);

bool wifiOk = false;
bool mqttOk = false;
unsigned long lastMqttTry = 0;

/** Simulated latch: true = locked (bolt engaged), false = unlocked (door can open). */
bool doorLocked = true;

String topicBase() {
  return String("iot/") + IOT_USER_ID + "/" + DEVICE_UUID;
}

String topicSet(int ch) {
  return topicBase() + "/component/" + String(ch) + "/set";
}

String topicStatus(int ch) {
  return topicBase() + "/component/" + String(ch) + "/status";
}

void mqttFlush() {
  unsigned long t = millis();
  while (millis() - t < MQTT_FLUSH_MS) {
    mqtt.loop();
    delay(2);
  }
}

bool publishQos1(const char *topic, JsonDocument &doc) {
  char buf[512];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n == 0 || n >= sizeof(buf)) {
    return false;
  }
  bool ok = mqtt.publish(topic, buf, (int)n, false, 1);
  mqttFlush();
  return ok;
}

void publishDoorStatus(const char *state, const char *messageId) {
  StaticJsonDocument<384> doc;
  doc["state"] = state;
  doc["locked"] = doorLocked;
  doc["door_open"] = !doorLocked;
  if (messageId && messageId[0]) {
    doc["message_id"] = messageId;
  }
  doc["ts"] = "1970-01-01T00:00:00Z";
  const String t = topicStatus(DOOR_COMPONENT_CHANNEL);
  if (publishQos1(t.c_str(), doc)) {
    Serial.printf("[TX] status -> %s\n", t.c_str());
  } else {
    Serial.printf("[TX] FAIL status -> %s\n", t.c_str());
  }
}

void handleCommand(const char *bytes, int length) {
  StaticJsonDocument<384> doc;
  DeserializationError err = deserializeJson(doc, bytes, (size_t)length);
  if (err) {
    Serial.printf("[RX] JSON parse error: %s (len=%d)\n", err.c_str(), length);
    return;
  }

  if (!doc.containsKey("action")) {
    Serial.println("[RX] missing action key");
    return;
  }

  String action = doc["action"].as<String>();
  action.toUpperCase();

  String mid = doc["message_id"].isNull() ? String("") : doc["message_id"].as<String>();
  const char *midc = mid.length() ? mid.c_str() : "";

  if (action == "ON") {
    doorLocked = true;
    publishDoorStatus("LOCKED", midc);
    Serial.println("[APP] ON -> locked");
    return;
  }
  if (action == "OFF") {
    doorLocked = false;
    publishDoorStatus("UNLOCKED", midc);
    Serial.println("[APP] OFF -> unlocked (door open)");
    return;
  }
  if (action == "TOGGLE") {
    doorLocked = !doorLocked;
    publishDoorStatus(doorLocked ? "LOCKED" : "UNLOCKED", midc);
    Serial.println("[APP] TOGGLE");
    return;
  }
  if (action == "SET") {
    publishDoorStatus(doorLocked ? "LOCKED" : "UNLOCKED", midc);
    Serial.println("[APP] SET (state echo)");
    return;
  }

  Serial.printf("[RX] unknown action: %s\n", action.c_str());
}

/**
 * Advanced handler: topic/payload are raw; avoids String-only callback edge cases on some cores.
 */
void onMessageAdvanced(MQTTClient * /*client*/, char topic[], char bytes[], int length) {
  Serial.printf("[RX] topic=%s bytes=%d\n", topic, length);

  const char *setSuffix = strstr(topic, "/set");
  if (setSuffix == nullptr || strcmp(setSuffix, "/set") != 0) {
    return;
  }

  const char *comp = strstr(topic, "/component/");
  if (comp == nullptr) {
    return;
  }
  comp += 11;
  const char *slash = strchr(comp, '/');
  if (slash == nullptr) {
    return;
  }
  int ch = 0;
  for (const char *p = comp; p < slash; p++) {
    if (*p < '0' || *p > '9') {
      return;
    }
    ch = ch * 10 + (*p - '0');
  }
  if (ch != DOOR_COMPONENT_CHANNEL) {
    Serial.printf("[RX] channel %d != DOOR_COMPONENT_CHANNEL %d\n", ch, DOOR_COMPONENT_CHANNEL);
    return;
  }

  if (bytes == nullptr || length <= 0) {
    Serial.println("[RX] empty payload");
    return;
  }

  handleCommand(bytes, length);
}

void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    wifiOk = true;
    return;
  }
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) {
    delay(200);
  }
  wifiOk = (WiFi.status() == WL_CONNECTED);
}

void connectMqtt() {
  if (!wifiOk) {
    return;
  }
  mqtt.begin(MQTT_HOST, MQTT_PORT, net);
  mqtt.setKeepAlive(60);
  mqtt.setTimeout(5000);
  mqtt.onMessageAdvanced(onMessageAdvanced);

  unsigned long t0 = millis();
  while (!mqtt.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD) && millis() - t0 < 15000) {
    delay(300);
  }
  mqttOk = mqtt.connected();
  if (!mqttOk) {
    Serial.printf("[MQTT] connect failed lastError=%d returnCode=%d\n",
                  (int)mqtt.lastError(), (int)mqtt.returnCode());
    return;
  }

  const String setTopic = topicSet(DOOR_COMPONENT_CHANNEL);
  if (setTopic.length() + 1 > sizeof(gSetTopicBuf)) {
    Serial.println("[MQTT] set topic too long for gSetTopicBuf");
    mqttOk = false;
    return;
  }
  strncpy(gSetTopicBuf, setTopic.c_str(), sizeof(gSetTopicBuf) - 1);
  gSetTopicBuf[sizeof(gSetTopicBuf) - 1] = '\0';

  if (!mqtt.subscribe(gSetTopicBuf, 1)) {
    Serial.printf("[MQTT] subscribe failed lastError=%d\n", (int)mqtt.lastError());
    mqttOk = false;
    return;
  }
  Serial.printf("[MQTT] subscribed QoS1 %s\n", gSetTopicBuf);

  // Process SUBACK and any queued inbound packets before first publishes.
  for (int i = 0; i < 80; i++) {
    mqtt.loop();
    delay(5);
  }

  StaticJsonDocument<192> online;
  online["status"] = "online";
  online["fw"] = "door-lock-demo";
  String devStatusTopic = topicBase() + "/device/status";
  publishQos1(devStatusTopic.c_str(), online);

  publishDoorStatus(doorLocked ? "LOCKED" : "UNLOCKED", "");
}

void setup() {
  Serial.begin(115200);
  delay(400);
  connectWifi();
  if (wifiOk) {
    connectMqtt();
  }
}

void loop() {
  if (!wifiOk || WiFi.status() != WL_CONNECTED) {
    wifiOk = false;
    mqttOk = false;
    connectWifi();
    delay(500);
    return;
  }
  if (!mqttOk || !mqtt.connected()) {
    mqttOk = false;
    if (millis() - lastMqttTry > 3000) {
      lastMqttTry = millis();
      connectMqtt();
    }
    delay(100);
    return;
  }
  mqtt.loop();
}
