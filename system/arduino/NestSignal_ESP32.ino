/*
 * NestSignal – ESP32-C6 Sensor-Sketch
 * Sendet alle 20 Sekunden Bewegungs- und Lärmdaten per HTTP POST an die PHP-API.
 *
 * Hardware:
 *  - SR602 PIR Sensor: OUT → GPIO2
 *  - INMP441 Mikrofon: WS → GPIO5, SCK → GPIO6, SD → GPIO4, L/R → GND
 *
 * Logik:
 *  - Alle 20 Sek eine Messung (bewegung 0/1, laerm 0/1)
 *  - 3 aufeinanderfolgende Messungen mit mind. einem Wert=1 → wake_event
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <driver/i2s.h>

// ──────────────────────────────────────────────
//  KONFIGURATION – hier deine Werte eintragen!
// ──────────────────────────────────────────────

const char* WIFI_SSID     = "DEIN_WLAN_NAME";
const char* WIFI_PASSWORD = "DEIN_WLAN_PASSWORT";

// URL deines PHP-Endpoints auf Hostpoint
const char* API_URL       = "https://djil.afopulax.myhostpoint.ch/api/sensor_data.php";

// API-Secret (muss in sensor_data.php / config.php identisch sein)
const char* API_SECRET    = "DEIN_API_SECRET";

// Gerätedaten
const int   DEVICE_ID     = 1;                          // device_id in der DB

// ──────────────────────────────────────────────
//  PIN-DEFINITIONEN
// ──────────────────────────────────────────────

#define PIR_PIN     2   // SR602 OUT → GPIO2
#define I2S_WS      5   // INMP441 WS/LRCL → GPIO5
#define I2S_SCK     6   // INMP441 SCK/BCLK → GPIO6
#define I2S_SD      4   // INMP441 SD/DOUT → GPIO4

// ──────────────────────────────────────────────
//  KONSTANTEN
// ──────────────────────────────────────────────

#define MEASURE_INTERVAL_MS  20000   // 20 Sekunden
#define LAERM_SCHWELLE       15000   // Lautstärke-Schwellwert (anpassen nach Test)
#define BUFFER_SIZE          256     // I2S-Puffergrösse
#define CONSECUTIVE_NEEDED   3       // Anzahl aufeinanderfolgender Messungen für wake_event

// ──────────────────────────────────────────────
//  GLOBALE VARIABLEN
// ──────────────────────────────────────────────

int consecutiveCount = 0;  // Zähler aufeinanderfolgender "positiver" Messungen
unsigned long lastMeasureTime = 0;

// ──────────────────────────────────────────────
//  SETUP
// ──────────────────────────────────────────────

void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n=== NestSignal gestartet ===");

  // PIR-Pin als Eingang
  pinMode(PIR_PIN, INPUT);

  // I2S für INMP441 initialisieren
  setupI2S();

  // WLAN verbinden
  connectWiFi();

  Serial.println("Bereit. Erste Messung in 20 Sekunden...");
}

// ──────────────────────────────────────────────
//  LOOP
// ──────────────────────────────────────────────

void loop() {
  unsigned long now = millis();

  if (now - lastMeasureTime >= MEASURE_INTERVAL_MS) {
    lastMeasureTime = now;
    doMeasurementAndSend();
  }

  // WLAN-Verbindung aufrechterhalten
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WLAN getrennt – reconnect...");
    connectWiFi();
  }
}

// ──────────────────────────────────────────────
//  MESSUNG UND SENDEN
// ──────────────────────────────────────────────

void doMeasurementAndSend() {
  // 1. Bewegung messen (SR602)
  int bewegung = digitalRead(PIR_PIN);  // 0 oder 1

  // 2. Lärm messen (INMP441)
  int laerm = messeLaerm();             // 0 oder 1

  Serial.printf("[Messung] bewegung=%d | laerm=%d\n", bewegung, laerm);

  // 3. Aufeinanderfolgende positive Messungen zählen
  bool positiv = (bewegung == 1 || laerm == 1);
  if (positiv) {
    consecutiveCount++;
    Serial.printf("  → Positiv-Zähler: %d / %d\n", consecutiveCount, CONSECUTIVE_NEEDED);
  } else {
    consecutiveCount = 0;
    Serial.println("  → Kein Signal, Zähler zurückgesetzt.");
  }

  // 4. Sende device_data an PHP-API
  bool gesendet = sendeSensorDaten(bewegung, laerm);

  // 5. Wenn 3 aufeinanderfolgend positiv → wake_event senden
  if (consecutiveCount >= CONSECUTIVE_NEEDED) {
    Serial.println("  *** Wake Event erkannt! Sende wake_event... ***");
    sendeWakeEvent(bewegung, laerm);
    consecutiveCount = 0;  // Reset nach wake_event
  }
}

// ──────────────────────────────────────────────
//  LÄRM MESSEN (INMP441 via I2S)
// ──────────────────────────────────────────────

int messeLaerm() {
  int32_t buffer[BUFFER_SIZE];
  size_t bytes_read = 0;

  esp_err_t result = i2s_read(I2S_NUM_0, buffer, sizeof(buffer), &bytes_read, pdMS_TO_TICKS(100));

  if (result != ESP_OK || bytes_read == 0) {
    Serial.println("  [I2S] Lesefehler oder keine Daten!");
    return 0;
  }

  int samples = bytes_read / sizeof(int32_t);

  int32_t maxVal = 0;
  for (int i = 0; i < samples; i++) {
    int32_t val = abs(buffer[i] >> 8);
    if (val > maxVal) maxVal = val;
  }

  Serial.printf("  [I2S] Lautstärke: %d (Schwelle: %d)\n", maxVal, LAERM_SCHWELLE);

  return (maxVal >= LAERM_SCHWELLE) ? 1 : 0;
}

// ──────────────────────────────────────────────
//  HTTP POST – device_data
// ──────────────────────────────────────────────

bool sendeSensorDaten(int bewegung, int laerm) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("  [HTTP] Kein WLAN – Senden abgebrochen.");
    return false;
  }

  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String payload = "action=sensor_data";
  payload += "&api_secret=" + String(API_SECRET);
  payload += "&device_id=" + String(DEVICE_ID);
  payload += "&bewegung=" + String(bewegung);
  payload += "&laerm=" + String(laerm);

  Serial.println("  [HTTP] Sende device_data...");
  int httpCode = http.POST(payload);

  Serial.printf("  [HTTP] Status: %d\n", httpCode);
  if (httpCode > 0) {
    Serial.println("  [HTTP] Antwort: " + http.getString());
  } else {
    Serial.println("  [HTTP] Fehler: " + http.errorToString(httpCode));
  }

  http.end();
  return (httpCode == 200);
}

// ──────────────────────────────────────────────
//  HTTP POST – wake_event
// ──────────────────────────────────────────────

void sendeWakeEvent(int bewegung, int laerm) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("  [HTTP] Kein WLAN – wake_event abgebrochen.");
    return;
  }

  String triggerType;
  if (bewegung == 1 && laerm == 1) {
    triggerType = "beides";
  } else if (bewegung == 1) {
    triggerType = "bewegung";
  } else {
    triggerType = "laerm";
  }

  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String payload = "action=wake_event";
  payload += "&api_secret=" + String(API_SECRET);
  payload += "&device_id=" + String(DEVICE_ID);
  payload += "&trigger_type=" + triggerType;

  Serial.println("  [HTTP] Sende wake_event (trigger_type=" + triggerType + ")...");
  int httpCode = http.POST(payload);

  Serial.printf("  [HTTP] Status: %d\n", httpCode);
  if (httpCode > 0) {
    Serial.println("  [HTTP] Antwort: " + http.getString());
  }

  http.end();
}

// ──────────────────────────────────────────────
//  WLAN VERBINDEN
// ──────────────────────────────────────────────

void connectWiFi() {
  Serial.printf("Verbinde mit WLAN: %s ", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int versuche = 0;
  while (WiFi.status() != WL_CONNECTED && versuche < 20) {
    delay(500);
    Serial.print(".");
    versuche++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWLAN verbunden!");
    Serial.println("IP-Adresse: " + WiFi.localIP().toString());
  } else {
    Serial.println("\nWLAN-Verbindung fehlgeschlagen! Nächster Versuch im Loop.");
  }
}

// ──────────────────────────────────────────────
//  I2S SETUP (INMP441)
// ──────────────────────────────────────────────

void setupI2S() {
  i2s_config_t i2s_config = {
    .mode = (i2s_mode_t)(I2S_MODE_MASTER | I2S_MODE_RX),
    .sample_rate = 16000,
    .bits_per_sample = I2S_BITS_PER_SAMPLE_32BIT,
    .channel_format = I2S_CHANNEL_FMT_ONLY_LEFT,
    .communication_format = I2S_COMM_FORMAT_STAND_I2S,
    .intr_alloc_flags = ESP_INTR_FLAG_LEVEL1,
    .dma_buf_count = 8,
    .dma_buf_len = BUFFER_SIZE,
    .use_apll = false,
    .tx_desc_auto_clear = false,
    .fixed_mclk = 0
  };

  i2s_pin_config_t pin_config = {
    .bck_io_num   = I2S_SCK,
    .ws_io_num    = I2S_WS,
    .data_out_num = I2S_PIN_NO_CHANGE,
    .data_in_num  = I2S_SD
  };

  esp_err_t err = i2s_driver_install(I2S_NUM_0, &i2s_config, 0, NULL);
  if (err != ESP_OK) {
    Serial.printf("I2S install error: %d\n", err);
  }

  err = i2s_set_pin(I2S_NUM_0, &pin_config);
  if (err != ESP_OK) {
    Serial.printf("I2S set_pin error: %d\n", err);
  }

  i2s_zero_dma_buffer(I2S_NUM_0);
  Serial.println("I2S (INMP441) bereit.");
}