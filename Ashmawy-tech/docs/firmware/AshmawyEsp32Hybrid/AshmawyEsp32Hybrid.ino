/**
 * Ashmawy ESP32 Hybrid — door lock + on-demand sensors + critical alerts.
 *
 * - MQTT + WiFi stay connected (commands work anytime).
 * - Periodic sensors (temperature, counter, motion) only while app heartbeat TTL active.
 * - door_status publishes immediately on change (even when app closed) for push alerts.
 * - Subscribes: .../app/heartbeat (QoS1), .../component/{ch}/set (QoS1)
 *
 * Libraries: MQTT (256dpi), ArduinoJson v6
 */

#include <WiFi.h>
#include <MQTT.h>
#include <ArduinoJson.h>
#include <cstring>

static const char *WIFI_SSID = "CirkitWifi";
static const char *WIFI_PASSWORD = "";

static const char *MQTT_HOST = "72.61.106.84";
static const uint16_t MQTT_PORT = 1883;
static const char *MQTT_USERNAME = "dev-dgkZnvru";
static const char *MQTT_PASSWORD = "password1234";
static const char *MQTT_CLIENT_ID = "dev-dgkZnvru";

static const char *IOT_USER_ID = "1";
static const char *DEVICE_UUID = "20e1196d-a31e-43ef-b092-2a21851ffa2a";

static const int DOOR_COMPONENT_CHANNEL = 1;
static const int RELAY_IN_PIN = 4;
static const bool RELAY_ACTIVE_HIGH = true;

static const unsigned long SENSOR_PUBLISH_MS = 5000UL;
static const unsigned long MQTT_FLUSH_MS = 120;
static const unsigned long ACTUATOR_SETTLE_MS = 80;

static char gSetTopicBuf[160];
static char gAppHeartbeatTopic[96] = "";
static char gTopicPrefix[96];

static unsigned long streamUntilMs = 0;
static unsigned long lastPublishMs = 0;
static unsigned long lastMqttTryMs = 0;

static int testInt = 10;
static float testFloat = 15.6f;
static bool testBool = false;
static bool lastDoorOpenForAlert = false;
static bool doorAlertInitialized = false;
static uint32_t publishSeq = 0;
static bool doorLocked = false;

WiFiClient net;
MQTTClient mqtt(8192);

static void buildTopics() {
  snprintf(gTopicPrefix, sizeof(gTopicPrefix), "iot/%s/%s", IOT_USER_ID, DEVICE_UUID);
  snprintf(gAppHeartbeatTopic, sizeof(gAppHeartbeatTopic), "%s/app/heartbeat", gTopicPrefix);
}

static void mqttFlush() {
  unsigned long t = millis();
  while (millis() - t < MQTT_FLUSH_MS) {
    mqtt.loop();
    delay(2);
  }
}

static bool streamingActive() {
  return streamUntilMs != 0 && millis() < streamUntilMs;
}

static void extendStreaming(uint32_t ttlSeconds) {
  if (ttlSeconds < 10) {
    ttlSeconds = 10;
  }
  if (ttlSeconds > 3600) {
    ttlSeconds = 3600;
  }
  streamUntilMs = millis() + (unsigned long)ttlSeconds * 1000UL;
  Serial.printf("[STREAM] active %lu s\n", (unsigned long)ttlSeconds);
}

static void stopStreaming() {
  streamUntilMs = 0;
  Serial.println("[STREAM] stopped");
}

static bool publishQos1(const char *topic, JsonDocument &doc) {
  char buf[640];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n == 0 || n >= sizeof(buf)) {
    return false;
  }
  bool ok = mqtt.publish(topic, buf, (int)n, false, 1);
  mqttFlush();
  return ok;
}

static bool publishSensorPayload(const char *sensorType, const char *buffer, size_t n) {
  if (!mqtt.connected() || n == 0) {
    return false;
  }
  char topic[128];
  snprintf(topic, sizeof(topic), "%s/sensor/%s", gTopicPrefix, sensorType);
  bool ok = mqtt.publish(topic, buffer, (int)n, false, 0);
  mqttFlush();
  if (ok) {
    Serial.printf("[PUB] %s\n", topic);
  }
  return ok;
}

static bool publishSensorV(const char *sensorType, float value, uint32_t seq) {
  StaticJsonDocument<192> doc;
  doc["v"] = value;
  doc["seq"] = seq;
  char buffer[192];
  size_t n = serializeJson(doc, buffer, sizeof(buffer));
  return n > 0 && publishSensorPayload(sensorType, buffer, n);
}

static bool publishSensorV(const char *sensorType, int value, uint32_t seq) {
  StaticJsonDocument<192> doc;
  doc["v"] = value;
  doc["seq"] = seq;
  char buffer[192];
  size_t n = serializeJson(doc, buffer, sizeof(buffer));
  return n > 0 && publishSensorPayload(sensorType, buffer, n);
}

static bool publishSensorV(const char *sensorType, bool value, uint32_t seq) {
  StaticJsonDocument<192> doc;
  doc["v"] = value;
  doc["seq"] = seq;
  char buffer[192];
  size_t n = serializeJson(doc, buffer, sizeof(buffer));
  return n > 0 && publishSensorPayload(sensorType, buffer, n);
}

static bool publishSensorString(const char *sensorType, const char *value, uint32_t seq) {
  StaticJsonDocument<224> doc;
  doc["v"] = value;
  doc["seq"] = seq;
  char buffer[224];
  size_t n = serializeJson(doc, buffer, sizeof(buffer));
  return n > 0 && n < sizeof(buffer) && publishSensorPayload(sensorType, buffer, n);
}

/** Critical: door_status immediately (ignores streaming gate). */
static void publishDoorStatusCritical(bool doorOpen) {
  publishSeq++;
  const char *doorText = doorOpen ? "door open" : "door closed";
  publishSensorString("door_status", doorText, publishSeq);
  lastDoorOpenForAlert = doorOpen;
  doorAlertInitialized = true;
}

static void updateDemoValues() {
  publishSeq++;
  testInt++;
  if (testInt > 100) {
    testInt = 0;
  }
  testFloat += 0.3f;
  if (testFloat > 35.0f) {
    testFloat = 15.0f;
  }
  testBool = !testBool;

  bool doorOpen = testBool;
  if (!doorAlertInitialized || doorOpen != lastDoorOpenForAlert) {
    publishDoorStatusCritical(doorOpen);
  }
}

static void publishTelemetryBurst() {
  updateDemoValues();
  publishSensorV("temperature", testFloat, publishSeq);
  publishSensorV("counter", testInt, publishSeq);
  publishSensorV("motion", testBool, publishSeq);
  const char *doorText = testBool ? "door open" : "door closed";
  publishSensorString("door_status", doorText, publishSeq);
}

static void publishDoorComponentStatus(const char *state, const char *correlationMessageId,
                                       const char *appliedAction, bool commandAck) {
  StaticJsonDocument<512> doc;
  doc["state"] = state;
  doc["locked"] = doorLocked;
  doc["door_open"] = !doorLocked;
  doc["command_ack"] = commandAck;
  if (appliedAction != nullptr && appliedAction[0] != '\0') {
    doc["applied_action"] = appliedAction;
  }
  if (correlationMessageId != nullptr && correlationMessageId[0] != '\0') {
    doc["message_id"] = correlationMessageId;
  }
  char topic[128];
  snprintf(topic, sizeof(topic), "%s/component/%d/status", gTopicPrefix, DOOR_COMPONENT_CHANNEL);
  publishQos1(topic, doc);
}

static bool applyLockCommand(const char *appliedAction, bool wantLocked, const char *midc) {
  doorLocked = wantLocked;
  const bool pinHigh = RELAY_ACTIVE_HIGH ? wantLocked : !wantLocked;
  digitalWrite(RELAY_IN_PIN, pinHigh ? HIGH : LOW);
  delay(ACTUATOR_SETTLE_MS);
  publishDoorComponentStatus(doorLocked ? "LOCKED" : "UNLOCKED", midc, appliedAction, true);
  bool doorOpen = !doorLocked;
  if (!doorAlertInitialized || doorOpen != lastDoorOpenForAlert) {
    publishDoorStatusCritical(doorOpen);
  }
  return true;
}

static void handleComponentSet(const char *bytes, int length) {
  StaticJsonDocument<384> doc;
  if (deserializeJson(doc, bytes, (size_t)length)) {
    return;
  }
  if (!doc.containsKey("action")) {
    return;
  }
  String action = doc["action"].as<String>();
  action.toUpperCase();
  String mid = doc["message_id"].isNull() ? String("") : doc["message_id"].as<String>();
  const char *midc = mid.length() ? mid.c_str() : "";

  if (action == "ON") {
    applyLockCommand("ON", true, midc);
  } else if (action == "OFF") {
    applyLockCommand("OFF", false, midc);
  } else if (action == "TOGGLE") {
    applyLockCommand("TOGGLE", !doorLocked, midc);
  }
}

static void handleAppHeartbeat(const char *bytes, int length) {
  StaticJsonDocument<384> doc;
  if (deserializeJson(doc, bytes, (size_t)length)) {
    return;
  }
  bool streaming = doc["streaming"].isNull() ? true : doc["streaming"].as<bool>();
  uint32_t ttl = doc["ttl_seconds"].isNull() ? 300 : doc["ttl_seconds"].as<uint32_t>();
  if (streaming) {
    extendStreaming(ttl);
    publishTelemetryBurst();
    lastPublishMs = millis();
  } else {
    stopStreaming();
  }
}

static void onMessageAdvanced(MQTTClient * /*client*/, char topic[], char bytes[], int length) {
  if (strstr(topic, "/app/heartbeat") != nullptr) {
    const char *hb = strstr(topic, "/app/heartbeat");
    if (hb != nullptr && strcmp(hb, "/app/heartbeat") == 0) {
      handleAppHeartbeat(bytes, length);
      return;
    }
  }
  if (strstr(topic, "/component/") != nullptr && strstr(topic, "/set") != nullptr) {
    const char *comp = strstr(topic, "/component/");
    if (comp != nullptr) {
      comp += 11;
      int ch = atoi(comp);
      if (ch == DOOR_COMPONENT_CHANNEL) {
        handleComponentSet(bytes, length);
      }
    }
  }
}

static bool connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 25000UL) {
    delay(200);
  }
  return WiFi.status() == WL_CONNECTED;
}

static bool connectMqtt() {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }
  if (mqtt.connected()) {
    return true;
  }
  if (millis() - lastMqttTryMs < 3000UL) {
    return false;
  }
  lastMqttTryMs = millis();

  mqtt.begin(MQTT_HOST, MQTT_PORT, net);
  mqtt.setKeepAlive(60);
  mqtt.onMessageAdvanced(onMessageAdvanced);

  unsigned long t0 = millis();
  while (!mqtt.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD) && millis() - t0 < 20000UL) {
    delay(400);
    mqtt.loop();
  }
  if (!mqtt.connected()) {
    return false;
  }

  mqtt.subscribe(gAppHeartbeatTopic, 1);
  snprintf(gSetTopicBuf, sizeof(gSetTopicBuf), "%s/component/%d/set", gTopicPrefix, DOOR_COMPONENT_CHANNEL);
  mqtt.subscribe(gSetTopicBuf, 1);

  Serial.println("[MQTT] connected (hybrid)");
  return true;
}

void setup() {
  Serial.begin(115200);
  delay(400);
  pinMode(RELAY_IN_PIN, OUTPUT);
  digitalWrite(RELAY_IN_PIN, RELAY_ACTIVE_HIGH ? LOW : HIGH);
  buildTopics();
  connectWifi();
  connectMqtt();
}

void loop() {
  if (!connectWifi()) {
    delay(500);
    return;
  }
  if (!connectMqtt()) {
    delay(200);
    return;
  }
  mqtt.loop();

  if (streamingActive() && millis() - lastPublishMs >= SENSOR_PUBLISH_MS) {
    lastPublishMs = millis();
    publishTelemetryBurst();
  }
}
