/**
 * Ashmawy ESP32 — minimal always-on sensor publisher (lab / Flutter testing).
 *
 * - Publishes demo sensors every SENSOR_PUBLISH_MS (no app heartbeat required).
 * - Does NOT handle door lock commands or app/heartbeat.
 * - Production installs: use AshmawyEsp32Hybrid.ino instead.
 *
 * Libraries: MQTT (256dpi), ArduinoJson v6
 * Docs: ./README.md
 */

#include <WiFi.h>
#include <MQTT.h>
#include <ArduinoJson.h>

static const char *WIFI_SSID = "YourNetwork";
static const char *WIFI_PASSWORD = "YourPassword";

static const char *MQTT_HOST = "your-emqx-host";
static const uint16_t MQTT_PORT = 1883;
static const char *MQTT_USERNAME = "dev-xxxxxxxx";
static const char *MQTT_PASSWORD = "paste-device-jwt-here";
static const char *MQTT_CLIENT_ID = "esp32-sensor-minimal-1";

static const char *IOT_USER_ID = "1";
static const char *DEVICE_UUID = "00000000-0000-0000-0000-000000000000";

static const unsigned long SENSOR_PUBLISH_MS = 10000UL;
static const unsigned long MQTT_FLUSH_MS = 120;

static char gTopicPrefix[96];
static unsigned long lastPublishMs = 0;
static unsigned long lastMqttTryMs = 0;

static int testInt = 10;
static float testFloat = 15.6f;
static bool testBool = false;
static uint32_t publishSeq = 0;

WiFiClient net;
MQTTClient mqtt(4096);

static void buildTopics() {
  snprintf(gTopicPrefix, sizeof(gTopicPrefix), "iot/%s/%s", IOT_USER_ID, DEVICE_UUID);
}

static void mqttFlush() {
  unsigned long t = millis();
  while (millis() - t < MQTT_FLUSH_MS) {
    mqtt.loop();
    delay(2);
  }
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
}

static void publishTelemetryBurst() {
  updateDemoValues();
  publishSensorV("temperature", testFloat, publishSeq);
  publishSensorV("counter", testInt, publishSeq);
  publishSensorV("motion", testBool, publishSeq);
  const char *doorText = testBool ? "door open" : "door closed";
  publishSensorString("door_status", doorText, publishSeq);
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

  unsigned long t0 = millis();
  while (!mqtt.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD) && millis() - t0 < 20000UL) {
    delay(400);
    mqtt.loop();
  }
  if (!mqtt.connected()) {
    Serial.println("[MQTT] connect failed");
    return false;
  }

  Serial.println("[MQTT] connected (sensor minimal)");
  return true;
}

void setup() {
  Serial.begin(115200);
  delay(400);
  buildTopics();
  connectWifi();
  connectMqtt();
  lastPublishMs = millis();
  publishTelemetryBurst();
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

  if (millis() - lastPublishMs >= SENSOR_PUBLISH_MS) {
    lastPublishMs = millis();
    publishTelemetryBurst();
  }
}
