#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <ESP32Servo.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <time.h>

// =======================
// KONFIGURASI WIFI + MQTT
// =======================
const char *WIFI_SSID = "ISI_NAMA_WIFI";
const char *WIFI_PASSWORD = "ISI_PASSWORD_WIFI";

const char *MQTT_HOST = "192.168.1.10";
const uint16_t MQTT_PORT = 1883;
const char *MQTT_USERNAME = "";
const char *MQTT_PASSWORD = "";

const char *DEVICE_ID = "petfeeder01";
const char *DEVICE_TOKEN = "change-me";

const char *MQTT_TOPIC_COMMAND = "pet-feeder/pakan/jadwal";
const char *MQTT_TOPIC_STATUS = "pet-feeder/status";
const char *MQTT_TOPIC_TELEMETRY = "pet-feeder/telemetry";

// WIB / Asia Jakarta = UTC+7
const long GMT_OFFSET_SEC = 7 * 3600;
const int DAYLIGHT_OFFSET_SEC = 0;

// =======================
// PIN ESP32
// =======================
#define SERVO_PIN 13
#define BUTTON_PIN 14
#define BUZZER_PIN 25
#define TRIG_PIN 26
#define ECHO_PIN 27

// =======================
// OBJEK KOMPONEN
// =======================
Servo feederServo;
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClient wifiClient;
PubSubClient mqttClient(wifiClient);

// =======================
// SETTING SISTEM
// =======================
float batasPakanRendah = 10.0;

int posisiTutup = 0;
int posisiBuka = 90;
int waktuBukaPakan = 700;
int durasiMinimalPakan = 1000;
int durasiMaksimalPakan = 3000;
int durasiPerGram = 70;

volatile bool sedangFeeding = false;
volatile float jarakTerakhir = 999;
volatile bool sensorError = false;
volatile bool pakanRendah = false;
volatile bool timeConfigured = false;
String statusLCD = "";

unsigned long lastFeedMillis = 0;
const unsigned long FEED_COOLDOWN_MS = 10000;

// =======================
// RTOS QUEUE + MUTEX
// =======================
struct FeedRequest
{
  char source[12];
  int porsi;
};

QueueHandle_t feedQueue;
SemaphoreHandle_t scheduleMutex;
SemaphoreHandle_t mqttMutex;

// =======================
// JADWAL DI RAM ESP32
// =======================
const int MAX_SCHEDULES = 10;

struct ScheduleItem
{
  bool used;
  bool active;
  char waktu[6]; // HH:MM
  int porsi;
  char lastRunKey[13]; // YYYYMMDDHHMM
};

ScheduleItem schedules[MAX_SCHEDULES];

// =======================
// DEKLARASI FUNGSI
// =======================
void taskSensor(void *parameter);
void taskButton(void *parameter);
void taskFeeder(void *parameter);
void taskMqtt(void *parameter);
void taskSchedule(void *parameter);
void taskDisplay(void *parameter);

void connectWiFi();
void connectMqtt();
void mqttCallback(char *topic, byte *payload, unsigned int length);
void handleCommand(JsonDocument &doc);
void publishStatus(const char *status, const char *reason, const char *source);
void publishTelemetry();
void enqueueFeeding(const char *source, int porsi);

void addOrUpdateSchedule(const char *waktu, int porsi, bool active);
void setScheduleStatus(const char *waktu, bool active);
void deleteSchedule(const char *waktu);

float bacaJarak();
float bacaJarakSekali();
int hitungDurasiBuka(int porsi);
void jalankanFeeding(const char *source, int porsi);
void tampilLCD(String baris1, String baris2);
void bunyiPendek();
void bunyiPeringatan();

void setup()
{
  Serial.begin(115200);
  delay(1000);

  pinMode(BUTTON_PIN, INPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);

  noTone(BUZZER_PIN);
  digitalWrite(BUZZER_PIN, LOW);

  feederServo.attach(SERVO_PIN);
  feederServo.write(posisiTutup);

  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  lcd.clear();
  tampilLCD("Pet Feeder", "Starting...");

  feedQueue = xQueueCreate(5, sizeof(FeedRequest));
  scheduleMutex = xSemaphoreCreateMutex();
  mqttMutex = xSemaphoreCreateMutex();

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  mqttClient.setServer(MQTT_HOST, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
  mqttClient.setBufferSize(768);

  xTaskCreatePinnedToCore(taskSensor, "Sensor", 4096, NULL, 1, NULL, 1);
  xTaskCreatePinnedToCore(taskButton, "Button", 2048, NULL, 2, NULL, 1);
  xTaskCreatePinnedToCore(taskFeeder, "Feeder", 4096, NULL, 3, NULL, 1);
  xTaskCreatePinnedToCore(taskMqtt, "MQTT", 6144, NULL, 2, NULL, 0);
  xTaskCreatePinnedToCore(taskSchedule, "Schedule", 4096, NULL, 1, NULL, 1);
  xTaskCreatePinnedToCore(taskDisplay, "Display", 4096, NULL, 1, NULL, 1);

  Serial.println("=================================");
  Serial.println("IoT Pet Feeder ESP32 Ready");
  Serial.println("MQTT + RTOS + Token Security aktif");
  Serial.println("=================================");
}

void loop()
{
  vTaskDelay(pdMS_TO_TICKS(1000));
}

// =======================
// TASK SENSOR
// =======================
void taskSensor(void *parameter)
{
  for (;;)
  {
    float jarak = bacaJarak();
    jarakTerakhir = jarak;
    sensorError = jarak == 999;
    pakanRendah = !sensorError && jarak > batasPakanRendah;

    Serial.print("Jarak: ");
    if (sensorError)
    {
      Serial.println("Sensor Error");
    }
    else
    {
      Serial.print(jarak);
      Serial.println(" cm");
    }

    vTaskDelay(pdMS_TO_TICKS(2000));
  }
}

// =======================
// TASK BUTTON MANUAL
// =======================
void taskButton(void *parameter)
{
  int lastButtonState = digitalRead(BUTTON_PIN);

  for (;;)
  {
    int kondisiButton = digitalRead(BUTTON_PIN);

    if (lastButtonState == LOW && kondisiButton == HIGH)
    {
      enqueueFeeding("button", 0);
    }

    lastButtonState = kondisiButton;
    vTaskDelay(pdMS_TO_TICKS(80));
  }
}

// =======================
// TASK FEEDER
// =======================
void taskFeeder(void *parameter)
{
  FeedRequest request;

  for (;;)
  {
    if (xQueueReceive(feedQueue, &request, portMAX_DELAY) == pdTRUE)
    {
      unsigned long nowMillis = millis();

      if (sedangFeeding)
      {
        Serial.print("Feeding ditolak dari ");
        Serial.print(request.source);
        Serial.println(": already_feeding");
        publishStatus("rejected", "already_feeding", request.source);
        continue;
      }

      if (lastFeedMillis > 0 && nowMillis - lastFeedMillis < FEED_COOLDOWN_MS)
      {
        Serial.print("Feeding ditolak dari ");
        Serial.print(request.source);
        Serial.println(": cooldown");
        publishStatus("rejected", "cooldown", request.source);
        continue;
      }

      jalankanFeeding(request.source, request.porsi);
      lastFeedMillis = millis();
    }
  }
}

// =======================
// TASK MQTT
// =======================
void taskMqtt(void *parameter)
{
  unsigned long lastTelemetry = 0;

  for (;;)
  {
    if (WiFi.status() != WL_CONNECTED)
    {
      connectWiFi();
    }

    if (WiFi.status() != WL_CONNECTED)
    {
      vTaskDelay(pdMS_TO_TICKS(3000));
      continue;
    }

    if (!mqttClient.connected())
    {
      connectMqtt();
    }

    mqttClient.loop();

    if (millis() - lastTelemetry > 10000)
    {
      publishTelemetry();
      lastTelemetry = millis();
    }

    vTaskDelay(pdMS_TO_TICKS(20));
  }
}

// =======================
// TASK JADWAL
// =======================
void taskSchedule(void *parameter)
{
  for (;;)
  {
    struct tm timeinfo;

    if (timeConfigured && getLocalTime(&timeinfo))
    {
      char waktuSekarang[6];
      char runKey[13];

      strftime(waktuSekarang, sizeof(waktuSekarang), "%H:%M", &timeinfo);
      strftime(runKey, sizeof(runKey), "%Y%m%d%H%M", &timeinfo);

      if (xSemaphoreTake(scheduleMutex, pdMS_TO_TICKS(500)) == pdTRUE)
      {
        for (int i = 0; i < MAX_SCHEDULES; i++)
        {
          if (!schedules[i].used || !schedules[i].active)
          {
            continue;
          }

          if (strcmp(schedules[i].waktu, waktuSekarang) == 0 &&
              strcmp(schedules[i].lastRunKey, runKey) != 0)
          {
            Serial.print("Jadwal cocok: ");
            Serial.print(schedules[i].waktu);
            Serial.print(" | porsi: ");
            Serial.println(schedules[i].porsi);
            strncpy(schedules[i].lastRunKey, runKey, sizeof(schedules[i].lastRunKey) - 1);
            schedules[i].lastRunKey[sizeof(schedules[i].lastRunKey) - 1] = '\0';
            enqueueFeeding("schedule", schedules[i].porsi);
          }
        }

        xSemaphoreGive(scheduleMutex);
      }
    }

    vTaskDelay(pdMS_TO_TICKS(1000));
  }
}

// =======================
// TASK DISPLAY + BUZZER STATUS
// =======================
void taskDisplay(void *parameter)
{
  for (;;)
  {
    if (!sedangFeeding)
    {
      if (sensorError)
      {
        noTone(BUZZER_PIN);
        digitalWrite(BUZZER_PIN, LOW);
        tampilLCD("Sensor Error", "Cek HC-SR04");
      }
      else if (pakanRendah)
      {
        String teksJarak = "Jarak:" + String(jarakTerakhir, 1) + "cm";
        tampilLCD(teksJarak, "Feed Low");
        bunyiPeringatan();
      }
      else
      {
        noTone(BUZZER_PIN);
        digitalWrite(BUZZER_PIN, LOW);
        String teksJarak = "Jarak:" + String(jarakTerakhir, 1) + "cm";
        tampilLCD(teksJarak, "Feed Ready");
      }
    }

    vTaskDelay(pdMS_TO_TICKS(1000));
  }
}

// =======================
// WIFI + MQTT
// =======================
void connectWiFi()
{
  if (WiFi.status() == WL_CONNECTED)
  {
    return;
  }

  tampilLCD("WiFi", "Connecting...");
  WiFi.disconnect(false);
  vTaskDelay(pdMS_TO_TICKS(500));
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  unsigned long startAttempt = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - startAttempt < 10000)
  {
    Serial.print(".");
    vTaskDelay(pdMS_TO_TICKS(500));
  }

  if (WiFi.status() != WL_CONNECTED)
  {
    Serial.println();
    Serial.println("WiFi belum tersambung, akan dicoba lagi di background");
    tampilLCD("WiFi Offline", "Manual OK");
    return;
  }

  Serial.println();
  Serial.print("WiFi connected: ");
  Serial.println(WiFi.localIP());
  tampilLCD("WiFi Connected", WiFi.localIP().toString());

  if (!timeConfigured)
  {
    configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, "pool.ntp.org", "time.nist.gov");
    timeConfigured = true;
  }
}

void connectMqtt()
{
  while (!mqttClient.connected())
  {
    String clientId = String("esp32_pet_feeder_") + DEVICE_ID + "_" + String((uint32_t)ESP.getEfuseMac(), HEX);

    Serial.print("Connecting MQTT...");

    bool connected;
    if (strlen(MQTT_USERNAME) > 0)
    {
      connected = mqttClient.connect(clientId.c_str(), MQTT_USERNAME, MQTT_PASSWORD);
    }
    else
    {
      connected = mqttClient.connect(clientId.c_str());
    }

    if (connected)
    {
      Serial.println("connected");

      bool subscribed = mqttClient.subscribe(MQTT_TOPIC_COMMAND);
      Serial.print("Subscribe topic ");
      Serial.print(MQTT_TOPIC_COMMAND);
      Serial.println(subscribed ? " berhasil" : " gagal");

      publishStatus("online", "mqtt_connected", "system");
    }
    else
    {
      Serial.print("failed, rc=");
      Serial.println(mqttClient.state());
      tampilLCD("MQTT Error", "Retry...");
      vTaskDelay(pdMS_TO_TICKS(3000));
    }
  }
}

void mqttCallback(char *topic, byte *payload, unsigned int length)
{
  char message[768];
  unsigned int copyLength = min(length, (unsigned int)(sizeof(message) - 1));
  memcpy(message, payload, copyLength);
  message[copyLength] = '\0';

  Serial.print("MQTT topic: ");
  Serial.println(topic);
  Serial.print("MQTT payload: ");
  Serial.println(message);

  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, message);

  if (error)
  {
    publishStatus("rejected", "invalid_json", "mqtt");
    return;
  }

  handleCommand(doc);
}

void handleCommand(JsonDocument &doc)
{
  const char *command = doc["command"] | "";
  const char *deviceId = doc["device_id"] | "";
  const char *token = doc["token"] | "";
  const char *waktu = doc["waktu"] | "";
  int porsi = doc["porsi"] | 0;

  if (strcmp(deviceId, DEVICE_ID) != 0)
  {
    Serial.print("Command diabaikan: device_id salah = ");
    Serial.println(deviceId);
    publishStatus("ignored", "wrong_device", "mqtt");
    return;
  }

  if (strcmp(token, DEVICE_TOKEN) != 0)
  {
    Serial.print("Command ditolak: token salah = ");
    Serial.println(token);
    publishStatus("rejected", "invalid_token", "mqtt");
    return;
  }

  if (strcmp(command, "feed_now") == 0 || strcmp(command, "FEED") == 0)
  {
    Serial.println("Command feed_now valid, masuk antrean feeding");
    enqueueFeeding("mqtt", porsi);
    publishStatus("accepted", "feed_queued", "mqtt");
  }
  else if (strcmp(command, "add_schedule") == 0)
  {
    Serial.println("Command add_schedule valid");
    addOrUpdateSchedule(waktu, porsi, true);
    publishStatus("accepted", "schedule_saved", "mqtt");
  }
  else if (strcmp(command, "activate_schedule") == 0)
  {
    Serial.println("Command activate_schedule valid");
    setScheduleStatus(waktu, true);
    publishStatus("accepted", "schedule_activated", "mqtt");
  }
  else if (strcmp(command, "deactivate_schedule") == 0)
  {
    Serial.println("Command deactivate_schedule valid");
    setScheduleStatus(waktu, false);
    publishStatus("accepted", "schedule_deactivated", "mqtt");
  }
  else if (strcmp(command, "delete_schedule") == 0)
  {
    Serial.println("Command delete_schedule valid");
    deleteSchedule(waktu);
    publishStatus("accepted", "schedule_deleted", "mqtt");
  }
  else if (strcmp(command, "status") == 0 || strcmp(command, "PING") == 0)
  {
    Serial.println("Command status/PING valid");
    publishTelemetry();
  }
  else
  {
    Serial.print("Command ditolak: command tidak dikenal = ");
    Serial.println(command);
    publishStatus("rejected", "unknown_command", "mqtt");
  }
}

void publishStatus(const char *status, const char *reason, const char *source)
{
  if (!mqttClient.connected())
  {
    return;
  }

  StaticJsonDocument<256> doc;
  doc["device_id"] = DEVICE_ID;
  doc["status"] = status;
  doc["reason"] = reason;
  doc["source"] = source;
  doc["is_feeding"] = sedangFeeding;
  doc["distance_cm"] = jarakTerakhir;

  char buffer[256];
  serializeJson(doc, buffer);

  if (xSemaphoreTake(mqttMutex, pdMS_TO_TICKS(500)) == pdTRUE)
  {
    mqttClient.publish(MQTT_TOPIC_STATUS, buffer);
    xSemaphoreGive(mqttMutex);
  }
}

void publishTelemetry()
{
  if (!mqttClient.connected())
  {
    return;
  }

  StaticJsonDocument<384> doc;
  doc["device_id"] = DEVICE_ID;
  doc["feed_level"] = sensorError ? "sensor_error" : (pakanRendah ? "low" : "ready");
  doc["distance_cm"] = jarakTerakhir;
  doc["is_feeding"] = sedangFeeding;
  doc["wifi"] = WiFi.status() == WL_CONNECTED ? "connected" : "disconnected";
  doc["mqtt"] = mqttClient.connected() ? "connected" : "disconnected";
  doc["ip"] = WiFi.localIP().toString();

  char buffer[384];
  serializeJson(doc, buffer);

  if (xSemaphoreTake(mqttMutex, pdMS_TO_TICKS(500)) == pdTRUE)
  {
    mqttClient.publish(MQTT_TOPIC_TELEMETRY, buffer);
    xSemaphoreGive(mqttMutex);
  }
}

void enqueueFeeding(const char *source, int porsi)
{
  FeedRequest request;
  strncpy(request.source, source, sizeof(request.source) - 1);
  request.source[sizeof(request.source) - 1] = '\0';
  request.porsi = porsi;

  if (xQueueSend(feedQueue, &request, 0) != pdTRUE)
  {
    Serial.print("Antrean feeding penuh untuk source ");
    Serial.println(source);
    publishStatus("rejected", "queue_full", source);
  }
  else
  {
    Serial.print("Feeding request masuk antrean dari ");
    Serial.print(source);
    Serial.print(" | porsi: ");
    Serial.println(porsi);
  }
}

// =======================
// JADWAL
// =======================
void addOrUpdateSchedule(const char *waktu, int porsi, bool active)
{
  if (strlen(waktu) != 5)
  {
    Serial.print("Jadwal ditolak: format waktu tidak valid = ");
    Serial.println(waktu);
    return;
  }

  if (xSemaphoreTake(scheduleMutex, pdMS_TO_TICKS(500)) == pdTRUE)
  {
    int emptyIndex = -1;

    for (int i = 0; i < MAX_SCHEDULES; i++)
    {
      if (schedules[i].used && strcmp(schedules[i].waktu, waktu) == 0)
      {
        schedules[i].porsi = porsi;
        schedules[i].active = active;
        Serial.print("Jadwal diperbarui: ");
        Serial.print(waktu);
        Serial.print(" | porsi: ");
        Serial.print(porsi);
        Serial.print(" | status: ");
        Serial.println(active ? "aktif" : "nonaktif");
        xSemaphoreGive(scheduleMutex);
        return;
      }

      if (!schedules[i].used && emptyIndex == -1)
      {
        emptyIndex = i;
      }
    }

    if (emptyIndex >= 0)
    {
      schedules[emptyIndex].used = true;
      schedules[emptyIndex].active = active;
      strncpy(schedules[emptyIndex].waktu, waktu, sizeof(schedules[emptyIndex].waktu) - 1);
      schedules[emptyIndex].waktu[sizeof(schedules[emptyIndex].waktu) - 1] = '\0';
      schedules[emptyIndex].porsi = porsi;
      schedules[emptyIndex].lastRunKey[0] = '\0';
      Serial.print("Jadwal baru tersimpan: ");
      Serial.print(waktu);
      Serial.print(" | porsi: ");
      Serial.print(porsi);
      Serial.print(" | status: ");
      Serial.println(active ? "aktif" : "nonaktif");
    }
    else
    {
      Serial.println("Jadwal gagal tersimpan: slot jadwal penuh");
    }

    xSemaphoreGive(scheduleMutex);
  }
}

void setScheduleStatus(const char *waktu, bool active)
{
  if (xSemaphoreTake(scheduleMutex, pdMS_TO_TICKS(500)) == pdTRUE)
  {
    for (int i = 0; i < MAX_SCHEDULES; i++)
    {
      if (schedules[i].used && strcmp(schedules[i].waktu, waktu) == 0)
      {
        schedules[i].active = active;
        Serial.print("Status jadwal berubah: ");
        Serial.print(waktu);
        Serial.print(" -> ");
        Serial.println(active ? "aktif" : "nonaktif");
      }
    }

    xSemaphoreGive(scheduleMutex);
  }
}

void deleteSchedule(const char *waktu)
{
  if (xSemaphoreTake(scheduleMutex, pdMS_TO_TICKS(500)) == pdTRUE)
  {
    for (int i = 0; i < MAX_SCHEDULES; i++)
    {
      if (schedules[i].used && strcmp(schedules[i].waktu, waktu) == 0)
      {
        schedules[i].used = false;
        schedules[i].active = false;
        schedules[i].waktu[0] = '\0';
        schedules[i].lastRunKey[0] = '\0';
        Serial.print("Jadwal dihapus: ");
        Serial.println(waktu);
      }
    }

    xSemaphoreGive(scheduleMutex);
  }
}

// =======================
// SENSOR
// =======================
float bacaJarakSekali()
{
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000);

  if (duration == 0)
  {
    return -1;
  }

  float distanceCm = duration * 0.034 / 2;

  if (distanceCm < 2 || distanceCm > 300)
  {
    return -1;
  }

  return distanceCm;
}

float bacaJarak()
{
  float total = 0;
  int jumlahValid = 0;

  for (int i = 0; i < 5; i++)
  {
    float jarak = bacaJarakSekali();

    if (jarak > 0)
    {
      total += jarak;
      jumlahValid++;
    }

    vTaskDelay(pdMS_TO_TICKS(50));
  }

  if (jumlahValid == 0)
  {
    return 999;
  }

  return total / jumlahValid;
}

// =======================
// FEEDING
// =======================
int hitungDurasiBuka(int porsi)
{
  if (porsi <= 0)
  {
    return waktuBukaPakan;
  }

  return constrain(porsi * durasiPerGram, durasiMinimalPakan, durasiMaksimalPakan);
}

void jalankanFeeding(const char *source, int porsi)
{
  sedangFeeding = true;
  int durasiBuka = hitungDurasiBuka(porsi);

  Serial.print("Feeding dimulai dari ");
  Serial.print(source);
  Serial.print(" | porsi: ");
  Serial.print(porsi);
  Serial.print(" gram | durasi buka: ");
  Serial.print(durasiBuka);
  Serial.println(" ms");

  publishStatus("feeding_started", "servo_open", source);

  noTone(BUZZER_PIN);
  digitalWrite(BUZZER_PIN, LOW);

  tampilLCD("Feeding...", String(source));

  feederServo.attach(SERVO_PIN);
  vTaskDelay(pdMS_TO_TICKS(100));
  Serial.print("Servo buka ke posisi ");
  Serial.println(posisiBuka);
  feederServo.write(posisiBuka);
  vTaskDelay(pdMS_TO_TICKS(durasiBuka));

  Serial.print("Servo tutup ke posisi ");
  Serial.println(posisiTutup);
  feederServo.write(posisiTutup);
  vTaskDelay(pdMS_TO_TICKS(500));

  bunyiPendek();

  tampilLCD("Feeding Done", "Ready");
  Serial.println("Feeding selesai");

  publishStatus("feeding_done", "servo_closed", source);

  vTaskDelay(pdMS_TO_TICKS(1500));
  sedangFeeding = false;
}

// =======================
// LCD + BUZZER
// =======================
void tampilLCD(String baris1, String baris2)
{
  String teksBaru = baris1 + baris2;

  if (teksBaru != statusLCD)
  {
    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print(baris1.substring(0, 16));

    lcd.setCursor(0, 1);
    lcd.print(baris2.substring(0, 16));

    statusLCD = teksBaru;
  }
}

void bunyiPendek()
{
  tone(BUZZER_PIN, 3000);
  vTaskDelay(pdMS_TO_TICKS(200));
  noTone(BUZZER_PIN);
  vTaskDelay(pdMS_TO_TICKS(200));
}

void bunyiPeringatan()
{
  tone(BUZZER_PIN, 3000);
  vTaskDelay(pdMS_TO_TICKS(300));
  noTone(BUZZER_PIN);
  vTaskDelay(pdMS_TO_TICKS(200));
}
