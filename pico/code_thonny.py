import network
import urequests
import machine
import dht
import time


SSID = "wifirpi"
PASSWORD = "88E4VB1YQBI15TM4UCK9KP1LWQ"


sensor = dht.DHT22(machine.Pin(2))


wlan = network.WLAN(network.STA_IF)
wlan.active(True)
wlan.connect(SSID, PASSWORD)

while not wlan.isconnected():
    print("Connexion WiFi...")
    time.sleep(1)


ip_pico = wlan.ifconfig()[0]

URL = "http://193.48.125.212/ETRS403/save.php"  
ip_serveur = URL.split("/")[2]

print(" Pico :", ip_pico)
print(" Serveur :", ip_serveur)


while True:
    try:
        sensor.measure()

        temp = sensor.temperature()
        hum = sensor.humidity()

        print(" Température :", temp, "°C")
        print(" Humidité :", hum, "%")

       
        data = "t={}&h={}".format(temp, hum)

        response = urequests.post(
            URL,
            data=data,
            headers={"Content-Type": "application/x-www-form-urlencoded"}
        )

        print(" Serveur :", response.text)

        response.close()

    except Exception as e:
        print(" Erreur :", e)

    time.sleep(2)
