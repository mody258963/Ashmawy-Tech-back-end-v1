/**
 * Home hub demo — ESP32: 10 relay channels + 5 MQTT sensors (bool / int / float)
 *
 * Libraries: "MQTT" (256dpi), "ArduinoJson" v6
 *
 * Backend: create ONE device, then 10 `iot_components` with channels 1..10 (type: switch or generic).
 *          Five sensor *types* are the MQTT topic segments after .../sensor/{type} (see SENSOR_DEFS).
 *
 * MQTT:
 *   - Subscribe: iot/{IOT_USER_ID}/{DEVICE_UUID}/component/+/set  (QoS 1)
 *   - Publish:   .../component/{ch}/status  (echo message_id, command_ack for API)
 *   - Publish:   .../sensor/{type}  with {"v": ...}  (QoS 0)
 *
 * Edit RELAY_PINS, SENSOR_DEFS, WiFi, MQTT, IOT_USER_ID, DEVICE_UUID to match your board and DB.
 */

#include <WiFi.h>
#include <MQTT.h>
#include <ArduinoJson.h>
#include <cstring>

// ---------------------------------------------------------------------------
// Wi-Fi & MQTT (use device JWT as MQTT_PASSWORD in production)
// ---------------------------------------------------------------------------
static const char *WIFI_SSID = "YOUR_WIFI_SSID";
static const char *WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

static const char *MQTT_HOST = "127.0.0.1";
static const uint16_t MQTT_PORT = 1883;
static const char *MQTT_USERNAME = "mqtt_username";
static const char *MQTT_PASSWORD = "mqtt_password_or_jwt";
static const char *MQTT_CLIENT_ID = "esp32-home-hub-1";

static const char *IOT_USER_ID = "1";
static const char *DEVICE_UUID = "00000000-0000-0000-0000-000000000000";

static const unsigned long MQTT_FLUSH_MS = 120;
static const unsigned long RELAY_SETTLE_MS = 40;
static const bool RELAY_ACTIVE_HIGH = true;

/** Output GPIO for component channel index 1..10 (RELAY_PINS[ch-1]). Adjust for your PCB. */
static const uint8_t RELAY_PINS[10] = {4, 5, 6, 7, 8, 9, 10, 11, 12, 13};

enum SensorKind : uint8_t { SK_BOOL = 0, SK_INT = 1, SK_FLOAT = 2 };

struct SensorDef {
  const char *type; // MQTT .../sensor/{type}
  uint8_t pin;
  SensorKind kind;
  bool usePullup; // digital only
  uint32_t intervalMs;
  uint32_t lastMs;
};

/** Five example sensors — change pins to valid ADC/GPIO for your ESP32-S3 module. */
static SensorDef SENSOR_DEFS[] = {
    {"motion_hall", 15, SK_BOOL, true, 500, 0},
    {"temp_adc", 2, SK_FLOAT, false, 3000, 0},
    {"lux_raw", 3, SK_INT, false, 2000, 0},
    {"counter_pin", 1, SK_INT, true, 1000, 0},
    {"water_leak", 14, SK_BOOL, true, 200, 0},
};
static const int SENSOR_COUNT = sizeof(SENSOR_DEFS) / sizeof(SENSOR_DEFS[0]);

static char gSetTopicFilter[96] = "";
static bool relayLevelHigh[10] = {false};

WiFiClient net;
MQTTClient mqtt(16384);
bool wifiOk = false;
bool mqttOk = false;
unsigned long lastMqttTry = 0;

String topicBase() {
  return String("iot/") + IOT_USER_ID + "/" + DEVICE_UUID;
}

String topicSet(int ch) {
  return topicBase() + "/component/" + String(ch) + "/set";
}

String topicStatus(int ch) {
  return topicBase() + "/component/" + String(ch) + "/status";
}

String topicSensor(const char *stype) {
  return topicBase() + "/sensor/" + stype;
}

void mqttFlush() {
  unsigned long t = millis();
  while (millis() - t < MQTT_FLUSH_MS) {
    mqtt.loop();
    delay(2);
  }
}

bool publishQos1(const char *topic, JsonDocument &doc) {
  char buf[768];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n == 0 || n >= sizeof(buf)) return false;
  bool ok = mqtt.publish(topic, buf, (int)n, false, 1);
  mqttFlush();
  return ok;
}

bool publishSensorQoS0(const char *topic, JsonDocument &doc) {
  char buf[256];
  size_t n = serializeJson(doc, buf, sizeof(buf));
  if (n == 0 || n >= sizeof(buf)) return false;
  bool ok = mqtt.publish(topic, buf, (int)n, false, 0);
  mqttFlush();
  return ok;
}

static void driveRelayChannel(int ch1to10, bool energized) {
  if (ch1to10 < 1 || ch1to10 > 10) return;
  uint8_t pin = RELAY_PINS[ch1to10 - 1];
  bool high = RELAY_ACTIVE_HIGH ? energized : !energized;
  digitalWrite(pin, high ? HIGH : LOW);
  relayLevelHigh[ch1to10 - 1] = high;
}

void publishChannelStatus(int ch, const char *correlationMessageId, const char *appliedAction,
                            bool commandAck) {
  StaticJsonDocument<512> doc;
  doc["channel"] = ch;
  bool energized = RELAY_ACTIVE_HIGH ? relayLevelHigh[ch - 1] : !relayLevelHigh[ch - 1];
  doc["relay_on"] = energized;
  doc["command_ack"] = commandAck;
  doc["uptime_ms"] = millis();
  if (appliedAction != nullptr && appliedAction[0] != '\0') {
    doc["applied_action"] = appliedAction;
  }
  if (correlationMessageId != nullptr && correlationMessageId[0] != '\0') {
    doc["message_id"] = correlationMessageId;
  }
  publishQos1(topicStatus(ch).c_str(), doc);
}

bool applyRelayCommand(int ch, const char *appliedAction, bool wantOn, const char *midc) {
  driveRelayChannel(ch, wantOn);
  delay(RELAY_SETTLE_MS);
  publishChannelStatus(ch, midc, appliedAction, true);
  return true;
}

void handleSetPayload(int ch, const char *bytes, int length) {
  StaticJsonDocument<384> doc;
  if (deserializeJson(doc, bytes, (size_t)length)) return;

  String action = doc["action"].as<String>();
  action.toUpperCase();
  String mid = doc["message_id"].isNull() ? String("") : doc["message_id"].as<String>();
  const char *midc = mid.length() ? mid.c_str() : "";

  if (ch < 1 || ch > 10) return;

  if (action == "ON") {
    applyRelayCommand(ch, "ON", true, midc);
    return;
  }
  if (action == "OFF") {
    applyRelayCommand(ch, "OFF", false, midc);
    return;
  }
  if (action == "TOGGLE") {
    bool pinHigh = relayLevelHigh[ch - 1];
    bool energized = RELAY_ACTIVE_HIGH ? pinHigh : !pinHigh;
    applyRelayCommand(ch, "TOGGLE", !energized, midc);
    return;
  }
  publishChannelStatus(ch, midc, "SET", false);
}

void onMessageAdvanced(MQTTClient * /*client*/, char topic[], char bytes[], int length) {
  if (strstr(topic, "/set") == nullptr || strcmp(strstr(topic, "/set"), "/set") != 0) return;
  const char *comp = strstr(topic, "/component/");
  if (!comp) return;
  comp += 11;
  const char *slash = strchr(comp, '/');
  if (!slash) return;
  int ch = atoi(comp);
  if (ch < 1) return;
  handleSetPayload(ch, bytes, length);
}

void sampleSensors() {
  uint32_t now = millis();
  for (int i = 0; i < SENSOR_COUNT; i++) {
    SensorDef &s = SENSOR_DEFS[i];
    if (s.intervalMs > 0 && now - s.lastMs < s.intervalMs) continue;
    s.lastMs = now;

    StaticJsonDocument<192> doc;
    if (s.kind == SK_BOOL) {
      int lv = digitalRead(s.pin) == HIGH ? 1 : 0;
      doc["v"] = lv != 0;
    } else if (s.kind == SK_INT) {
      if (s.usePullup) {
        doc["v"] = digitalRead(s.pin) == HIGH ? 1 : 0;
      } else {
        doc["v"] = analogRead(s.pin);
      }
    } else {
      int raw = analogRead(s.pin);
      doc["v"] = (float)raw * (3.3f / 4095.0f);
      doc["raw"] = raw;
    }
    publishSensorQoS0(topicSensor(s.type).c_str(), doc);
  }
}

void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    wifiOk = true;
    return;
  }
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) delay(200);
  wifiOk = (WiFi.status() == WL_CONNECTED);
}

void connectMqtt() {
  if (!wifiOk) return;
  mqtt.begin(MQTT_HOST, MQTT_PORT, net);
  mqtt.setKeepAlive(60);
  mqtt.setTimeout(5000);
  mqtt.onMessageAdvanced(onMessageAdvanced);
  unsigned long t0 = millis();
  while (!mqtt.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD) && millis() - t0 < 15000) {
    delay(300);
  }
  mqttOk = mqtt.connected();
  if (!mqttOk) return;

  snprintf(gSetTopicFilter, sizeof(gSetTopicFilter), "iot/%s/%s/component/+/set", IOT_USER_ID, DEVICE_UUID);
  if (!mqtt.subscribe(gSetTopicFilter, 1)) {
    mqttOk = false;
    return;
  }
  for (int i = 0; i < 60; i++) {
    mqtt.loop();
    delay(5);
  }
}

void setup() {
  Serial.begin(115200);
  delay(300);

  for (int i = 0; i < 10; i++) {
    pinMode(RELAY_PINS[i], OUTPUT);
    digitalWrite(RELAY_PINS[i], RELAY_ACTIVE_HIGH ? LOW : HIGH);
    relayLevelHigh[i] = false;
  }

  for (int i = 0; i < SENSOR_COUNT; i++) {
    SensorDef &s = SENSOR_DEFS[i];
    if (s.kind == SK_BOOL || (s.kind == SK_INT && s.usePullup)) {
      pinMode(s.pin, s.usePullup ? INPUT_PULLUP : INPUT);
    } else {
      pinMode(s.pin, INPUT);
#if defined(ESP32)
      analogSetPinAttenuation(s.pin, ADC_11db);
#endif
    }
  }

  connectWifi();
  if (wifiOk) connectMqtt();
}

void loop() {
  if (!wifiOk || WiFi.status() != WL_CONNECTED) {
    wifiOk = false;
    mqttOk = false;
    connectWifi();
    delay(400);
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
  sampleSensors();
}
