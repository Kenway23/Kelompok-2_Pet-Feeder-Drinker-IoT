#include <ESP32Servo.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

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

// =======================
// SETTING SISTEM
// =======================
// Jika jarak lebih dari batas ini, pakan dianggap kosong/rendah
float batasPakanRendah = 10.0;

int posisiTutup = 0;
int posisiBuka = 90;
int waktuBukaPakan = 2000;

bool sedangFeeding = false;
String statusLCD = "";

// Untuk mencegah servo langsung jalan saat alat pertama nyala
int lastButtonState = LOW;

void setup() {
  Serial.begin(115200);
  delay(1000);

  // Button kamu ikut power 5V/VIN
  // Ditekan = HIGH
  // Tidak ditekan = LOW
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
  delay(1500);

  // Baca kondisi awal button agar servo tidak langsung jalan
  lastButtonState = digitalRead(BUTTON_PIN);

  tampilLCD("Pet Feeder", "Cek Sensor");

  Serial.println("=================================");
  Serial.println("IoT Pet Feeder ESP32 Ready");
  Serial.println("Semua power menggunakan 5V / VIN");
  Serial.println("Button HIGH = ditekan");
  Serial.println("Button LOW  = tidak ditekan");
  Serial.println("Jarak besar = Feed Low");
  Serial.println("Jarak kecil = Feed Ready");
  Serial.println("=================================");
}

void loop() {
  float jarak = bacaJarak();
  int kondisiButton = digitalRead(BUTTON_PIN);

  Serial.println("=================================");

  Serial.print("Jarak terdeteksi: ");
  if (jarak == 999) {
    Serial.println("Sensor Error");
  } else {
    Serial.print(jarak);
    Serial.println(" cm");
  }

  Serial.print("Batas pakan rendah: ");
  Serial.print(batasPakanRendah);
  Serial.println(" cm");

  Serial.print("Button: ");
  if (kondisiButton == HIGH) {
    Serial.println("DITEKAN");
  } else {
    Serial.println("TIDAK DITEKAN");
  }

  // =======================
  // BUTTON MANUAL FEEDING
  // =======================
  // Feeding hanya berjalan saat tombol berubah dari LOW ke HIGH
  if (lastButtonState == LOW && kondisiButton == HIGH && sedangFeeding == false) {
    feeding();
  }

  lastButtonState = kondisiButton;

  // =======================
  // STATUS SENSOR DAN PAKAN
  // =======================
  if (sedangFeeding == false) {
    if (jarak == 999) {
      Serial.println("STATUS: SENSOR ERROR / TIDAK TERBACA");

      noTone(BUZZER_PIN);
      digitalWrite(BUZZER_PIN, LOW);

      tampilLCD("Sensor Error", "Cek HC-SR04");
    }
    else if (jarak > batasPakanRendah) {
      Serial.println("STATUS: FEED LOW - ISI PAKAN");

      String teksJarak = "Jarak:" + String(jarak, 1) + "cm";
      tampilLCD(teksJarak, "Feed Low");

      bunyiPeringatan();
    }
    else {
      Serial.println("STATUS: FEED READY - PAKAN AMAN");

      noTone(BUZZER_PIN);
      digitalWrite(BUZZER_PIN, LOW);

      String teksJarak = "Jarak:" + String(jarak, 1) + "cm";
      tampilLCD(teksJarak, "Feed Ready");
    }
  }

  delay(500);
}

// =======================
// BACA SENSOR SEKALI
// =======================
float bacaJarakSekali() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000);

  Serial.print("Duration: ");
  Serial.print(duration);
  Serial.print(" | ");

  if (duration == 0) {
    Serial.println("Echo tidak terbaca");
    return -1;
  }

  float distanceCm = duration * 0.034 / 2;

  Serial.print("Raw distance: ");
  Serial.println(distanceCm);

  if (distanceCm < 2 || distanceCm > 300) {
    Serial.println("Data dibuang karena tidak masuk batas");
    return -1;
  }

  return distanceCm;
}

// =======================
// BACA SENSOR RATA-RATA
// =======================
float bacaJarak() {
  float total = 0;
  int jumlahValid = 0;

  for (int i = 0; i < 5; i++) {
    float jarak = bacaJarakSekali();

    if (jarak > 0) {
      total += jarak;
      jumlahValid++;
    }

    delay(50);
  }

  if (jumlahValid == 0) {
    return 999;
  }

  return total / jumlahValid;
}

// =======================
// FUNGSI FEEDING
// =======================
void feeding() {
  sedangFeeding = true;

  Serial.println("Feeding dimulai...");

  noTone(BUZZER_PIN);
  digitalWrite(BUZZER_PIN, LOW);

  tampilLCD("Feeding...", "Tunggu");
  bunyiPendek();

  feederServo.write(posisiBuka);
  delay(waktuBukaPakan);

  feederServo.write(posisiTutup);
  delay(500);

  bunyiPendek();

  tampilLCD("Feeding Done", "Ready");

  Serial.println("Feeding selesai");

  delay(1500);

  sedangFeeding = false;
}

// =======================
// FUNGSI LCD
// =======================
void tampilLCD(String baris1, String baris2) {
  String teksBaru = baris1 + baris2;

  if (teksBaru != statusLCD) {
    lcd.clear();

    lcd.setCursor(0, 0);
    lcd.print(baris1);

    lcd.setCursor(0, 1);
    lcd.print(baris2);

    statusLCD = teksBaru;
  }
}

// =======================
// BUZZER PENDEK
// =======================
void bunyiPendek() {
  tone(BUZZER_PIN, 3000);
  delay(200);
  noTone(BUZZER_PIN);
  delay(200);
}

// =======================
// BUZZER PERINGATAN
// =======================
void bunyiPeringatan() {
  tone(BUZZER_PIN, 3000);
  delay(300);
  noTone(BUZZER_PIN);
  delay(200);
}