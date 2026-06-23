#include <MQTT.h>
#include <NTPClient.h>
#include <Wire.h>
#include <WiFi.h>
#include <WiFiUdp.h>

// PINS
//------
const int food_servo = 32;
const int food_dist_trig = 26;
const int food_dist_echo = 25;
const int food_button = 17;

const int water_servo = 33;
const int water_dist_trig = 14;
const int water_dist_echo = 27;
const int water_button = 16;

// THREADS
//---------
TaskHandle_t main_thread;
TaskHandle_t webserver_thread;
TaskHandle_t mqtt_thread;

// VARS
//------
// wifi connection
const char* ssid = "test";
const char* password = "test";

WiFiClient wifi_client;

const char wifi_attempt_limit = 5;
char wifi_attempts = 0;
bool wifi_connected = false;

// mqtt
const char* mqtt_addr = "iot-cat-feeder.cloud.shiftr.io";
const char* mqtt_username = "iot-cat-feeder";
const char* mqtt_password = "iCeCdkvG81Vf1nrs";
const short mqtt_port = 443;

MQTTClient mqtt_client;

const char mqtt_attempt_limit = 5;
char mqtt_attempts = 0;
bool mqtt_connected = false;

// ntp
const char* ntp_addr = "id.pool.ntp.org";
const long gmt_offset_sec = 25200; // 60 secs * 60 mins * 7 hours (GMT+7)

WiFiUDP ntp_udp;
NTPClient time_client(ntp_udp, ntp_addr);

const char ntp_attempt_limit = 5;
char ntp_attempts = 0
char ntp_connected = false;

// timer
uint8_t timer_hour = 0;
uint8_t timer_minute = 0;
uint8_t timer_second = 0;

// run status
bool wifi_conn_attempt_made = false;
bool mqtt_conn_attempt_made = false;
bool ntp_conn_attempt_made = false;
bool pin_mode_done = false;

// display strings
char[3] hour_str = {'0', '0'. '\0'};
char[3] minute_str = {'0', '0'. '\0'};
char[3] second_str = {'0', '0'. '\0'};

// General:
void setup() {
  Serial.begin(115200);
  Serial.println("Starting...");

  wifi_connect();
  mqtt_connect();

  set_pinmode();
}

// There'll be two threads, which is:
// 1. Main thread
// 3. MQTT S/R thread
//
void loop() {
  delay(1000); // this speeds up the simulation
}

// HELPER FUNCS
//--------------
void wifi_connect() {
  if (wifi_conn_attempt_made) {
    return;
  }

  WiFi.begin(ssid, password);
  Serial.print("Connecting to ");
  Serial.print(ssid);
  Serial.println("...");

  while(WiFi.status() != WL_CONNECTED) {
    if(wifi_attempts >= wifi_attempt_limit) {
      break;
    }

    delay(1000);
    Serial.println("...");
    wifi_attempts += 1;
  }

  wifi_connected = !(wifi_attempts >= wifi_attempt_limit);

  if(wifi_connected) {
    Serial.print("Successfully connected to ");
    Serial.println(ssid);
  } else {
    Serial.print("Failed connecting to ");
    Serial.println(ssid);
    Serial.println(". IoT Pet Feeder can only be operated manually without any internet connection.");
  }

  wifi_conn_attempt_made = true;
}

void mqtt_connect() {
  if (mqtt_conn_attempt_made) {
    return;
  }

  client.begin(" iot-cat-feeder.cloud.shiftr.io", wifi_client);
  client.onMessage();

  Serial.println("Connecting to MQTT server");
  while(!client.connect(mqtt_addr, mqtt_username, mqtt_password)) {
    if (mqtt_attempts >= mqtt_attempt_limit) {
      break;
    }

    delay(1000);
    Serial.println("...");
    mqtt_attempts += 1;
  }

  mqtt_connected = !(mqtt_attempts >= mqtt_attempt_limit);

  if(mqtt_connected) {
    Serial.println("Successfully connected to MQTT server.");
  } else {
    Serial.println("Failed connecting to MQTT server. IoT Pet Feeder can only be operated manually without any internet connection.");
  }

  mqtt_conn_attempt_made = true;
}

void ntp_connect() {
  if (ntp_conn_attempt_made) {
    return;
  }

  time_client.begin();
  timeClient.setTimeOffset(gmt_offset_sec);

  ntp_conn_attempt_made = true;
}

void set_pinmode() {
  if (pin_mode_done) {
    return;
  }

  pinMode(food_servo, OUTPUT);
  pinMode(food_button, INPUT);
  pinMode(food_dist_trig, OUTPUT);
  pinMode(food_dist_echo, INPUT);

  pinMode(water_servo, OUTPUT);
  pinMode(water_button, INPUT);
  pinMode(water_dist_trig, OUTPUT);
  pinMode(water_dist_echo, INPUT);

  pin_mode_done = true;
}

// THREAD FUNCS
//--------------
void run_main_thread(void* params) {

}

void run_mqtt_thread(void* params) {

}

void fmt_2digit(char* arr, uint8_t val) {
  if(val > 99) {
    printf("fmt_2digit does not support values above 2 digits. Entered value: %d \n", val);
    val = val % 100;
  }

  uint8_t div = val / 10;
  uint8_t rem = val % 10;

  arr[0] = (char) (div + 48);
  arr[1] = (char) (rem + 48);
}