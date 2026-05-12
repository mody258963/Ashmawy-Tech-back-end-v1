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
 */

#include <WiFi.h>
#include <MQTT.h>
#include <ArduinoJson.h>

static const char *WIFI_SSID = "YOUR_WIFI_SSID";
static const char *WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

static const char *MQTT_HOST = "72.61.106.84";
static const uint16_t MQTT_PORT = 1883;

static const char *MQTT_USERNAME = "iot";
static const char *MQTT_PASSWORD = "password1234";

static const char *IOT_USER_ID = "2";
static const char *DEVICE_UUID = "20e1196d-a31e-43ef-b092-2a21851ffa2a0";
static const char *MQTT_CLIENT_ID = "dev-dgkZnvru0";


/** Must match iot_components.channel for your door lock row. */
static const int DOOR_COMPONENT_CHANNEL = 1;

static const unsigned long MQTT_FLUSH_MS = 120;

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

void handleCommand(const String &payload) {
  StaticJsonDocument<384> doc;
  if (deserializeJson(doc, payload)) {
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
  }
}

void onMessage(String &topic, String &payload) {
  if (!topic.endsWith("/set")) {
    return;
  }
  int p = topic.indexOf("/component/");
  if (p < 0) {
    return;
  }
  int a = p + 11;
  int b = topic.indexOf("/", a);
  if (b < 0) {
    return;
  }
  int ch = topic.substring(a, b).toInt();
  if (ch != DOOR_COMPONENT_CHANNEL) {
    return;
  }
  Serial.printf("[RX] set ch=%d payload=%s\n", ch, payload.c_str());
  handleCommand(payload);
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
  mqtt.onMessage(onMessage);

  unsigned long t0 = millis();
  while (!mqtt.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD) && millis() - t0 < 15000) {
    delay(300);
  }
  mqttOk = mqtt.connected();
  if (!mqttOk) {
    return;
  }

  mqtt.subscribe(topicSet(DOOR_COMPONENT_CHANNEL).c_str(), 1);
  Serial.printf("[MQTT] subscribed QoS1 %s\n", topicSet(DOOR_COMPONENT_CHANNEL).c_str());

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
