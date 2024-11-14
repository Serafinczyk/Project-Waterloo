class timeLib {

  private:
    tm timeData;
    byte  devAddr;
    const char* NTPAdress;
    WiFiUDP* NTPudp;
    const int NTP_PACKET_SIZE = 48;


    String leadingZero(int x) {
      if (x < 10) {
        return "0" + String(x);
      } else {
        return String(x);
      }
    }

    void rtcError() {
      Serial.println("RTC Error!");
      errorFunc();
    }

    void writeToRTC(byte addr, byte data) {
      Wire.beginTransmission(devAddr);
      Wire.write(addr);
      Wire.write(data);
      if (Wire.endTransmission() != 0) {
        rtcError();
      }
    }

    byte getStatus() {
      byte value;

      Wire.beginTransmission(devAddr);
      Serial.print("Reading RTC Status: ");
      Wire.write(0x0F);//sending register address
      if (Wire.endTransmission() != 0) {
        rtcError();
      }
      Wire.beginTransmission(devAddr);
      if (Wire.requestFrom(devAddr, 1) != 1) {
        rtcError();
      }
      if (Wire.available() != 1) {
        rtcError();
      }
      value = Wire.read();
      if (Wire.endTransmission() != 0) {
        rtcError();
      }
      Serial.println(value);

      return value;
    }


  public:
    typedef std::function<void(void)> ErrorHandlerFunction;
    timeLib(byte addr, WiFiUDP* udpService , const char* ntpServerName, ErrorHandlerFunction onError);
    ErrorHandlerFunction errorFunc;
    tm tmStruct() {
      return timeData;
    }
    void getFromRTC() {
      Serial.println("Connecting to RTC");
      Wire.beginTransmission(devAddr);
      Serial.println("Reading DateTime");
      byte x[7];
      Wire.write(0);
      if (Wire.endTransmission() != 0) {
        rtcError();
      }
      Wire.beginTransmission(devAddr);
      if (Wire.requestFrom(devAddr, 7) != 7) {
        rtcError();
      };
      if (Wire.available() != 7) {
        rtcError();
      };
      for (byte i = 0; i < 7; i++) {
        x[i] = Wire.read();
      }
      timeData.tm_sec = (x[0] >> 4) * 10 + (x[0] & 0b00001111);
      timeData.tm_min = (x[1] >> 4) * 10 + (x[1] & 0b00001111);
      timeData.tm_hour = (bitRead(x[2], 5) * 20) + (bitRead(x[2], 4) * 10) + (x[2] & 0b00001111);
      if(x[3]==7){
        timeData.tm_wday = 0;
      }else{
        timeData.tm_wday = x[3];
      }
      timeData.tm_mday = (x[4] >> 4) * 10 + (x[4] & 0b00001111);
      timeData.tm_mon = bitRead(x[5], 4) * 10 + (x[5] & 0b00001111);
      timeData.tm_mon--;// decrementing month for tm struct
      timeData.tm_year = 100 + bitRead(x[5], 7) * 100 + (x[6] >> 4) * 10 + (x[6] & 0b00001111);
      if (Wire.endTransmission() != 0) {
        rtcError();
      }
    }


    void toRTC() {
      Serial.println("Connecting to RTC");
      Serial.println("Setting date & time");
      writeToRTC(1, ((timeData.tm_min / 10) << 4) | timeData.tm_min % 10);//minutes
      if (timeData.tm_hour / 10 == 2) {
        writeToRTC(2, 0b00100000  | (timeData.tm_hour % 10));////hours with all weird switchess
      } else {
        if (timeData.tm_hour / 10 == 1) {
          writeToRTC(2, 0b00010000  | (timeData.tm_hour % 10));
        }
      }
      if(timeData.tm_wday==0){
        writeToRTC(3, 7);//day of week
      }else{
        writeToRTC(3, timeData.tm_wday);//day of week
      }
      
      byte mon = timeData.tm_mon + 1;
      writeToRTC(4, ((timeData.tm_mday / 10) << 4) | timeData.tm_mday % 10);//day of month
      if (timeData.tm_year / 100 == 200) { //months and century
        writeToRTC(5, (0b10000000 | (mon / 10) << 4) | mon % 10);
      } else {
        writeToRTC(5, (0b00000000 | (mon / 10) << 4) | mon % 10);
      }
      byte yearLast2Digits = timeData.tm_year % 100;
      writeToRTC(6, ((yearLast2Digits / 10) << 4) | yearLast2Digits % 10);//year last 2 digits
      writeToRTC(0, ((timeData.tm_sec / 10) << 4) | timeData.tm_sec % 10);// setting seconds will shift registers in rtc
    }

    bool  getFromNTP() {
      NTPudp->begin(1023); // receiving port
      Serial.println("sending NTP packet...");
      byte packetBuffer[ NTP_PACKET_SIZE];
      memset(packetBuffer, 0, NTP_PACKET_SIZE);
      IPAddress host;
      WiFi.hostByName(NTPAdress, host);
      packetBuffer[0] = 0b11100011;
      packetBuffer[1] = 0b00010000;
      packetBuffer[2] = 10; //10s
      packetBuffer[3] = 0; //precision
      packetBuffer[12]  = 49;
      packetBuffer[13]  = 0x4E;
      packetBuffer[14]  = 49;
      packetBuffer[15]  = 52;
      if (!NTPudp->beginPacket(host, 123)) {
        NTPudp->stop();
        Serial.println("NTP Connection Error");
        return false;
      }
      NTPudp->write(packetBuffer, NTP_PACKET_SIZE);
      if (!NTPudp->endPacket()) {
        NTPudp->stop();
        Serial.println("NTP Connection Error");
        return false;
      }
      Serial.println("Sent! Waiting for response");
      int timeOut = 20;
      while (NTPudp->parsePacket() == 0) {
        Serial.println(".");
        delay (10);
        if (timeOut < 0) {
          NTPudp->stop();
          Serial.println("Timeout!!");
          return false;
        }
        timeOut--;
      }
      Serial.println("Response!!");
      Serial.println("Reading and making time");
      NTPudp->read(packetBuffer, NTP_PACKET_SIZE);
      NTPudp->stop();
      time_t Unix = ((int)packetBuffer[40] << 24 | (int)packetBuffer[41] << 16 | (int)packetBuffer[42] << 8 | (int)packetBuffer[43]) - 2208988800UL;
      timeData = *gmtime( &Unix );
      //Serial.println(timeData.tm_wday);
      return true;
    }


    String toStr() {
      return leadingZero(timeData.tm_year + 1900) + '-' + leadingZero(timeData.tm_mon + 1) + '-' + leadingZero(timeData.tm_mday) + ' ' + leadingZero(timeData.tm_hour) + ':' + leadingZero(timeData.tm_min) + ':' + leadingZero(timeData.tm_sec);
    }

    time_t toUnix() {
      return mktime(&timeData);
    }

    bool lostPower() {
      Serial.println("Connecting to RTC");
      byte StatusReg = getStatus();
      writeToRTC(0x0F, StatusReg & 0b01111111);
      return bitRead(StatusReg, 7);
    }

    void prepair(int clockPin) {
      pinMode(clockPin, INPUT);
      Serial.println("Connecting to RTC");

      byte StatusReg = getStatus();
      writeToRTC(0x0F, StatusReg & 0b11111100); //clear alarm flags
      writeToRTC(0x0E, 0b01100100);// enable oscilator in battery mode; disable alarms; disable sqr wave; frequency 1HZ
      Serial.println("Checking rtc connection");
      if (!digitalRead(clockPin)) {
        rtcError();
      }
      writeToRTC(0x0E, 0b01100111);// enable alarms

      //alarm once per second
      writeToRTC(0x07, 0b10000000);
      writeToRTC(0x08, 0b10000000);
      writeToRTC(0x09, 0b10000000);
      writeToRTC(0x0A, 0b10000000);

      //alarm once per hour
      writeToRTC(0x0B, 0b00000000);
      writeToRTC(0x0C, 0b10000000);
      writeToRTC(0x0D, 0b10000000);
      delay(1200);
      if (digitalRead(clockPin)) {
        rtcError();
      }

      writeToRTC(0x0E, 0b01100110);// disable alarm 1
      writeToRTC(0x0F, StatusReg & 0b11111100); //clear alarm flags
      Serial.println("Success");
    }

    void clearA2Flag() {
      byte StatusReg = getStatus();;
      writeToRTC(0x0F, StatusReg & 0b11111101);
    }


};


timeLib::timeLib(byte addr, WiFiUDP* udpService, const char* ntpServerName, ErrorHandlerFunction onError) {
  timeData.tm_sec = 0;
  timeData.tm_min = 0;
  timeData.tm_hour = 0;
  timeData.tm_mday = 0;
  timeData.tm_mon = 0;
  timeData.tm_year = 0;
  timeData.tm_wday = 0;
  timeData.tm_yday = 0;
  timeData.tm_isdst = 0;
  devAddr = addr;
  NTPAdress = ntpServerName;
  NTPudp = udpService;
  errorFunc = onError;
}
