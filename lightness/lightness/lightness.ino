#include <Wire.h>
#include <BH1750.h>
#include <i2cdetect.h>


BH1750 lightMeter;
void setup(){
  Serial.begin(9600);
  Wire.begin();
  lightMeter.begin();
}

void loop() { 
  float lux = lightMeter.readLightLevel();
  Serial.println(lux);
  delay(2000);
}
