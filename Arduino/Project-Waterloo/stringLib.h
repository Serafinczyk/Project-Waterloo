int storeString (int startAdress, String text) { // return size of saved string
  EEPROM.write(startAdress, text.length());
  int i;
  for (i = 0; i < text.length(); ++i)
  {
    EEPROM.write(i + 1 + startAdress, text[i]);
  }
  return i + 1 + startAdress;
}

double streamParseDouble(Stream * stream) {
  stream->setTimeout(100);
  String value = stream->readStringUntil('.');
  String fraction = stream->readStringUntil('\n');
  double state = value.toInt();
  state = state + ((double)fraction.toInt() / 100000);
  Serial.println("Double = " + String(state, 5));
  return state;
}


#ifdef COMBINELOG
#define LOGHTTP
static class {
private:
    void addString(String b){
     if(strlen(logP)+b.length()>=BUFLOGSIZE){memset(logP, 0, BUFLOGSIZE);strcpy(logP, "...buffer overflow...\n");}
     strcat(logP, b.c_str());
    }
public:
    char logP[BUFLOGSIZE];
    void begin(int x) {Serial.begin(x);}
    
    void print(String x) {addString(x) ;Serial.print(x);}
    void print(int x) {addString(String(x));Serial.print(x);}
    void print(double x,int y) {addString(String(x,y));Serial.print(x,y);}
    void print(IPAddress IP) {addString(IP.toString());Serial.print(IP.toString());}
    
    void println(String x) {addString(x + "\n");Serial.println(x);}
    void println(int x) {addString(String(x) + "\n");Serial.println(x);}
    void println(double x,int y) {addString(String(x,y) + "\n");Serial.println(x,y);}
    void println(IPAddress IP) {addString(IP.toString()+ "\n");Serial.println(IP.toString());}
    
    byte available(...){return Serial.available();}
    String readStringUntil(char x){return Serial.readStringUntil(x);}
    int read(...){return Serial.read();}
    int peek(...){return Serial.peek();}
    void write(char x) {addString(String(x));Serial.write(x);}
}SLOG;
#define Serial SLOG
#endif



#ifdef LOGHTTPONLY
#define LOGHTTP
static class {
private:
    void addString(String b){
      if(strlen(logP)+b.length()>=BUFLOGSIZE){logP="...buffer overflow...\n";}
      strcat(logP, b);
    }
    
public:
    char logP[BUFLOGSIZE] = "";
    void begin(...) {}
    
    void print(String x) {addString(x) ;}
    void print(int x) {addString(String(x));}
    void print(double x,int y) {addString(String(x,y));}
    void print(IPAddress IP) {addString(IP.toString());}
    
    void println(String x) {addString(x + "\n");}
    void println(int x) {addString(String(x) + "\n");}
    void println(double x,int y) {addString(String(x,y) + "\n");}
    void println(IPAddress IP) {addString(IP.toString()+ "\n");}
    
    int available(...){return 0;}
    String readStringUntil(...){return "";}
    int read(...){return -1;}
    int peek(...){return -1;}
    void write(char x) {addString(String(x));}
}SLOG;
#define Serial SLOG
#endif



#ifdef NOLOG
#define Serial NO
static class {
public:
    void begin(...) {}
    void print(...) {}
    void println(...) {}
    int available(...){return 0;}
    String readStringUntil(...){return "";}
    int read(...){return -1;}
    int peek(...){return -1;}
    void write(...) {}
}Serial;
#endif
