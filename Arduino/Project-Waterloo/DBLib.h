

class requestLib {

  private:
    timeLib* time;
    const String* serverAddr;
    const char* cert;
    BearSSL::WiFiClientSecure client;
    HTTPClient http;
    int httpCode;
    unsigned int limits[7] = {
      0b11111111000000000000000000000000,//Sunday
      0b11111111000000000000000000000000,//Monday
      0b11111111000000000000000000000000,//Tuesday
      0b11111111000000000000000000000000,//Wednesday
      0b11111111000000000000000000000000,//Thursday
      0b11111111000000000000000000000000,//Friday
      0b11111111000000000000000000000000//Saturday
    };

    bool sendRequest(char* scriptName, String RQParams) {
      if (!WiFi.status() == WL_CONNECTED) {
        Serial.println("Error - Network not connected");
        return false;
      }
      Serial.print("Free memory:");
      Serial.println(ESP.getFreeHeap(), DEC);
      Serial.print("Memory fragmentation:");
      Serial.println(ESP.getHeapFragmentation(), DEC);
      //client.setInsecure();
      BearSSL::X509List certificate(cert);
      client.setTrustAnchors(&certificate);
      time->getFromRTC();
      Serial.print("NOW is:");
      Serial.println(time->toUnix());
      Serial.println(time->toStr());
      client.setX509Time(time->toUnix());
      Serial.println("Connecting to server");
      if (!http.begin(client, *serverAddr + scriptName)) {
        Serial.println("Error - Server error");
        return false;
      }
      Serial.println("Sending request");
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      try {
        httpCode = http.POST(RQParams);
      } catch (...) {
        RAMError();
      }
      return true;
    }

    void parseLimits() {
      //Serial.println(client.readString());
      Serial.println("Bit encoded limits parsed:");
      for (int day = 0; day < 7; day++) {
        for (int bit = 31; bit >= 0; bit--) {
          if (client.read() == '1') {
            bitSet(limits[day], bit);
          } else {
            bitClear(limits[day], bit);
          }
        }
        Serial.println(limits[day]);
        client.read();
      }
    }

    void RAMError() {
      Serial.println("RAM error!");
      time->errorFunc();
    }


  public:
    requestLib(timeLib* timeLibP, const String* URL, const char* txtCert);
    struct data {
      float flow;
      double counter;
      String dateAndTime;
    };

    void flashError() {
      Serial.println("Saving error!");
      Serial.println("Memory corupted!");
      time->errorFunc();
    }

    bool getHourLimit() {
      return bitRead(limits[time->tmStruct().tm_wday], time->tmStruct().tm_hour);
    }

    bool getStatus(volatile unsigned int* statusVar) {
      if (sendRequest("get.php", "chipID=" + String (ESP.getChipId()))) {
        if (httpCode == HTTP_CODE_OK) {
          delay(10);
          client.readStringUntil('\n');
          if (client.peek() == '*') {
            client.read();
            *statusVar = *statusVar + ((double)streamParseDouble(&client) * 1000 / sensorStep);
            parseLimits();
            http.end();
            httpCode = 0;
            return true;
          } else {
            Serial.print("Data cannot be read - Server response: ");
            Serial.println(client.readStringUntil('\n'));
            http.end();
            httpCode = 0;
            return false;
          }
        } else {
          Serial.print("Cannot read data - Error code: ");
          Serial.println(String(httpCode) + " " + http.errorToString(httpCode));
          http.end();
          httpCode = 0;
          return false;
        }
      } else {
        httpCode = 0;
        return false;
      }
    }

    byte sendDataToDB(data x) {
      Serial.println("Sending data from: " + x.dateAndTime);
      if (sendRequest("send.php", "chipID=" + String (ESP.getChipId()) + "&flow=" + String(x.flow, 2) + "&counter=" + String(x.counter, 5) + "&dateAndTime=" + x.dateAndTime)) {
        if (httpCode == HTTP_CODE_OK) {
          delay(10);
          client.readStringUntil('\n');
          if (client.peek() == '*') {
            client.read();
            parseLimits();
            Serial.println("Data sent!");
            http.end();
            httpCode = 0;
            return 1;
          } else {
            Serial.print("Data cannot be send - Server response: ");
            String response = client.readStringUntil('\n');

            Serial.println(response);
            if (response == "Same row is already in DB\r") {
              http.end();
              httpCode = 0;
              return 55;
            } else {
              http.end();
              httpCode = 0;
              return 2;
            }
          }



        } else {
          Serial.print("Data cannot be send - Error code: ");
          Serial.println(String(httpCode) + " " + http.errorToString(httpCode));
          http.end();
          httpCode = 0;
          return 2;
        }
      } else {
        httpCode = 0;
        return 2;
      }
    }

    void storeDataInFS (data x) {
      if (!LittleFS.begin()) {
        flashError();
      }
      Dir dataFolder = LittleFS.openDir(recordsFolder);
removing:
      unsigned int i = 0;
      i--;
      Serial.println("Max file number: " + String(i) + "(" + String(i, BIN) + " in binary)");

      Serial.println("List of files " + recordsFolder + ":");

      String Name;  //uint verssion is N
      unsigned int N = 0;

      String EarliestName; //uint verssion is i

      bool isEmpty = true;

      unsigned int numberOfFiles = 0;

      while (dataFolder.next()) {
        isEmpty = false;
        String fileName = dataFolder.fileName();
        Serial.println(fileName);
        unsigned int recorNumber = fileName.toInt();
        if (recorNumber > N) {
          N = recorNumber;
        }
        if (recorNumber < i) {
          i = recorNumber;
        }
        numberOfFiles++;
      }
      Name = String(N + 1);
      EarliestName = String (i);



      Serial.println("====================");
      if (isEmpty) {
        Serial.println("Directory is empty");
        Name = "1";
      } else {
        Serial.println(String(numberOfFiles) + " files found!");
        Serial.println("files limit is: " + String(recordsLimit));
        Serial.println("First file is " + EarliestName);
        if (numberOfFiles == recordsLimit) {
          Serial.println ("Removing file " + EarliestName);
          if (not LittleFS.remove(recordsFolder + "/" + EarliestName)) {
            flashError();
          }
        }
        if (numberOfFiles > recordsLimit) {
          Serial.println ("Removing file " + EarliestName);
          if (not LittleFS.remove(recordsFolder + "/" + EarliestName)) {
            flashError();
          }
          dataFolder.rewind();
          goto removing;
        }
      }
      Serial.println("Creating file with name " + Name + ":");
      File record = LittleFS.open(recordsFolder + "/" + Name, "w");
      record.println(x.flow, 2);
      Serial.println(x.flow, 2);
      record.println(x.counter, 5);
      Serial.println(x.counter, 5);
      record.println(x.dateAndTime);
      Serial.println(x.dateAndTime);
      record.close();
      LittleFS.end();
      Serial.println("File created!");

    }

    bool sendAlert() {
      Serial.println("Sending an alert");
      if (sendRequest("alert.php", "chipID=" + String (ESP.getChipId()))) {
        if (httpCode == HTTP_CODE_OK) {
          String response = http.getString();
          if (response == "OK") {
            Serial.println("Sent!");
            http.end();
            httpCode = 0;
            return true;
          } else {
            Serial.print("Alert cannot be send - Server response: ");
            Serial.println(response);
            http.end();
            httpCode = 0;
            return false;
          }
        } else {
          Serial.print("Alert cannot be send - Error code: ");
          Serial.println(String(httpCode) + " " + http.errorToString(httpCode));
          http.end();
          httpCode = 0;
          return false;
        }
      } else {
        httpCode = 0;
        return false;
      }
    }


};


requestLib::requestLib(timeLib* timeLibP, const String* URL, const char* txtCert) {
  time = timeLibP;
  serverAddr = URL;
  cert = txtCert;
  httpCode = 0;
}
