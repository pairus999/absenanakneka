#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_Fingerprint.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ===================== CONFIG =====================
const char* WIFI_SSID     = "ZIGGI";
const char* WIFI_PASSWORD = "mrzs2015";
const char* SERVER_BASE   = "http://absenanakneka.rf.gd/absensi/api/device.php";
const char* API_KEY       = "absenanaknekabyfairus";

// AS608: sensor TX -> ESP32 GPIO16 (RX2), sensor RX -> ESP32 GPIO17 (TX2)
#define FP_RX 16
#define FP_TX 17
#define SDA_PIN 26
#define SCL_PIN 25
#define BUZZER_PIN 15

const uint32_t COMMAND_INTERVAL = 2000;
const uint32_t HEARTBEAT_INTERVAL = 20000;
const uint32_t ENROLL_TIMEOUT = 30000;

HardwareSerial FingerSerial(2);
Adafruit_Fingerprint finger(&FingerSerial);
LiquidCrystal_I2C lcd(0x27, 16, 2);

uint32_t lastHeartbeat = 0;
uint32_t lastCommandPoll = 0;

// ===================== UI =====================
void beep(uint8_t count = 1, uint16_t ms = 100) {
  for (uint8_t i = 0; i < count; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(ms);
    digitalWrite(BUZZER_PIN, LOW);
    if (i + 1 < count) delay(100);
  }
}

void lcdMsg(String line1, String line2 = "") {
  lcd.clear();
  line1 = line1.substring(0, 16);
  line2 = line2.substring(0, 16);
  lcd.setCursor(0, 0); lcd.print(line1);
  lcd.setCursor(0, 1); lcd.print(line2);
}

// ===================== HTTP =====================
bool httpGet(const String& url, String& response, int& code) {
  HTTPClient http;
  http.setTimeout(8000);
  if (!http.begin(url)) return false;
  http.addHeader("X-API-Key", API_KEY);
  code = http.GET();
  response = http.getString();
  http.end();
  return code >= 200 && code < 300;
}

bool httpPostJson(const String& url, JsonDocument& doc, String& response, int& code) {
  HTTPClient http;
  http.setTimeout(10000);
  if (!http.begin(url)) return false;
  http.addHeader("X-API-Key", API_KEY);
  http.addHeader("Content-Type", "application/json");
  String body;
  serializeJson(doc, body);
  code = http.POST(body);
  response = http.getString();
  http.end();
  return code >= 200 && code < 300;
}

void heartbeat() {
  String response;
  int code = 0;
  bool ok = httpGet(String(SERVER_BASE) + "?action=heartbeat", response, code);
  Serial.printf("[HEARTBEAT] HTTP %d %s\n", code, ok ? "OK" : "FAIL");
}

// ===================== FINGERPRINT =====================
bool waitForFinger(uint32_t timeoutMs) {
  uint32_t start = millis();
  while (millis() - start < timeoutMs) {
    uint8_t p = finger.getImage();
    if (p == FINGERPRINT_OK) return true;
    if (p != FINGERPRINT_NOFINGER && p != FINGERPRINT_PACKETRECIEVEERR) {
      Serial.printf("[FP] getImage=%u\n", p);
    }
    delay(80);
  }
  return false;
}

bool waitForNoFinger(uint32_t timeoutMs) {
  uint32_t start = millis();
  while (millis() - start < timeoutMs) {
    uint8_t p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) return true;
    delay(80);
  }
  return false;
}

bool enrollFingerprint(uint16_t id) {
  if (id < 1 || id > 127) return false;

  Serial.printf("[ENROLL] START ID #%u\n", id);
  lcdMsg("DAFTAR JARI", "ID #" + String(id));
  beep();
  delay(500);

  lcdMsg("TEMPELKAN JARI", "PERTAMA");
  if (!waitForFinger(ENROLL_TIMEOUT)) {
    lcdMsg("TIMEOUT", "Jari pertama");
    beep(2, 180);
    return false;
  }

  uint8_t p = finger.image2Tz(1);
  Serial.printf("[ENROLL] image2Tz(1)=%u\n", p);
  if (p != FINGERPRINT_OK) {
    lcdMsg("GAGAL BACA", "Jari pertama");
    beep(2, 180);
    return false;
  }

  lcdMsg("ANGKAT JARI", "SEBENTAR...");
  beep();
  if (!waitForNoFinger(10000)) {
    lcdMsg("ANGKAT JARI", "LALU COBA LAGI");
    beep(2, 180);
    return false;
  }
  delay(500);

  lcdMsg("TEMPEL LAGI", "JARI YANG SAMA");
  if (!waitForFinger(ENROLL_TIMEOUT)) {
    lcdMsg("TIMEOUT", "Jari kedua");
    beep(2, 180);
    return false;
  }

  p = finger.image2Tz(2);
  Serial.printf("[ENROLL] image2Tz(2)=%u\n", p);
  if (p != FINGERPRINT_OK) {
    lcdMsg("GAGAL BACA", "Jari kedua");
    beep(2, 180);
    return false;
  }

  p = finger.createModel();
  Serial.printf("[ENROLL] createModel=%u\n", p);
  if (p != FINGERPRINT_OK) {
    lcdMsg("JARI BERBEDA", "ULANGI ENROLL");
    beep(3, 160);
    return false;
  }

  p = finger.storeModel(id);
  Serial.printf("[ENROLL] storeModel=%u ID #%u\n", p, id);
  if (p != FINGERPRINT_OK) {
    lcdMsg("GAGAL SIMPAN", "AS608");
    beep(3, 160);
    return false;
  }

  lcdMsg("ENROLL BERHASIL", "ID #" + String(id));
  beep(2, 120);
  delay(1500);
  lcdMsg("TEMPELKAN JARI", "UNTUK ABSEN");
  Serial.println("[ENROLL] SUCCESS");
  return true;
}

void sendCommandResult(long commandId, bool success, const String& message) {
  StaticJsonDocument<256> doc;
  doc["command_id"] = commandId;
  doc["success"] = success;
  doc["message"] = message;

  String response;
  int code = 0;
  bool ok = httpPostJson(String(SERVER_BASE) + "?action=result", doc, response, code);
  Serial.printf("[COMMAND RESULT] HTTP %d %s -> %s\n", code, ok ? "OK" : "FAIL", response.c_str());
}

void pollCommand() {
  String response;
  int code = 0;
  if (!httpGet(String(SERVER_BASE) + "?action=command", response, code)) {
    Serial.printf("[COMMAND] HTTP %d / no command\n", code);
    return;
  }

  StaticJsonDocument<768> doc;
  DeserializationError err = deserializeJson(doc, response);
  if (err) {
    Serial.printf("[COMMAND] JSON error: %s\n", err.c_str());
    return;
  }

  JsonObject cmd = doc["command"].as<JsonObject>();
  if (cmd.isNull()) return;

  long commandId = cmd["id"] | 0;
  String type = cmd["type"] | "";
  int fingerprintId = cmd["fingerprint_id"] | 0;

  Serial.printf("[COMMAND] id=%ld type=%s fingerprint=%d\n", commandId, type.c_str(), fingerprintId);

  if (type == "ENROLL" && commandId > 0 && fingerprintId > 0) {
    bool success = enrollFingerprint(fingerprintId);
    sendCommandResult(commandId, success,
      success ? "Fingerprint berhasil didaftarkan" : "Pendaftaran fingerprint gagal");
  } else {
    sendCommandResult(commandId, false, "Command tidak dikenali");
  }
}

int scanFingerprint() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) return 0;

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) return 0;

  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) {
    lcdMsg("TIDAK TERDAFTAR", "COBA LAGI");
    beep(2, 180);
    delay(900);
    lcdMsg("TEMPELKAN JARI", "UNTUK ABSEN");
    return 0;
  }

  Serial.printf("[ABSEN] Fingerprint ID #%d\n", finger.fingerID);
  return finger.fingerID;
}

void sendAttendance(int fingerprintId) {
  StaticJsonDocument<160> doc;
  doc["fingerprint_id"] = fingerprintId;

  String response;
  int code = 0;
  bool sent = httpPostJson(String(SERVER_BASE) + "?action=attendance", doc, response, code);
  Serial.printf("[ABSEN] HTTP %d %s -> %s\n", code, sent ? "OK" : "FAIL", response.c_str());

  if (!sent) {
    lcdMsg("SERVER ERROR", "CEK WIFI/WEB");
    beep(2, 180);
    delay(1200);
    lcdMsg("TEMPELKAN JARI", "UNTUK ABSEN");
    return;
  }

  StaticJsonDocument<512> result;
  if (deserializeJson(result, response)) {
    lcdMsg("RESPON ERROR", "SERVER");
    beep(2, 180);
    delay(1200);
    lcdMsg("TEMPELKAN JARI", "UNTUK ABSEN");
    return;
  }

  bool success = result["success"] | false;
  String nama = result["nama"] | "SISWA";
  String message = result["message"] | "GAGAL";

  if (success) {
    lcdMsg(nama, "ABSEN BERHASIL");
    beep();
  } else {
    lcdMsg(message, nama);
    beep(2, 180);
  }

  delay(1500);
  lcdMsg("TEMPELKAN JARI", "UNTUK ABSEN");
}

// ===================== SETUP / LOOP =====================
void setup() {
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);
  Serial.begin(115200);
  delay(500);

  Wire.begin(SDA_PIN, SCL_PIN);
  lcd.init();
  lcd.backlight();
  lcdMsg("ABSENSI", "STARTING...");

  FingerSerial.begin(57600, SERIAL_8N1, FP_RX, FP_TX);
  finger.begin(57600);

  // Give the AS608 several chances to wake/respond.
  bool fpOK = false;
  for (int attempt = 1; attempt <= 8; attempt++) {
    Serial.printf("[AS608] detect attempt %d/8...\n", attempt);
    if (finger.verifyPassword()) {
      fpOK = true;
      break;
    }
    while (FingerSerial.available()) FingerSerial.read();
    delay(500);
  }

  if (!fpOK) {
    Serial.println("[AS608] ERROR: sensor tidak terdeteksi");
    lcdMsg("AS608 ERROR", "RX16 TX17");
    beep(3, 200);
    // Do not freeze forever; restart so wiring/power can be retested.
    delay(4000);
    ESP.restart();
  }

  Serial.println("[AS608] OK");
  lcdMsg("AS608 OK", "CONNECT WIFI...");

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  uint32_t wifiStart = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - wifiStart < 30000) {
    delay(500);
    Serial.print('.');
  }
  Serial.println();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WIFI] GAGAL");
    lcdMsg("WIFI ERROR", "RESTART ESP32");
    beep(3, 200);
    delay(3000);
    ESP.restart();
  }

  Serial.print("[WIFI] OK IP: ");
  Serial.println(WiFi.localIP());
  lcdMsg("WIFI CONNECTED", "READY");
  beep(2, 80);
  delay(800);
  lcdMsg("TEMPELKAN JARI", "UNTUK ABSEN");
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WIFI] TERPUTUS, reconnect...");
    lcdMsg("WIFI TERPUTUS", "RECONNECT...");
    WiFi.disconnect();
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    delay(3000);
    return;
  }

  uint32_t now = millis();

  if (now - lastHeartbeat >= HEARTBEAT_INTERVAL) {
    lastHeartbeat = now;
    heartbeat();
  }

  if (now - lastCommandPoll >= COMMAND_INTERVAL) {
    lastCommandPoll = now;
    pollCommand();
  }

  // Normal attendance scanning runs only when the sensor is idle.
  int id = scanFingerprint();
  if (id > 0) {
    beep();
    sendAttendance(id);
    delay(200);
  }

  delay(80);
}
