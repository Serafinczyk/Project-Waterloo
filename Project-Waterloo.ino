
//https://github.com/esp8266/Arduino

////////////////////////global variables////////////////////////
volatile unsigned int count = 0; //counter
volatile unsigned int flowCounter = 0; //counter
bool connectionState;//stores state of wifi true=connected
String WifiList; //HTML formated list of wifi's
bool WebSetupchanged;// true if client change setup via website
String ssid;
String password;
bool isEmpty;
String deviceURL;
bool alertAvailable = false;
bool alertFired = false;
////////////////////////global variables////////////////////////

////////////////////////counter////////////////////////
IRAM_ATTR void Flow() {
  count++;
  flowCounter++;
  //Serial.print("#");
}
////////////////////////counter////////////////////////


#include <ESP8266WiFi.h>
#include "setup.h"
//#include <WiFiClient.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>
#include <ESP8266WebServer.h>

#ifdef OTA
#include <ESP8266HTTPUpdateServer.h>
#endif

#include <DNSServer.h>
#include <EEPROM.h>
#include <WiFiUdp.h>
#include <LittleFS.h>
#include <Wire.h>
#include "stringLib.h"
#include "timeLib.h"
#include "DBLib.h"
#include "webPage.h"



ESP8266WebServer server(80);
#ifdef OTA
ESP8266HTTPUpdateServer httpUpdater;
#endif


WiFiUDP udp;
timeLib Time(RTCaddress, &udp , ntpServerName, []() {// onError
  Serial.println("System HALTED!!!");
  WiFi.disconnect();
  while (1) {
    digitalWrite(statusLed, HIGH);
    delay(100);
    digitalWrite(statusLed, LOW);
    delay(100);
    ESP.wdtFeed();
  }
});
requestLib RQ(&Time, &serverName, ISRG_Root_X1);
#include "network.h"





void setup() {
  Serial.begin(74880);
  Wire.begin(5,4);
  delay(100);
  Serial.println("");
  Serial.println("Booting...");
#ifdef LOGHTTPONLY
  Serial.println("Shell output: /log.txt");
  Serial.println("Shell input: /null");
#endif

#ifdef COMBINELOG
  Serial.println("Shell output: /log.txt; UART TX");
  Serial.println("Shell input: UART RX");
#endif

#ifndef LOGHTTP
  Serial.println("Shell output: UART TX");
  Serial.println("Shell input: UART RX");
#endif
  Serial.print("Chip ID: ");
  Serial.println(ESP.getChipId());
  Serial.println("Project name: Water sensor");
  Serial.print("Project file: ");
  Serial.println(__FILE__);
  Serial.print("Last changed: ");
  Serial.print(__DATE__);
  Serial.print(" ");
  Serial.print(__TIME__);
  Serial.println("\n"); // two line ends
  pinMode(sensor, INPUT);
  attachInterrupt(digitalPinToInterrupt(sensor), Flow, FALLING);  //Configures interrupt to run the function "Flow"
  pinMode(statusLed, OUTPUT);
  pinMode(warningLed, OUTPUT);
  pinMode(button, INPUT_PULLUP);
  pinMode(Clock, INPUT);

  digitalWrite(statusLed, HIGH);
  digitalWrite(warningLed, LOW);

  EEPROM.begin(98);
  Serial.println("Waiting for EEPROM");
  delay(100);
  if (digitalRead(button) == LOW) {
    if (!(EEPROM.read(0) == 0 or  EEPROM.read(0) == 255)) {
      Serial.println("Key detected, erasing Wifi data");
      for (int i = 0; i < 98; i++) {
        EEPROM.write(i, 0);
      }
      if (!EEPROM.commit()) {
        RQ.flashError();
      }
    }
  }



  if (!(EEPROM.read(0) == 0 or  EEPROM.read(0) == 255)) {
    Serial.println("Network data found");
    byte dataLength = EEPROM.read(0);
    for (int i = 0; i < dataLength; ++i)
    {
      ssid = ssid + char(EEPROM.read(i + 1));
    }

    Serial.print("SSID: ");
    Serial.println(ssid);

    dataLength = EEPROM.read(dataLength + 1); // password length adress
    for (int i = 0; i < dataLength; ++i)
    {
      password = password + char(EEPROM.read(i + ssid.length() + 2));
    }

    Serial.print("Password: ");
    if (viewPassword) {
      Serial.println(password);
    } else {
      for (int i = 0; i < password.length() ; i++) {
        Serial.print("*");
      }
      Serial.println(" ");
    }
    networkConnect();

  } else {
    scanForNetworks();
    Serial.println("Creating access point");
    WiFi.mode(WIFI_AP);
    DNSServer dns;
    WiFi.softAPConfig(apIP, apIP, subnetmask);
    if (apPassword) {
      WiFi.softAP(APname, APpassword);
    } else {
      WiFi.softAP(APname);
    }
    Serial.println("Access point created");
    digitalWrite(warningLed, HIGH);
    WebSetupchanged = false;
    Serial.print ("Server IP is ");
    Serial.println (WiFi.softAPIP());
    dns.start(53, "*", WiFi.softAPIP());
    serwerSetup (apIP);
stringToLong:
    Serial.println("Please enter network SSID:");
    while (!Serial.available()) {
      dns.processNextRequest();
      server.handleClient();
      if (WebSetupchanged) {
        goto networkSetViaWeb;
      }
    }
    ssid = Serial.readStringUntil('\n');
    Serial.println("Please enter network password:");
    while (!Serial.available()) {
      dns.processNextRequest();
      server.handleClient();
      if (WebSetupchanged) {
        goto networkSetViaWeb;
      }
    }
    password = Serial.readStringUntil('\n');
    if (ssid.length() > 32) {
      Serial.println("SSID is too long :(");
      goto stringToLong;
    }
    if (password.length() > 63) {
      Serial.println("Password is too long :(");
      goto stringToLong;
    }
networkSetViaWeb:
    dns.stop();
    server.stop();
    WiFi.disconnect();
    serialFlushRead(); //erasing serial incoming buffer
    networkSet();
    networkConnect();
    waitForNetworkConnect();

  }


  EEPROM.end();
  ///core config end
  Serial.println("Waiting for rtc");
  delay(3000);
  if (!skipClockSync and Time.lostPower()) {
    waitForNetworkConnect();
    while (!Time.getFromNTP()) {
      Serial.println ("Trying again");
      delay (1000);
    }
    Time.toRTC();
    Serial.println(Time.toStr());
    Time.getFromRTC();
    Serial.println(Time.toStr());
    //Serial.println(Time.tmStruct().tm_mon);
  } else {
    Serial.println ("Clock sync skipped");
  }

  Time.prepair(Clock);
  //clock config end

  Serial.println("Testing FS");
  if (!LittleFS.begin()) {
    RQ.flashError();
  }

  Serial.println("Scanning FS");
  Serial.println("List of files " + recordsFolder + ":");
  Dir dataFolder = LittleFS.openDir(recordsFolder);
  String Name;  //uint verssion is N
  unsigned int N = 0;
  isEmpty = true;
  while (dataFolder.next()) {
    isEmpty = false;
    String fileName = dataFolder.fileName();
    Serial.println(fileName);
    unsigned int recorNumber = fileName.toInt();
    if (recorNumber > N) {
      N = recorNumber;
    }
  }
  Name = String(N);
  dataFolder.rewind();
  Serial.println("====================");
  if (isEmpty) {
    Serial.println("Directory is empty");
    Serial.println("wating for network connection");
    waitForNetworkConnect();

    if (!skipDBRequest) {
      while (!RQ.getStatus(&count)) {
        Serial.println("Trying again in 10 seconds");
        delay(10000);
      }
      Serial.println ("Counter state = " + String (count));
    } else {
      Serial.println("Skipped, counting from 0");
    }


  } else {
    Serial.println("Reading state from file " + Name);
    File record = LittleFS.open(recordsFolder + "/" + Name, "r");
    record.readStringUntil('\n');
    count = count + ((double)streamParseDouble(&record) * 1000 / 2.25);
    Serial.println ("Counter state = " + String (count));
    record.close();
  }


  LittleFS.end();
  if (WiFi.status() == WL_CONNECTED) {
    serwerSetup (WiFi.localIP());
  }

  digitalWrite(warningLed, LOW);
  flowCounter = 0;
  Time.clearA2Flag();
  alertFired = false;
  alertAvailable = RQ.getHourLimit();
  //Serial.println(alertAvailable);
  Serial.println("Ready!");

}





void loop() {
  static unsigned long lastButtonPressed = 0;
  static bool buttonClicked = false;
  if (WiFi.status() != WL_CONNECTED) {
    if (connectionState) {
      Serial.println("Wifi disconnected :(");
    }
    connectionState = false;
    digitalWrite(statusLed, HIGH);

  } else {
    if (!connectionState) {
      Serial.println("Wifi connected :)");
      Serial.print("IP: ");
      Serial.println(WiFi.localIP());
      serwerSetup (WiFi.localIP());
    }
    if (!buttonClicked) {
      digitalWrite(statusLed, LOW);
    }
    connectionState = true;
    server.handleClient();
  }

  if (  (!digitalRead(button)) and ((lastButtonPressed + 10000 < millis()) or (lastButtonPressed > millis())) ) { //clock sync
    lastButtonPressed = millis();
    analogWrite(statusLed, 30);
    buttonClicked = true;
    if (Time.getFromNTP()) {
      digitalWrite(statusLed, 0);
      Time.toRTC();
    }
  }

  if (buttonClicked and ((lastButtonPressed + 10000 < millis()) or (lastButtonPressed > millis()))) {
    digitalWrite(statusLed, 0);
    buttonClicked = false;
  }

  if ((!digitalRead(Clock)) or Serial.peek() == '*') { // every hour it makes request to db or after '*' from serial
    // ml per hour    l
    //count=0;
    //count--;//for test
    //Serial.println(count);//for test
    Time.getFromRTC();
    requestLib::data dataToSend = {(float)flowCounter * sensorStep / 1000, (double)count * sensorStep / 1000, Time.toStr()};
    flowCounter = 0;
    if (!isEmpty) {

      Serial.println ("Trying sending previous data");
      if (!LittleFS.begin()) {
        RQ.flashError();
      }
      Serial.println("Scanning FS");
      Dir dataFolder = LittleFS.openDir(recordsFolder);
      Serial.println("List of files " + recordsFolder + ":");

      unsigned int numberOfFiles = 0;
      while (dataFolder.next()) {
        Serial.println(dataFolder.fileName());
        numberOfFiles++;
      }
      Serial.println("====================");
      Serial.println(String(numberOfFiles) + " files found");
      unsigned int tableOfRecords [numberOfFiles];
      Serial.println("Sorting files");
      dataFolder.rewind();
      for (unsigned int i = 0; i < numberOfFiles; i++) {
        dataFolder.next();
        tableOfRecords [i] = dataFolder.fileName().toInt();
      }
      dataFolder.rewind();
      //sorting function
      bool u = false;
      do {
        u = false;
        for (unsigned int i = 0; i < numberOfFiles - 1; i++) {
          if (tableOfRecords [i] > tableOfRecords [i + 1]) {
            unsigned int r1 = tableOfRecords [i];
            unsigned int r2 = tableOfRecords [i + 1];
            //arduino hasn't got swap() function
            tableOfRecords [i] = r2;
            tableOfRecords [i + 1] = r1;
            //as swap() function
            u = true;
          }
        }
      } while (u);
      //end of sorting function
      for (unsigned int i = 0; i < numberOfFiles; i++) {
        Serial.println("Trying to send file " + String(tableOfRecords [i]));
        File record = LittleFS.open(recordsFolder + "/" + String(tableOfRecords [i]), "r");
        requestLib::data previousData = {record.parseFloat(), streamParseDouble(&record), delLastChar(record.readStringUntil('\n'))};
        record.close();

        if (!skipDBRequest) {
          if (RQ.sendDataToDB(previousData) != 2) {
            isEmpty = true;
            if (not LittleFS.remove(recordsFolder + "/" + String(tableOfRecords [i]))) {
              RQ.flashError();
            }
            delay(100);
          } else {
            isEmpty = false;
            break;
          }
        } else {
          isEmpty = false;
          break;
        }
      }
      LittleFS.end();
    }


    if (isEmpty) {
      byte responseCode = 2;
      if (!skipDBRequest) {
        responseCode = RQ.sendDataToDB(dataToSend);
      }
      if (responseCode == 2) {
        Serial.println("Saving data in FS");
        digitalWrite(warningLed, HIGH);
        RQ.storeDataInFS (dataToSend);
        isEmpty = false;
      } else {
        digitalWrite(warningLed, LOW);
      }
      if (responseCode == 55) {
        if (Time.getFromNTP()) {
          Time.toRTC();
        }
      }
    } else {
      Serial.println("Saving data in FS");
      digitalWrite(warningLed, HIGH);
      RQ.storeDataInFS (dataToSend);
    }
    Time.clearA2Flag();

    alertFired = false;
    alertAvailable = RQ.getHourLimit();
  }


  if (Serial.peek() == '+' or (!alertFired and alertAvailable and flowCounter > 0)) {
    alertFired = RQ.sendAlert();
  }

  if (Serial.peek() == '/') {
    Serial.println("\nExec commmand: reboot...");
    ESP.restart();
  }

  if (Serial.peek() == '-') {
    Serial.println("\nExec commmand: stats...");
    Serial.print("Free memory:");
    Serial.println(ESP.getFreeHeap(), DEC);
    Serial.print("Memory fragmentation:");
    Serial.println(ESP.getHeapFragmentation(), DEC);

  }

    if (Serial.peek() == '.') {
    Serial.println("\nExec commmand: state...");
    Serial.print("Counter: ");
    Serial.println(count);
    Serial.print("Flow per H: ");
    Serial.println(flowCounter);

  }

  if (Serial.available()) {
    /*Serial.write(*/Serial.read()/*)*/;
  }
}
