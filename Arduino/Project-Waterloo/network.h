String delLastChar(String input){
String tmp;
if (input.length()>0){
for (int i=0;i<(input.length()-1);i++){tmp=tmp+input[i];}
}
return tmp;
}


void serialFlushRead() {
  while (Serial.read() >= 0);
}







void networkSet() {
  digitalWrite(statusLed, HIGH);
  delay(100);
  storeString (storeString (0, ssid), password);
  digitalWrite(warningLed, LOW);
  if (!EEPROM.commit()) {
    RQ.flashError();
  }
}

void scanForNetworks() {
  Serial.println("Scanning for networks");
  int n = (skipNetworkScan) ? 0 : WiFi.scanNetworks();
  Serial.println("scan done");
  if (n == 0) {
    Serial.println("no networks found or scan skipped");
    WifiList = "<br>No networks found</br>";

  } else {
    Serial.print(n);
    Serial.println(" networks found");
    WifiList = "<ol>";
    for (int i = 0; i < n; ++i) {
      // Print SSID and RSSI for each network found
      Serial.print(i + 1);
      Serial.print(": ");
      Serial.print(WiFi.SSID(i));
      Serial.print(" (");
      Serial.print(WiFi.RSSI(i));
      Serial.print(")");
      Serial.println((WiFi.encryptionType(i) == ENC_TYPE_NONE) ? " " : "*");
      WifiList =  WifiList + "<li> " + WiFi.SSID(i) + " (" + WiFi.RSSI(i) + ")" + ((WiFi.encryptionType(i) == ENC_TYPE_NONE) ? " " : "*") + "</li>";
      delay(10);
    }
    WifiList = WifiList + "</ol>";
  }
}


void networkConnect() {
  WiFi.disconnect();
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  Serial.println("Conecting to wifi");
  WebSetupchanged = false;
  connectionState = false;
}

void waitForNetworkConnect(){
  if(WiFi.status() != WL_CONNECTED){
    int i = 1;
    while (WiFi.status() != WL_CONNECTED) {
     delay(200);
     Serial.print(".");
     digitalWrite(warningLed, !digitalRead(warningLed));
      if (i == 22) {
        Serial.println("");
        i = 0;
      }
      i++;
    }
   digitalWrite(warningLed, LOW);
   connectionState = true;
   Serial.println("");
   Serial.print("Connected to ");
   Serial.println(ssid);
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
  }
}

void serwerSetup (IPAddress IP) {
  deviceURL="http://";
  deviceURL = deviceURL + IP.toString();
  server.onNotFound( []() {
    server.sendHeader("Location",deviceURL);
    server.send(301, "text/plain", "");
  });
  server.on("/", []() {
    server.send(200, "text/html", webPageSetup + WifiList + webPageSetup2);
  });
  server.on("/wifiscan", []() {
    server.send(200, "text/html", webPageScan);
    scanForNetworks();
  });
  server.on("/favicon.ico", []() {
    server.send(200, "image/png", favicon, sizeof(favicon));
  });
  server.on("/setwifi", []() {
    if (server.method() != HTTP_POST) {
      server.sendHeader("Location", deviceURL);
      server.send(301, "text/plain", "");
    } else {
      if (server.args() == 3) {
        if (server.argName(0) == "ssid" and server.argName(1) == "password") {
          String message;
          if (server.arg(0).length() > 32) {
            message = message + "SSID too long!" + "<br/>";
          }
          if (server.arg(1).length() > 63) {
            message = message + "Password too long!" + "<br/>";
          }
          if (server.arg(0).length() == 0) {
            message = message + "Blank SSID!" + "<br/>";
          }

          if (message.length() == 0) {
            server.send(200, "text/plain", "Setup changed");
            WebSetupchanged = true;
            ssid = server.arg(0);
            password = server.arg(1);
          }else{
            server.send(200, "text/html", webPageError + message + webPageError2);
          }


        } else {
          server.send(400, "text/plain", "Bad request 2");
        }
      } else {
        server.send(400, "text/plain", "Bad request 1");
        Serial.println(server.args());
      }
    }
  });
  
  
  #ifdef LOGHTTP
  server.on("/log.txt", []() {
    server.send(200, "text/plain",SLOG.logP);
  });
  #endif

  #ifdef OTA
  httpUpdater.setup(&server);
  #endif
  
  server.begin();
  Serial.println("HTTP server started");
}
